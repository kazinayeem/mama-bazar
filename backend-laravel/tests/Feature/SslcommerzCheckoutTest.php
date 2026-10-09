<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\SslcommerzTransaction;
use App\Support\SslcommerzSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SslcommerzCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_PASSWORD = 'sandbox-secret-pass';

    private const GATEWAY_URL = 'https://sandbox.sslcommerz.com/EasyCheckOut/testcde1234';

    private Product $product;

    private ShippingMethod $shipping;

    /** @var array<string, mixed> */
    private array $validationResponse = [];

    protected function setUp(): void
    {
        parent::setUp();

        PaymentMethod::ensureDefaults();

        $this->product = Product::create([
            'title' => 'Steel Pressure Cooker',
            'slug' => 'steel-pressure-cooker',
            'price' => 1200,
            'stock' => 50,
            'status' => 'active',
        ]);

        $this->shipping = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'charge' => 60,
            'estimated_delivery' => '24-48h',
            'applicable_areas' => 'inside_dhaka,outside_dhaka',
            'priority' => 1,
            'status' => 'active',
        ]);

        Http::fake([
            'sandbox.sslcommerz.com/gwprocess/v4/api.php' => Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => self::GATEWAY_URL,
                'sessionkey' => 'SESSIONKEY123',
            ]),
            'sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => fn () => Http::response($this->validationResponse),
        ]);
    }

    private function configureGateway(): void
    {
        SslcommerzSettings::persist(['store_id' => 'mamab0test', 'store_password' => self::STORE_PASSWORD, 'sandbox' => true, 'currency' => 'BDT']);
        PaymentMethod::where('code', 'sslcommerz')->update(['enabled' => true, 'maintenance_mode' => false]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(string $paymentMethod = 'sslcommerz', array $overrides = []): array
    {
        return array_merge([
            'order_key' => 'ok_'.uniqid(),
            'customer_name' => 'Rahim Uddin',
            'phone' => '01712345678',
            'email' => 'rahim@example.com',
            'district' => 'Dhaka',
            'address' => 'House 12, Road 5, Dhanmondi',
            'shipping_method_id' => $this->shipping->id,
            'payment_method' => $paymentMethod,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ], $overrides);
    }

    /**
     * @return array{0: Order, 1: SslcommerzTransaction}
     */
    private function placeGatewayOrder(): array
    {
        $this->configureGateway();
        $this->post(route('checkout.process'), $this->checkoutPayload())->assertRedirect(self::GATEWAY_URL);

        $transaction = SslcommerzTransaction::latest('id')->firstOrFail();

        return [$transaction->order, $transaction];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function gatewayValidation(Order $order, SslcommerzTransaction $transaction, array $overrides = []): array
    {
        return array_merge([
            'status' => 'VALID',
            'tran_id' => $transaction->tran_id,
            'val_id' => 'VAL'.$transaction->id,
            'amount' => '1260.00',
            'store_amount' => '1229.13',
            'currency' => 'BDT',
            'currency_type' => 'BDT',
            'currency_amount' => '1260.00',
            'bank_tran_id' => 'BANK'.$transaction->id,
            'card_type' => 'VISA-Dutch Bangla',
            'risk_level' => '0',
            'value_a' => $order->order_id,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function signed(array $payload): array
    {
        $keys = array_keys($payload);
        $fields = $payload;
        $fields['store_passwd'] = md5(self::STORE_PASSWORD);
        ksort($fields);
        $hash = md5(implode('&', array_map(fn ($k, $v) => $k.'='.$v, array_keys($fields), $fields)));

        return $payload + ['verify_key' => implode(',', $keys), 'verify_sign' => $hash];
    }

    private function validationRequestCount(): int
    {
        return Http::recorded(fn (HttpRequest $request) => str_contains($request->url(), 'validationserverAPI'))->count();
    }

    public function test_checkout_redirects_to_sslcommerz_and_keeps_order_unpaid(): void
    {
        $this->configureGateway();

        $this->post(route('checkout.process'), $this->checkoutPayload('sslcommerz', [
            'transaction_id' => 'FAKE-TRX',
            'sender_number' => '01700000000',
        ]))->assertRedirect(self::GATEWAY_URL);

        $order = Order::firstOrFail();
        $this->assertSame('payment_pending', $order->payment_status);
        $this->assertSame('payment_pending', $order->status);
        $this->assertNull($order->transaction_id);

        $transaction = SslcommerzTransaction::firstOrFail();
        $this->assertSame(SslcommerzTransaction::STATUS_INITIATED, $transaction->status);
        $this->assertSame('1260.00', (string) $transaction->amount);
        $this->assertSame('SESSIONKEY123', $transaction->session_key);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'gwprocess/v4/api.php')
            && $request['total_amount'] === '1260.00'
            && $request['currency'] === 'BDT'
            && $request['tran_id'] === $transaction->tran_id
            && $request['success_url'] === route('payment.sslcommerz.success')
            && $request['ipn_url'] === route('payment.sslcommerz.ipn')
            && $request['value_a'] === $order->order_id);
    }

    public function test_success_callback_marks_order_paid_only_after_validation_api_confirms(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();
        $this->validationResponse = $this->gatewayValidation($order, $transaction);

        $this->post(route('payment.sslcommerz.success'), ['tran_id' => $transaction->tran_id, 'val_id' => 'VAL'.$transaction->id, 'status' => 'VALID'])
            ->assertRedirect(route('order.success', ['orderId' => $order->order_id, 'token' => $order->access_token]))
            ->assertCookieMissing(config('session.cookie'));

        $order->refresh();
        $this->assertSame('verified', $order->payment_status);
        $this->assertSame('pending', $order->status);
        $this->assertSame('BANK'.$transaction->id, $order->transaction_id);
        $this->assertEquals(1260.0, (float) $order->amount_sent);
        $this->assertSame(SslcommerzTransaction::STATUS_VALIDATED, $transaction->fresh()->status);
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'validationserverAPI.php')
            && $request['val_id'] === 'VAL'.$transaction->id);
    }

    public function test_repeated_success_and_ipn_callbacks_are_idempotent(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();
        $this->validationResponse = $this->gatewayValidation($order, $transaction);
        $callback = ['tran_id' => $transaction->tran_id, 'val_id' => 'VAL'.$transaction->id, 'status' => 'VALID'];

        $this->post(route('payment.sslcommerz.success'), $callback)->assertRedirect();
        $this->post(route('payment.sslcommerz.ipn'), $this->signed($callback))->assertOk();
        $this->post(route('payment.sslcommerz.success'), $callback)
            ->assertRedirect(route('order.success', ['orderId' => $order->order_id, 'token' => $order->access_token]));

        $this->assertSame(1, $this->validationRequestCount());
        $this->assertSame(1, OrderStatusHistory::where('order_id', $order->id)->where('note', 'like', 'Paid via SSLCOMMERZ%')->count());
        $this->assertSame('verified', $order->fresh()->payment_status);
    }

    public function test_browser_success_redirect_is_not_trusted_when_gateway_validation_fails(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();
        $this->validationResponse = ['status' => 'INVALID_TRANSACTION'];

        $this->post(route('payment.sslcommerz.success'), ['tran_id' => $transaction->tran_id, 'val_id' => 'FORGED', 'status' => 'VALID'])
            ->assertRedirect(route('payment.sslcommerz.status', ['orderId' => $order->order_id, 'token' => $order->access_token]));

        $this->assertNotContains($order->fresh()->payment_status, ['verified', 'success']);
        $this->assertSame(SslcommerzTransaction::STATUS_FAILED, $transaction->fresh()->status);
    }

    public function test_amount_or_currency_mismatch_does_not_mark_order_paid(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();

        $this->validationResponse = $this->gatewayValidation($order, $transaction, ['amount' => '10.00', 'currency_amount' => '10.00']);
        $this->post(route('payment.sslcommerz.success'), ['tran_id' => $transaction->tran_id, 'val_id' => 'VAL1']);
        $this->assertNotContains($order->fresh()->payment_status, ['verified', 'success']);

        $this->validationResponse = $this->gatewayValidation($order, $transaction, ['currency_type' => 'USD']);
        $this->post(route('payment.sslcommerz.success'), ['tran_id' => $transaction->tran_id, 'val_id' => 'VAL2']);
        $this->assertNotContains($order->fresh()->payment_status, ['verified', 'success']);
        $this->assertSame(SslcommerzTransaction::STATUS_FAILED, $transaction->fresh()->status);
    }

    public function test_failed_and_cancelled_payments_keep_order_unpaid_and_offer_retry(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();

        $this->post(route('payment.sslcommerz.fail'), ['tran_id' => $transaction->tran_id, 'status' => 'FAILED', 'error' => 'Card declined'])
            ->assertRedirect(route('payment.sslcommerz.status', ['orderId' => $order->order_id, 'token' => $order->access_token]));

        $this->assertSame(SslcommerzTransaction::STATUS_FAILED, $transaction->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('payment_pending', $order->fresh()->status);

        $this->get(route('payment.sslcommerz.status', ['orderId' => $order->order_id, 'token' => $order->access_token]))
            ->assertOk()
            ->assertSee('Payment not completed')
            ->assertSee(route('payment.sslcommerz.retry'), false);

        $this->post(route('payment.sslcommerz.retry'), ['orderId' => $order->order_id, 'token' => $order->access_token])
            ->assertRedirect(self::GATEWAY_URL);
        $retry = SslcommerzTransaction::latest('id')->firstOrFail();
        $this->assertNotSame($transaction->tran_id, $retry->tran_id);

        $this->post(route('payment.sslcommerz.cancel'), ['tran_id' => $retry->tran_id, 'status' => 'CANCELLED'])->assertRedirect();
        $this->assertSame(SslcommerzTransaction::STATUS_CANCELLED, $retry->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
    }

    public function test_late_failure_callback_cannot_downgrade_a_validated_payment(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();
        $this->validationResponse = $this->gatewayValidation($order, $transaction);
        $this->post(route('payment.sslcommerz.success'), ['tran_id' => $transaction->tran_id, 'val_id' => 'VAL1']);

        $this->post(route('payment.sslcommerz.fail'), ['tran_id' => $transaction->tran_id, 'status' => 'FAILED']);
        $this->post(route('payment.sslcommerz.cancel'), ['tran_id' => $transaction->tran_id, 'status' => 'CANCELLED']);

        $this->assertSame(SslcommerzTransaction::STATUS_VALIDATED, $transaction->fresh()->status);
        $this->assertSame('verified', $order->fresh()->payment_status);
    }

    public function test_second_validated_payment_for_a_paid_order_is_flagged_as_duplicate(): void
    {
        [$order, $first] = $this->placeGatewayOrder();
        $second = SslcommerzTransaction::factory()->create(['order_id' => $order->id, 'amount' => 1260]);

        $this->validationResponse = $this->gatewayValidation($order, $first);
        $this->post(route('payment.sslcommerz.success'), ['tran_id' => $first->tran_id, 'val_id' => 'VAL1']);
        $this->validationResponse = $this->gatewayValidation($order, $second);
        $this->post(route('payment.sslcommerz.ipn'), $this->signed(['tran_id' => $second->tran_id, 'val_id' => 'VAL2', 'status' => 'VALID']))->assertOk();

        $this->assertSame(SslcommerzTransaction::STATUS_VALIDATED, $first->fresh()->status);
        $this->assertSame(SslcommerzTransaction::STATUS_DUPLICATE, $second->fresh()->status);
        $this->assertSame('BANK'.$first->id, $order->fresh()->transaction_id);
    }

    public function test_risky_payment_is_held_for_manual_review(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();
        $this->validationResponse = $this->gatewayValidation($order, $transaction, ['risk_level' => '1']);

        $this->post(route('payment.sslcommerz.ipn'), $this->signed(['tran_id' => $transaction->tran_id, 'val_id' => 'VAL1', 'status' => 'VALID']))->assertOk();

        $this->assertSame('payment_verification', $order->fresh()->payment_status);
        $this->assertSame(SslcommerzTransaction::STATUS_HELD, $transaction->fresh()->status);
    }

    public function test_ipn_rejects_failure_notifications_with_invalid_signature(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();

        $this->post(route('payment.sslcommerz.ipn'), ['tran_id' => $transaction->tran_id, 'status' => 'FAILED', 'verify_key' => 'status,tran_id', 'verify_sign' => 'forged'])
            ->assertForbidden();

        $this->assertSame(SslcommerzTransaction::STATUS_INITIATED, $transaction->fresh()->status);
        $this->assertSame('payment_pending', $order->fresh()->payment_status);
    }

    public function test_unreachable_validation_api_leaves_order_pending_until_ipn_confirms(): void
    {
        [$order, $transaction] = $this->placeGatewayOrder();

        Http::fake(['sandbox.sslcommerz.com/validator/*' => Http::failedConnection()]);
        $this->post(route('payment.sslcommerz.success'), ['tran_id' => $transaction->tran_id, 'val_id' => 'VAL1'])
            ->assertRedirect(route('payment.sslcommerz.status', ['orderId' => $order->order_id, 'token' => $order->access_token]));
        $this->assertSame('payment_pending', $order->fresh()->payment_status);
        $this->assertSame(SslcommerzTransaction::STATUS_INITIATED, $transaction->fresh()->status);
    }

    public function test_status_and_retry_pages_require_the_order_access_token(): void
    {
        [$order] = $this->placeGatewayOrder();

        $this->get(route('payment.sslcommerz.status', ['orderId' => $order->order_id, 'token' => 'wrong']))->assertNotFound();
        $this->post(route('payment.sslcommerz.retry'), ['orderId' => $order->order_id, 'token' => 'wrong'])->assertNotFound();
    }

    public function test_gateway_stays_hidden_when_its_migration_has_not_run(): void
    {
        $this->configureGateway();
        Schema::drop('sslcommerz_transactions');

        $this->assertFalse(SslcommerzSettings::load()->isCheckoutReady());
        $this->post(route('checkout.process'), $this->checkoutPayload())
            ->assertSessionHas('error', fn (string $message) => ! str_contains($message, 'SQLSTATE'));

        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_unconfigured_gateway_is_hidden_and_cash_on_delivery_still_works(): void
    {
        PaymentMethod::where('code', 'sslcommerz')->update(['enabled' => true, 'maintenance_mode' => false]);

        $this->get(route('checkout'))->assertOk()
            ->assertViewHas('paymentMethods', fn ($methods) => ! $methods->contains('code', 'sslcommerz') && $methods->contains('code', 'cod'));

        $this->post(route('checkout.process'), $this->checkoutPayload('sslcommerz'))
            ->assertSessionHas('error');
        $this->assertSame(0, Order::count());

        $this->post(route('checkout.process'), $this->checkoutPayload('cod'))->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('success', $order->payment_status);
        $this->assertSame(0, SslcommerzTransaction::count());
        Http::assertNothingSent();
    }
}
