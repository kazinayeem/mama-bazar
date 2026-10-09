<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminPermission;
use App\Services\AuditService;
use App\Services\SslcommerzService;
use App\Support\SslcommerzSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * PIN-protected SSLCOMMERZ configuration. The unlock PIN only authorizes editing
 * the gateway settings for a short, server-side window; it grants no other rights.
 */
class AdminPaymentGatewayController extends Controller
{
    public const UNLOCK_SESSION_KEY = 'payment_gateway_unlock';

    private const MAX_PIN_ATTEMPTS = 5;

    private const PIN_LOCKOUT_SECONDS = 900;

    private const MAX_UNLOCK_MINUTES = 30;

    public static function isUnlocked(Request $request): bool
    {
        $unlock = $request->session()->get(self::UNLOCK_SESSION_KEY);

        return is_array($unlock)
            && (int) ($unlock['user_id'] ?? 0) === (int) $request->user()?->id
            && (int) ($unlock['expires_at'] ?? 0) > now()->getTimestamp();
    }

    public static function unlockExpiresAt(Request $request): ?int
    {
        return self::isUnlocked($request) ? (int) $request->session()->get(self::UNLOCK_SESSION_KEY.'.expires_at') : null;
    }

    public function unlock(Request $request): RedirectResponse
    {
        $request->validate(['pin' => 'required|string|max:32']);

        $user = $request->user();
        $limiterKey = 'payment-gateway-unlock:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_PIN_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);
            $this->audit($request, 'payment_gateway.unlock_blocked', ['reason' => 'too_many_attempts'], 'failure');

            return back()->withErrors(['pin' => "Too many incorrect PIN attempts. Try again in {$minutes} minute(s)."], 'gatewayUnlock');
        }

        $configuredPin = (string) config('services.payment_settings.unlock_pin', '');
        if ($configuredPin === '') {
            $this->audit($request, 'payment_gateway.unlock_failed', ['reason' => 'pin_not_configured'], 'failure');

            return back()->withErrors(['pin' => 'Payment settings unlock is not configured on the server. Set PAYMENT_SETTINGS_UNLOCK_PIN.'], 'gatewayUnlock');
        }

        if (! hash_equals($configuredPin, (string) $request->input('pin'))) {
            RateLimiter::hit($limiterKey, self::PIN_LOCKOUT_SECONDS);
            $remaining = RateLimiter::remaining($limiterKey, self::MAX_PIN_ATTEMPTS);
            $this->audit($request, 'payment_gateway.unlock_failed', ['reason' => 'invalid_pin', 'attempts_remaining' => $remaining], 'failure');

            return back()->withErrors(['pin' => $remaining > 0
                ? "Incorrect PIN. {$remaining} attempt(s) remaining."
                : 'Incorrect PIN. Unlocking is blocked for 15 minutes.'], 'gatewayUnlock');
        }

        RateLimiter::clear($limiterKey);
        $minutes = min(self::MAX_UNLOCK_MINUTES, max(1, (int) config('services.payment_settings.unlock_minutes', 10)));
        $request->session()->put(self::UNLOCK_SESSION_KEY, [
            'user_id' => (int) $user->id,
            'expires_at' => now()->addMinutes($minutes)->getTimestamp(),
        ]);
        $this->audit($request, 'payment_gateway.unlocked', ['valid_for_minutes' => $minutes]);

