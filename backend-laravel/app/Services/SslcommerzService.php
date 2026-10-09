<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\SslcommerzTransaction;
use App\Support\SslcommerzSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * SSLCOMMERZ hosted checkout (API v4).
 *
 * An order is only marked paid after the transaction has been confirmed through
 * the official Order Validation API — browser redirects and IPN posts are
 * treated as hints that trigger that server-to-server check.
 */
class SslcommerzService
{
    public const MIN_AMOUNT = 10;

    public const MAX_AMOUNT = 500000;

    public const OUTCOME_PAID = 'paid';

    public const OUTCOME_ALREADY_PROCESSED = 'already_processed';

    public const OUTCOME_HELD = 'held';

    public const OUTCOME_DUPLICATE = 'duplicate';

    public const OUTCOME_INVALID = 'invalid';

    public const OUTCOME_UNKNOWN = 'unknown_transaction';

    public const OUTCOME_GATEWAY_ERROR = 'gateway_error';

    private const PAID_PAYMENT_STATUSES = ['verified', 'success'];

    private ?SslcommerzSettings $settings = null;

    public function settings(): SslcommerzSettings
    {
        return $this->settings ??= SslcommerzSettings::load();
    }

    /**
     * Create a gateway session for an unpaid order.
     *
     * @return array{success: bool, redirectUrl: ?string, message: ?string, transaction: ?SslcommerzTransaction}
     */
    public function initiatePayment(Order $order): array
    {
        $settings = $this->settings();

        if ($order->payment_method !== SslcommerzSettings::METHOD_CODE) {
            return $this->initiationFailure('This order does not use online payment.');
        }
        if (in_array($order->payment_status, self::PAID_PAYMENT_STATUSES, true) || $order->payment_status === 'payment_verification') {
            return $this->initiationFailure('This order has already been paid.');
        }
        if (in_array($order->status, ['cancelled', 'returned', 'delivered'], true)) {
            return $this->initiationFailure('This order can no longer be paid online.');
        }
        if (! $settings->isCheckoutReady()) {
            return $this->initiationFailure('Online payment is temporarily unavailable. Please contact us to complete your order.');
        }

        $amount = round((float) $order->total_price, 2);
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return $this->initiationFailure('This order amount cannot be paid online.');
        }

        try {
            $transaction = SslcommerzTransaction::create([
                'order_id' => $order->id,
                'tran_id' => $this->generateTranId($order),
                'amount' => $amount,
                'currency' => $settings->currency,
                'mode' => $settings->mode(),
                'status' => SslcommerzTransaction::STATUS_INITIATED,
            ]);
        } catch (Throwable $e) {
            Log::error('SSLCOMMERZ transaction could not be recorded', ['order_id' => $order->id, 'error' => class_basename($e)]);

            return $this->initiationFailure('Online payment is temporarily unavailable. Please try again shortly or contact us.');
        }

        try {
            $response = $this->http()->asForm()->post($settings->baseUrl().'/gwprocess/v4/api.php', $this->sessionPayload($order, $transaction, $settings));
            $body = $response->json() ?? [];
        } catch (ConnectionException) {
            $this->markInitiationFailed($transaction, 'Gateway connection timed out');

            return $this->initiationFailure('We could not reach the payment gateway. Please try again in a moment.', $transaction);
        } catch (Throwable $e) {
            Log::error('SSLCOMMERZ session initiation error', ['tran_id' => $transaction->tran_id, 'error' => class_basename($e)]);
            $this->markInitiationFailed($transaction, 'Gateway request failed');

            return $this->initiationFailure('We could not start the online payment. Please try again.', $transaction);
        }

        $gatewayUrl = (string) ($body['GatewayPageURL'] ?? '');
        if (strtoupper((string) ($body['status'] ?? '')) !== 'SUCCESS' || ! $this->isTrustedGatewayUrl($gatewayUrl)) {
            $reason = Str::limit(strip_tags((string) ($body['failedreason'] ?? 'Session was not created')), 240);
            Log::warning('SSLCOMMERZ session initiation rejected', ['tran_id' => $transaction->tran_id, 'reason' => $reason]);
            $this->markInitiationFailed($transaction, $reason);

            return $this->initiationFailure('The payment gateway could not start your payment. Please try again or choose another payment method.', $transaction);
        }

