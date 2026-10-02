<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendTemplatedEmailJob;
use App\Models\User;
use App\Services\ActivityLoggerService;
use App\Services\EmailDispatcherService;
use App\Services\EmailOtpService;
use App\Services\EmailPreferenceService;
use App\Services\EmailSettingService;
use App\Support\EmailQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthWebController extends Controller
{
    private const GENERIC_RESET_MESSAGE = 'If an account exists for that email, we have sent a password reset link. Please check your inbox and spam folder.';

    private const GENERIC_LOGIN_CODE_MESSAGE = 'If that email belongs to a verified account, we have sent a sign-in code.';

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login', ['loginOtpEnabled' => EmailSettingService::isAutomationEnabled('login_otp')]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $credentials['phone'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid phone number or password.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'phone' => ['Your account has been deactivated.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        ActivityLoggerService::logCustomer(
            'customer.login',
            $user,
            "Customer logged in: {$user->name}",
            ['actor' => $user, 'source' => 'storefront']
        );

        if ($user->isStaff()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->mustVerifyEmail()) {
            return redirect()->route('auth.verify-otp')
                ->with('info', 'Please verify your email address to finish setting up your account.');
        }

        return redirect()->intended(route('home'));
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register', ['emailRequired' => EmailSettingService::requiresRegistrationEmail()]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => [
                EmailSettingService::requiresRegistrationEmail() ? 'required' : 'nullable',
                'email:rfc',
                'max:100',
                Rule::unique('users', 'email'),
            ],
            'password' => 'required|string|min:6',
            'marketing_opt_in' => 'nullable|boolean',
        ], [
            'email.unique' => 'An account with this email already exists. Please sign in or reset your password.',
        ]);

        $email = ! empty($data['email']) ? EmailOtpService::normalize($data['email']) : null;
        $needsVerification = $email !== null && EmailSettingService::isAutomationEnabled('account_otp');

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $email,
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'status' => 'active',
            'marketing_opt_in' => false,
            'email_verification_required' => $needsVerification,
        ]);

        if ($request->boolean('marketing_opt_in') && $email) {
            EmailPreferenceService::setUserConsent($user, true, 'registration');
        }

        Auth::login($user);
        $request->session()->regenerate();

        ActivityLoggerService::logCustomer(
            'customer.registered',
            $user,
            "Customer registered: {$user->name} ({$user->phone})",
            ['actor' => $user, 'source' => 'storefront']
        );

        if ($needsVerification) {
            $sent = EmailOtpService::issueAndSend($email, EmailOtpService::TYPE_ACCOUNT, $user->name, $user->id);

            return redirect()->route('auth.verify-otp')->with(
                $sent['success'] ? 'success' : 'error',
                $sent['success']
                    ? 'Account created! Enter the '.EmailOtpService::length().'-digit code we sent to your email.'
                    : 'Account created, but we could not send the verification email right now. Please use "Resend code" in a moment.'
            );
        }

        if ($email) {
            $this->queueWelcome($user);
        }

        return redirect()->route('home')->with('success', 'Account created successfully!');
    }

    public function showVerifyOtp(Request $request)
    {
        $user = $request->user();
        if (! $user || empty($user->email)) {
            return redirect()->route('home');
        }

        if ($user->isEmailVerified()) {
            return redirect()->route('home')->with('success', 'Your email address is already verified.');
        }

        return view('auth.verify-otp', [
            'email' => $user->email,
            'maskedEmail' => self::maskEmail($user->email),
            'cooldown' => EmailOtpService::cooldownRemaining($user->email, EmailOtpService::TYPE_ACCOUNT),
            'codeLength' => EmailOtpService::length(),
            'expiresMinutes' => EmailOtpService::expiresMinutes(),
            'canSkip' => ! EmailSettingService::verificationEnforced(),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $user = $request->user();
        if (! $user || empty($user->email)) {
            return redirect()->route('home');
        }

        $request->validate(['code' => 'required|string|max:12']);

        if ($user->isEmailVerified()) {
            return redirect()->route('home')->with('success', 'Your email address is already verified.');
        }

        $result = EmailOtpService::verifyOtp($user->email, (string) $request->input('code'), EmailOtpService::TYPE_ACCOUNT);
        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $user->refresh();
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->queueWelcome($user);

        return redirect()->intended(route('home'))->with('success', 'Email verified successfully! Welcome to '.config('app.name', 'Mama Bazar').'.');
    }

    public function resendOtp(Request $request)
    {
        $user = $request->user();
        if (! $user || empty($user->email)) {
            return redirect()->route('home');
        }

        if ($user->isEmailVerified()) {
            return redirect()->route('home')->with('success', 'Your email address is already verified.');
        }

        $result = EmailOtpService::issueAndSend($user->email, EmailOtpService::TYPE_ACCOUNT, $user->name, $user->id);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|max:191']);
        $email = EmailOtpService::normalize((string) $request->input('email'));

        $limiterKey = 'password-reset:'.sha1($email);
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return back()->with('success', self::GENERIC_RESET_MESSAGE);
        }
        RateLimiter::hit($limiterKey, 900);

        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'active')
            ->orderByRaw('email_verified_at IS NULL')
            ->orderByDesc('id')
            ->first();

        if ($user && EmailSettingService::isAutomationEnabled('password_reset')) {
            $token = Str::random(64);
            $minutes = max(10, (int) config('email_system.password_reset_expires_minutes', 60));
            $user->forceFill([
                'reset_token_hash' => hash('sha256', $token),
                'reset_token_expires_at' => now()->addMinutes($minutes),
            ])->save();

            $url = route('auth.reset-password', ['token' => $token]);
            EmailDispatcherService::sendTemplate('password_reset', $email, $user->name, [
                'customer_name' => $user->name,
                'customer_email' => $email,
                'reset_url' => $url,
                'reset_expires_minutes' => (string) $minutes,
            ], 'auth', ['user_id' => $user->id, 'redact' => [$token, $url]]);
        }

        return back()->with('success', self::GENERIC_RESET_MESSAGE);
    }

    public function showResetPassword(string $token)
    {
        $user = $this->userForResetToken($token);

        return view('auth.reset-password', [
            'token' => $user ? $token : null,
            'invalid' => $user === null,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $this->userForResetToken((string) $request->input('token'));
        if (! $user) {
            return redirect()->route('auth.forgot-password')
                ->with('error', 'This password reset link is invalid or has expired. Please request a new one.');
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'reset_token_hash' => null,
            'reset_token_expires_at' => null,
            'remember_token' => Str::random(60),
        ])->save();

        $this->queueSecurityNotice($user, 'Your password was changed.');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Your password has been reset successfully.');
    }

    public function showLoginOtp()
    {
        abort_unless(EmailSettingService::isAutomationEnabled('login_otp'), 404);
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login-otp', [
            'email' => session('login_otp_email'),
            'codeLength' => EmailOtpService::length(),
            'cooldown' => $this->loginOtpCooldown(),
        ]);
    }

    public function sendLoginOtp(Request $request)
    {
        abort_unless(EmailSettingService::isAutomationEnabled('login_otp'), 404);
        $request->validate(['email' => 'required|email|max:191']);
        $email = EmailOtpService::normalize((string) $request->input('email'));

        // Same response and countdown whether or not the account exists.
        if ($this->loginOtpCooldown() > 0 && $request->session()->get('login_otp_email') === $email) {
            return redirect()->route('auth.login-otp');
        }

        $user = $this->loginOtpUser($email);
        if ($user) {
            EmailOtpService::issueAndSend($email, EmailOtpService::TYPE_LOGIN, $user->name, $user->id);
        }

        $request->session()->put('login_otp_email', $email);
        $request->session()->put('login_otp_sent_at', now()->timestamp);

        return redirect()->route('auth.login-otp')->with('success', self::GENERIC_LOGIN_CODE_MESSAGE);
    }

    public function verifyLoginOtp(Request $request)
    {
        abort_unless(EmailSettingService::isAutomationEnabled('login_otp'), 404);
        $request->validate(['code' => 'required|string|max:12']);
        $email = (string) $request->session()->get('login_otp_email', '');

        $user = $email !== '' ? $this->loginOtpUser($email) : null;
        $result = $user
            ? EmailOtpService::verifyOtp($email, (string) $request->input('code'), EmailOtpService::TYPE_LOGIN)
            : ['success' => false];

        if (! $result['success']) {
            return back()->with('error', 'Invalid or expired code. Please try again or request a new code.');
        }

        $request->session()->forget(['login_otp_email', 'login_otp_sent_at']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', 'Signed in successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, max(1, mb_strlen($local) - 1)));

        return $visible.str_repeat('•', max(1, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }

    private function loginOtpCooldown(): int
    {
        $sentAt = (int) session('login_otp_sent_at', 0);

        return $sentAt > 0 ? max(0, EmailOtpService::cooldownSeconds() - (now()->timestamp - $sentAt)) : 0;
    }

    private function userForResetToken(string $token): ?User
    {
        if (strlen($token) !== 64) {
            return null;
        }

        return User::where('reset_token_hash', hash('sha256', $token))
            ->where('reset_token_expires_at', '>', now())
            ->where('status', 'active')
            ->first();
    }

    /** Email-code sign-in is limited to active customers with a verified email. */
    private function loginOtpUser(string $email): ?User
    {
        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->whereNotNull('email_verified_at')
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        return $user && ! $user->isStaff() ? $user : null;
    }

    private function queueWelcome(User $user): void
    {
        if (! $user->email || ! EmailSettingService::isAutomationEnabled('welcome')) {
            return;
        }

        EmailQueue::dispatch(new SendTemplatedEmailJob('welcome_email', $user->email, $user->name, [
            'customer_name' => $user->name,
            'customer_email' => $user->email,
        ], 'transactional', ['dedupe_key' => "user:{$user->id}:welcome", 'user_id' => $user->id]));
    }

    public static function queueSecurityNotice(User $user, string $event, ?string $toEmail = null): void
    {
        $to = $toEmail ?: $user->email;
        if (! $to || ! EmailSettingService::isAutomationEnabled('security_notice')) {
            return;
        }

        EmailQueue::dispatch(new SendTemplatedEmailJob('account_security_notice', $to, $user->name, [
            'customer_name' => $user->name,
            'customer_email' => $to,
            'security_event' => $event,
            'security_time' => now()->timezone(config('app.timezone'))->format('d M Y, h:i A'),
        ], 'auth', ['user_id' => $user->id]));
    }
}