        return back()->with('success', "Payment gateway configuration unlocked for {$minutes} minutes.");
    }

    public function lock(Request $request): RedirectResponse
    {
        $request->session()->forget(self::UNLOCK_SESSION_KEY);
        $this->audit($request, 'payment_gateway.locked', ['reason' => 'manual']);

        return back()->with('success', 'Payment gateway configuration locked.');
    }

    public function update(Request $request): RedirectResponse
    {
        if (! self::isUnlocked($request)) {
            $request->session()->forget(self::UNLOCK_SESSION_KEY);

            return back()->with('error', 'Payment gateway configuration is locked. Unlock it with the PIN and try again.');
        }

        $validated = $request->validate([
            'store_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'store_password' => 'nullable|string|max:255',
            'clear_store_id' => 'nullable|boolean',
            'clear_store_password' => 'nullable|boolean',
            'mode' => 'required|in:sandbox,live',
            'currency' => 'required|in:'.implode(',', SslcommerzSettings::CURRENCIES),
            'enabled' => 'nullable|boolean',
            'confirm_live' => 'nullable|boolean',
        ], [
            'store_id.regex' => 'Store ID may only contain letters, numbers, dots, dashes and underscores.',
        ]);

        $user = $request->user();
        $before = SslcommerzSettings::load();
        $wantsLive = $validated['mode'] === 'live';
        $canManageLive = EnsureAdminPermission::allows($user, ['payment_methods.enable_live']);

        if ($wantsLive && ! $canManageLive) {
            $this->audit($request, 'payment_gateway.live_denied', ['mode_from' => $before->mode(), 'mode_to' => 'live'], 'failure');

            return back()->with('error', 'Only a Super Admin or a member with "Activate Live Payments" permission can change Live mode settings.');
        }
        if ($wantsLive && $before->sandbox && ! $request->boolean('confirm_live')) {
            return back()->withErrors(['confirm_live' => 'Confirm that you want to switch to Live mode, where real customer payments are charged.'])->withInput($request->except('store_password'));
        }

        $changes = [
            'sandbox' => ! $wantsLive,
            'currency' => $validated['currency'],
            'updated_by' => (int) $user->id,
        ];
        $credentialActions = [];
        foreach (['store_id', 'store_password'] as $field) {
            $current = $field === 'store_id' ? $before->storeId() : $before->storePassword();
            if ($request->boolean('clear_'.$field)) {
                if (filled($current) && $before->{$field === 'store_id' ? 'storeIdSource' : 'storePasswordSource'} === SslcommerzSettings::SOURCE_DATABASE) {
                    $changes[$field] = '';
                    $credentialActions[$field] = 'cleared';
                }
            } elseif (filled($validated[$field] ?? null) && $validated[$field] !== $current) {
                $changes[$field] = $validated[$field];
                $credentialActions[$field] = 'replaced';
            }
        }

        $after = SslcommerzSettings::persist($changes);

        $method = SslcommerzSettings::method();
        $wasEnabled = (bool) $method->enabled;
        $warning = null;
        if ($request->boolean('enabled')) {
            $warning = $after->enableBlocker($user);
            $method->enabled = $warning === null;
            if ($warning === null) {
                $method->maintenance_mode = false;
            }
        } else {
            $method->enabled = false;
        }
        $method->save();

        $changedSettings = array_keys($credentialActions);
        if ($before->mode() !== $after->mode()) {
            $changedSettings[] = 'mode';
        }
        if ($before->currency !== $after->currency) {
            $changedSettings[] = 'currency';
        }
        if ($wasEnabled !== (bool) $method->enabled) {
            $changedSettings[] = 'enabled';
        }

        $this->audit($request, 'payment_gateway.updated', [
            'changed_settings' => $changedSettings,
            'credential_actions' => $credentialActions,
            'mode_from' => $before->mode(),
            'mode_to' => $after->mode(),
            'enabled_from' => $wasEnabled,
            'enabled_to' => (bool) $method->enabled,
            'credential_source' => $after->storePasswordSource,
        ]);

        $request->session()->forget(self::UNLOCK_SESSION_KEY);

        $redirect = back()->with('success', $changedSettings === []
            ? 'No changes to save. Configuration locked.'
            : 'SSLCOMMERZ configuration saved and locked.');

        return $warning !== null ? $redirect->with('error', 'Gateway left disabled: '.$warning) : $redirect;
    }

    public function test(Request $request, SslcommerzService $sslcommerz): RedirectResponse
    {
        $settings = $sslcommerz->settings();
        $result = $sslcommerz->testConnection();

        $changes = [
            'last_test' => [
                'ok' => $result['ok'],
                'message' => $result['message'],
                'mode' => $settings->mode(),
                'at' => now()->toIso8601String(),
            ],
        ];
        if ($result['ok']) {
            $changes['validated_fingerprint'] = $settings->fingerprint();
        } elseif (! empty($result['credentialsRejected'])) {
            $changes['validated_fingerprint'] = null;
        }
        if ($settings->hasCredentials() || array_key_exists('validated_fingerprint', $changes)) {
            SslcommerzSettings::persist($changes);
        }

        $this->audit($request, 'payment_gateway.tested', [
            'mode' => $settings->mode(),
            'result' => $result['ok'] ? 'passed' : 'failed',
            'message' => $result['message'],
        ], $result['ok'] ? 'success' : 'failure');

        return back()->with($result['ok'] ? 'success' : 'error', 'Test Configuration: '.$result['message']);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function audit(Request $request, string $action, array $details, string $status = 'success'): void
    {
        $user = $request->user();

        AuditService::log([
            'actorId' => $user?->id,
            'actorName' => $user?->name ?? 'Admin',
            'actorEmail' => $user?->email,
            'action' => $action,
            'targetType' => 'PaymentGateway',
            'targetId' => SslcommerzSettings::METHOD_CODE,
            'details' => $details,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'status' => $status,
        ]);
    }
}