        $transaction->update(['session_key' => Str::limit((string) ($body['sessionkey'] ?? ''), 100, '')]);

        return ['success' => true, 'redirectUrl' => $gatewayUrl, 'message' => null, 'transaction' => $transaction];
    }

    /**
     * Process a success redirect or a VALID IPN: validate with SSLCOMMERZ, then settle the order once.
     *
     * @param  array<string, mixed>  $payload
     * @return array{outcome: string, transaction: ?SslcommerzTransaction}
     */
    public function handleValidCallback(array $payload, string $source): array
    {
        $transaction = SslcommerzTransaction::query()->where('tran_id', (string) ($payload['tran_id'] ?? ''))->first();
        if (! $transaction) {
            return ['outcome' => self::OUTCOME_UNKNOWN, 'transaction' => null];
        }

        if ($transaction->isFinal()) {
            return ['outcome' => self::OUTCOME_ALREADY_PROCESSED, 'transaction' => $transaction];
        }

        if (isset($payload['verify_sign'], $payload['verify_key']) && ! $this->verifySignature($payload)) {
            Log::warning('SSLCOMMERZ callback signature mismatch', ['tran_id' => $transaction->tran_id, 'source' => $source]);

            return ['outcome' => self::OUTCOME_INVALID, 'transaction' => $transaction];
        }

        $valId = trim((string) ($payload['val_id'] ?? ''));
        if ($valId === '') {
            return ['outcome' => self::OUTCOME_INVALID, 'transaction' => $transaction];
        }

        $validation = $this->validateWithGateway($valId, $transaction->mode);
        if ($validation === null) {
            return ['outcome' => self::OUTCOME_GATEWAY_ERROR, 'transaction' => $transaction];
        }

        $mismatch = $this->validationMismatch($validation, $transaction);
        if ($mismatch !== null) {
            Log::warning('SSLCOMMERZ validation rejected', ['tran_id' => $transaction->tran_id, 'reason' => $mismatch, 'source' => $source]);
            $this->markUnsuccessful(['tran_id' => $transaction->tran_id, 'error' => $mismatch], SslcommerzTransaction::STATUS_FAILED, $source);

            return ['outcome' => self::OUTCOME_INVALID, 'transaction' => $transaction->fresh()];
        }

        return $this->settle($transaction, $validation, $source);
    }

    /**
     * Record a failed / cancelled / expired attempt. Never downgrades a validated transaction.
     *
     * @param  array<string, mixed>  $payload
     */
    public function markUnsuccessful(array $payload, string $status, string $source): ?SslcommerzTransaction
    {
        $tranId = (string) ($payload['tran_id'] ?? '');
        if ($tranId === '') {
            return null;
        }

        return DB::transaction(function () use ($tranId, $payload, $status, $source): ?SslcommerzTransaction {
            $transaction = SslcommerzTransaction::query()->where('tran_id', $tranId)->lockForUpdate()->first();
            if (! $transaction) {
                return null;
            }
            if ($transaction->isFinal() || $transaction->status === $status) {
                return $transaction;
            }

            $transaction->update([
                'status' => $status,
                'failure_reason' => Str::limit(strip_tags((string) ($payload['error'] ?? $payload['failedreason'] ?? ucfirst($status))), 240),
            ]);

            $order = Order::query()->whereKey($transaction->order_id)->lockForUpdate()->first();
            if ($order && ! in_array($order->payment_status, [...self::PAID_PAYMENT_STATUSES, 'payment_verification'], true)) {
                $order->payment_status = 'failed';
                $order->save();

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'note' => "SSLCOMMERZ payment {$status} (tran {$transaction->tran_id}, via {$source})",
                    'created_by_user_id' => null,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * IPN signature check documented by SSLCOMMERZ (md5 over the verify_key fields).
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload): bool
    {
        $password = $this->settings()->storePassword();
        if (! filled($password) || empty($payload['verify_sign']) || empty($payload['verify_key'])) {
            return false;
        }

        $fields = [];
        foreach (explode(',', (string) $payload['verify_key']) as $key) {
            $fields[$key] = (string) ($payload[$key] ?? '');
        }
        $fields['store_passwd'] = md5($password);
        ksort($fields);

        $hashString = implode('&', array_map(fn ($key, $value) => $key.'='.$value, array_keys($fields), $fields));

        return hash_equals(md5($hashString), (string) $payload['verify_sign']);
    }

    /**
     * Non-charging connectivity / credential check using the Transaction Query API.
     *
     * @return array{ok: bool, message: string, credentialsRejected?: bool}
     */
    public function testConnection(): array
    {
        $settings = $this->settings();
        if (! $settings->hasCredentials()) {
            return ['ok' => false, 'message' => 'Store ID and Store Password are required before testing.', 'credentialsRejected' => true];
        }

        try {
            $response = $this->http()->get($settings->baseUrl().'/validator/api/merchantTransIDvalidationAPI.php', [
                'tran_id' => 'MBZ-CONNECTION-TEST',
                'store_id' => $settings->storeId(),
                'store_passwd' => $settings->storePassword(),
                'format' => 'json',
                'v' => 1,
            ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Could not connect to SSLCOMMERZ ('.$settings->mode().'). Check the server\'s outbound network access and try again.'];
        } catch (Throwable $e) {
            Log::warning('SSLCOMMERZ connection test error', ['error' => class_basename($e)]);

            return ['ok' => false, 'message' => 'The connection test failed unexpectedly.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'SSLCOMMERZ responded with HTTP '.$response->status().'.'];
        }

        $apiConnect = strtoupper((string) ($response->json('APIConnect') ?? ''));

        return match ($apiConnect) {
            'DONE' => ['ok' => true, 'message' => 'Credentials accepted by SSLCOMMERZ '.($settings->sandbox ? 'Sandbox' : 'Live').'. No payment was made.'],
            'INVALID_REQUEST' => ['ok' => false, 'message' => 'SSLCOMMERZ rejected the request. Check the Store ID and Store Password.', 'credentialsRejected' => true],
            'INACTIVE' => ['ok' => false, 'message' => 'This SSLCOMMERZ store is inactive for '.$settings->mode().' mode.', 'credentialsRejected' => true],
            'FAILED' => ['ok' => false, 'message' => 'SSLCOMMERZ could not authenticate this store. Check the credentials and mode.', 'credentialsRejected' => true],
            default => ['ok' => false, 'message' => 'Unexpected response from SSLCOMMERZ.'],
        };
    }

    /**
     * @return array<string, mixed>|null null when the gateway could not be reached
     */
    private function validateWithGateway(string $valId, string $mode): ?array
    {
        $settings = $this->settings();
        if (! $settings->hasCredentials()) {
            return null;
        }

        try {
            $response = $this->http()->get($settings->baseUrl($mode).'/validator/api/validationserverAPI.php', [
                'val_id' => $valId,
                'store_id' => $settings->storeId(),
                'store_passwd' => $settings->storePassword(),
                'v' => 1,
                'format' => 'json',
            ]);
        } catch (Throwable $e) {
            Log::warning('SSLCOMMERZ validation API unreachable', ['error' => class_basename($e)]);

            return null;
        }

        if (! $response->successful() || ! is_array($response->json())) {
            return null;
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $validation
     */
    private function validationMismatch(array $validation, SslcommerzTransaction $transaction): ?string
    {
        $status = strtoupper((string) ($validation['status'] ?? ''));
        if (! in_array($status, ['VALID', 'VALIDATED'], true)) {
            return 'Gateway status '.($status !== '' ? $status : 'missing');
        }
        if ((string) ($validation['tran_id'] ?? '') !== $transaction->tran_id) {
            return 'Transaction ID mismatch';
        }

        $currency = strtoupper((string) ($validation['currency_type'] ?? $validation['currency'] ?? ''));
        if ($currency !== strtoupper($transaction->currency)) {
            return 'Currency mismatch';
        }

        $paidAmount = (float) ($validation['currency_amount'] ?? $validation['amount'] ?? 0);
        if (abs($paidAmount - (float) $transaction->amount) > 0.01) {
            return 'Amount mismatch';
        }

        $order = $transaction->order;
        if (! $order || abs((float) $order->total_price - (float) $transaction->amount) > 0.01) {
            return 'Order total changed after payment started';
        }
        if (isset($validation['value_a']) && (string) $validation['value_a'] !== '' && (string) $validation['value_a'] !== (string) $order->order_id) {
            return 'Order reference mismatch';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validation
     * @return array{outcome: string, transaction: SslcommerzTransaction}
     */
    private function settle(SslcommerzTransaction $transaction, array $validation, string $source): array
    {
        $result = DB::transaction(function () use ($transaction, $validation, $source): array {
            $locked = SslcommerzTransaction::query()->whereKey($transaction->id)->lockForUpdate()->first();
            if ($locked->isFinal()) {
                return ['outcome' => self::OUTCOME_ALREADY_PROCESSED, 'transaction' => $locked];
            }

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();
            $riskLevel = (int) ($validation['risk_level'] ?? 0);
            $gatewayFields = [
                'val_id' => Str::limit((string) ($validation['val_id'] ?? ''), 100, '') ?: null,
                'bank_tran_id' => Str::limit((string) ($validation['bank_tran_id'] ?? ''), 100, '') ?: null,
                'card_type' => Str::limit((string) ($validation['card_type'] ?? ''), 60, '') ?: null,
                'validated_amount' => (float) ($validation['currency_amount'] ?? $validation['amount'] ?? 0),
                'store_amount' => isset($validation['store_amount']) ? (float) $validation['store_amount'] : null,
                'risk_level' => $riskLevel,
                'gateway_payload' => $this->safeGatewayPayload($validation),
                'validated_at' => now(),
            ];

            $alreadyPaid = SslcommerzTransaction::query()
                ->where('order_id', $locked->order_id)
                ->where('id', '!=', $locked->id)
                ->whereIn('status', [SslcommerzTransaction::STATUS_VALIDATED, SslcommerzTransaction::STATUS_HELD])
                ->exists();

            if ($alreadyPaid) {
                $locked->update($gatewayFields + ['status' => SslcommerzTransaction::STATUS_DUPLICATE, 'failure_reason' => 'Order already paid by another transaction — refund required']);
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'note' => "SSLCOMMERZ duplicate payment {$locked->tran_id} received — refund required",
                    'created_by_user_id' => null,
                ]);

                return ['outcome' => self::OUTCOME_DUPLICATE, 'transaction' => $locked];
            }

            $held = $riskLevel === 1;
            $locked->update($gatewayFields + ['status' => $held ? SslcommerzTransaction::STATUS_HELD : SslcommerzTransaction::STATUS_VALIDATED, 'failure_reason' => null]);

            $order->payment_status = $held ? 'payment_verification' : 'verified';
            if (in_array($order->status, ['payment_pending', 'payment_verification'], true)) {
                $order->status = $held ? 'payment_verification' : 'pending';
            }
            $order->transaction_id = $locked->bank_tran_id ?: $locked->tran_id;
            $order->payment_date = now();
            $order->amount_sent = $locked->validated_amount;
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => $held
                    ? "SSLCOMMERZ payment {$locked->tran_id} validated but flagged as risky — manual review required"
                    : "Paid via SSLCOMMERZ ({$locked->card_type}) — tran {$locked->tran_id}, validated via {$source}",
                'created_by_user_id' => null,
            ]);

            return ['outcome' => $held ? self::OUTCOME_HELD : self::OUTCOME_PAID, 'transaction' => $locked];
        });

        if (in_array($result['outcome'], [self::OUTCOME_PAID, self::OUTCOME_HELD], true)) {
            $order = $result['transaction']->order;
            ActivityLoggerService::logOrder(
                $result['outcome'] === self::OUTCOME_PAID ? 'order.payment_verified' : 'order.payment_held',
                $order,
                "Order #{$order->order_id} SSLCOMMERZ payment ".($result['outcome'] === self::OUTCOME_PAID ? 'validated' : 'held for risk review'),
                ['source' => 'sslcommerz', 'newValues' => ['payment_status' => $order->payment_status]]
            );
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(Order $order, SslcommerzTransaction $transaction, SslcommerzSettings $settings): array
    {
        $itemCount = (int) $order->items()->sum('quantity');

        return [
            'store_id' => $settings->storeId(),
            'store_passwd' => $settings->storePassword(),
            'total_amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'currency' => $transaction->currency,
            'tran_id' => $transaction->tran_id,
            'success_url' => route('payment.sslcommerz.success'),
            'fail_url' => route('payment.sslcommerz.fail'),
            'cancel_url' => route('payment.sslcommerz.cancel'),
            'ipn_url' => route('payment.sslcommerz.ipn'),
            'cus_name' => Str::limit((string) ($order->customer_name ?: 'Customer'), 50, ''),
            'cus_email' => filter_var($order->email, FILTER_VALIDATE_EMAIL) ? $order->email : 'customer@mama-bazar.com',
            'cus_add1' => Str::limit((string) ($order->address ?: 'N/A'), 50, ''),
            'cus_city' => Str::limit((string) ($order->district ?: 'Dhaka'), 50, ''),
            'cus_postcode' => Str::limit((string) ($order->postal_code ?: '1000'), 30, ''),
            'cus_country' => 'Bangladesh',
            'cus_phone' => Str::limit((string) ($order->phone ?: '01700000000'), 20, ''),
            'shipping_method' => 'NO',
            'num_of_item' => max(1, $itemCount),
            'product_name' => 'MamaBazar order '.$order->order_id,
            'product_category' => 'Ecommerce',
            'product_profile' => 'general',
            'value_a' => $order->order_id,
        ];
    }

    private function generateTranId(Order $order): string
    {
        do {
            $tranId = 'MBZ'.$order->id.'T'.now()->format('ymdHis').Str::upper(Str::random(4));
        } while (SslcommerzTransaction::query()->where('tran_id', $tranId)->exists());

        return $tranId;
    }

    private function isTrustedGatewayUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        return ($parts['scheme'] ?? '') === 'https'
            && ($host === 'sslcommerz.com' || str_ends_with($host, '.sslcommerz.com'));
    }

    /**
     * @param  array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    private function safeGatewayPayload(array $validation): array
    {
        return array_intersect_key($validation, array_flip([
            'status', 'tran_date', 'tran_id', 'val_id', 'amount', 'store_amount', 'currency', 'currency_type',
            'currency_amount', 'bank_tran_id', 'card_type', 'card_issuer', 'card_brand', 'card_issuer_country',
            'risk_level', 'risk_title', 'value_a',
        ]));
    }

    private function markInitiationFailed(SslcommerzTransaction $transaction, string $reason): void
    {
        $transaction->update(['status' => SslcommerzTransaction::STATUS_FAILED, 'failure_reason' => Str::limit($reason, 240)]);
    }

    /**
     * @return array{success: bool, redirectUrl: ?string, message: ?string, transaction: ?SslcommerzTransaction}
     */
    private function initiationFailure(string $message, ?SslcommerzTransaction $transaction = null): array
    {
        return ['success' => false, 'redirectUrl' => null, 'message' => $message, 'transaction' => $transaction];
    }

    private function http(): PendingRequest
    {
        return Http::timeout(max(5, (int) config('services.sslcommerz.timeout', 30)))
            ->connectTimeout(10)
            ->acceptJson();
    }
}
