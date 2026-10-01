<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function seedCheckoutBasics(): array
    {
        $product = Product::create([
            'title' => 'Checkout Test Product',
            'slug' => 'checkout-test-product',
            'price' => 500,
            'stock' => 50,
            'status' => 'active',
        ]);
        $shipInside = ShippingMethod::create([
            'name' => 'Standard (Inside Dhaka)',
            'charge' => 60,
            'estimated_delivery' => '24-48h',
            'applicable_areas' => 'inside_dhaka',
            'priority' => 10,
            'status' => 'active',
        ]);
        ShippingMethod::create([
            'name' => 'Outside Dhaka Courier',
            'charge' => 120,
            'estimated_delivery' => '48-72h',
            'applicable_areas' => 'outside_dhaka',
            'priority' => 20,
            'status' => 'active',
        ]);
        PaymentMethod::create([
            'code' => 'cod', 'name' => 'Cash on Delivery', 'type' => 'cod',
            'enabled' => true, 'sort_order' => 1, 'maintenance_mode' => false, 'config' => [],
        ]);
        $coupon = Coupon::create([
            'code' => 'SAVE50', 'discount_type' => 'fixed', 'discount_value' => 50,
            'min_order_amount' => 100, 'status' => 'active',
        ]);
        return compact('product', 'shipInside', 'coupon');
    }

    public function test_checkout_page_loads(): void
    {
        $this->seedCheckoutBasics();
        $res = $this->get('/checkout');
        $res->assertStatus(200);
        $res->assertSee('Delivery Information');
        $res->assertSee('Shipping Method');
        $res->assertSee('Payment Method');
        $res->assertSee('Your Order');
    }

    public function test_coupon_endpoint_validates(): void
    {
        ['coupon' => $coupon] = $this->seedCheckoutBasics();
        $res = $this->postJson('/checkout/validate-coupon', ['code' => 'save50', 'subtotal' => 500]);
        $res->assertOk()->assertJson(['success' => true, 'code' => 'SAVE50', 'discount' => 50]);

        $bad = $this->postJson('/checkout/validate-coupon', ['code' => 'NOPE', 'subtotal' => 500]);
        $bad->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_order_recalculates_shipping_and_rejects_tampering(): void
    {
        ['product' => $p, 'shipInside' => $ship] = $this->seedCheckoutBasics();

        $payload = [
            'customer_name' => 'Test User',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'address' => 'House 1, Road 2, Dhaka',
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cod',
            'shipping_method_id' => $ship->id,
            'shipping_cost' => 1, // tampered — must be ignored
        ];
        $res = $this->post('/checkout', $payload);
        $this->assertStringContainsString('/order/success', $res->headers->get('Location'));
        $this->assertDatabaseHas('orders', [
            'phone' => '01712345678',
            'shipping_cost' => 60, // recalculated, not 1
            'subtotal' => 500,
        ]);
    }

    public function test_order_rejects_wrong_area_and_bad_phone(): void
    {
        ['product' => $p, 'shipInside' => $ship] = $this->seedCheckoutBasics();

        $base = [
            'customer_name' => 'Test User',
            'phone' => '01712345678',
            'district' => 'Chattogram',
            'address' => 'Some address here',
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cod',
            'shipping_method_id' => $ship->id,
        ];
        $this->post('/checkout', $base)->assertRedirect()->assertSessionHas('error');

        $badPhone = array_merge($base, ['district' => 'Dhaka', 'phone' => '12345']);
        $this->post('/checkout', $badPhone)->assertSessionHasErrors('phone');
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepaidPayload(string $paymentCode, string $senderNumber): array
    {
        ['product' => $p, 'shipInside' => $ship] = $this->seedCheckoutBasics();
        PaymentMethod::create([
            'code' => 'bkash', 'name' => 'bKash', 'type' => 'mobile_banking',
            'enabled' => true, 'sort_order' => 2, 'maintenance_mode' => false, 'config' => [],
        ]);
        PaymentMethod::create([
            'code' => 'bank', 'name' => 'Bank Transfer', 'type' => 'bank',
            'enabled' => true, 'sort_order' => 5, 'maintenance_mode' => false, 'config' => [],
        ]);

        return [
            'customer_name' => 'Prepaid User',
            'phone' => '01912345678',
            'district' => 'Dhaka',
            'address' => 'House 5, Road 7, Dhaka',
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => $paymentCode,
            'shipping_method_id' => $ship->id,
            'sender_number' => $senderNumber,
            'transaction_id' => 'TRX123ABC',
        ];
    }

    public function test_mobile_banking_sender_number_accepts_spaces_dashes_and_bangla_digits(): void
    {
        $this->post('/checkout', $this->prepaidPayload('bkash', '০১৭১২-৩৪৫ ৬৭৮'))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['phone' => '01912345678', 'sender_number' => '01712345678']);
    }

    public function test_mobile_banking_rejects_invalid_sender_number(): void
    {
        $this->post('/checkout', $this->prepaidPayload('bkash', '12345'))
            ->assertSessionHasErrors('sender_number');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_bank_transfer_accepts_account_number_as_sender(): void
    {
        $this->post('/checkout', $this->prepaidPayload('bank', '1234-5678-9012'))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['phone' => '01912345678', 'sender_number' => '123456789012']);
    }

    public function test_duplicate_order_key_does_not_double_create(): void
    {
        ['product' => $p, 'shipInside' => $ship] = $this->seedCheckoutBasics();
        $payload = [
            'customer_name' => 'Dup User',
            'phone' => '01812345678',
            'district' => 'Dhaka',
            'address' => 'Dup address 123',
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_method' => 'cod',
            'shipping_method_id' => $ship->id,
            'order_key' => 'fixed-test-key-123',
        ];
        $this->post('/checkout', $payload)->assertRedirect();
        $this->post('/checkout', $payload)->assertRedirect();
        $this->assertEquals(1, \App\Models\Order::where('phone', '01812345678')->count());
    }
}
