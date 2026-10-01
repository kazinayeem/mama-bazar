<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceGuestCheckoutVerifyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\PaymentMethod::ensureDefaults();
    }

    private function makeOrder(array $over = []): Order
    {
        $ship = ShippingMethod::firstOrCreate(['name' => 'Test Delivery'], ['charge' => 60, 'status' => 'active', 'cod_available' => true]);
        $cat = Category::firstOrCreate(['slug' => 't'], ['name' => 'T', 'status' => 'active']);
        $p = Product::firstOrCreate(['slug' => 'tp'], ['title' => 'Test Product', 'price' => 500, 'category_id' => $cat->id, 'status' => 'active', 'stock' => 50, 'sku' => 'MB-101']);

        $result = OrderService::createOrder(array_merge([
            'customer_name' => 'Guest User',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'address' => 'House 1, Road 2, Dhaka',
            'shipping_method_id' => $ship->id,
            'payment_method' => 'cod',
            'items' => [['product_id' => $p->id, 'quantity' => 2]],
            '_user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/120.0 Mobile Safari/537.36',
            '_client_ip' => '103.4.5.6',
            'utm_source' => 'facebook',
            'utm_campaign' => 'summer-sale',
        ], $over));

        return Order::where('order_id', $result['order']['orderId'])->firstOrFail();
    }

    public function test_guest_order_created_with_invoice_analytics(): void
    {
        $order = $this->makeOrder();

        $this->assertNull($order->user_id);
        $this->assertNotNull($order->invoice_number);
        $this->assertNotNull($order->access_token);
        $this->assertEquals('Chrome', $order->browser);
        $this->assertEquals('Android', $order->os_platform);
        $this->assertEquals('Mobile', $order->device_type);
        $this->assertEquals('103.4.5.0', $order->ip_truncated); // truncated, not raw
        $this->assertNotNull($order->ip_hash);
        $this->assertEquals('facebook', $order->utm_source);
        $this->assertEquals('Test Product', $order->items->first()->product_title);
        $this->assertEquals('MB-101', $order->items->first()->product_sku);
    }

    public function test_idempotency_prevents_duplicates(): void
    {
        $o1 = $this->makeOrder(['idempotency_key' => 'key-123']);
        $count1 = Order::count();
        $o2 = $this->makeOrder(['idempotency_key' => 'key-123']);
        $this->assertEquals($count1, Order::count());
        $this->assertEquals($o1->id, $o2->id);
    }

    public function test_tracking_requires_phone(): void
    {
        $order = $this->makeOrder();
        $this->assertNull(OrderService::trackOrder($order->order_id, null));
        $this->assertNull(OrderService::trackOrder($order->order_id, '01999999999'));
        $this->assertNotNull(OrderService::trackOrder($order->order_id, '01712345678'));
        $this->assertNotNull(OrderService::trackOrder($order->order_id, null, $order->access_token));
    }

    public function test_admin_invoice_pages_require_auth(): void
    {
        $order = $this->makeOrder();
        $this->get("/admin/orders/{$order->id}/invoice")->assertRedirect();
        $this->get("/admin/orders/{$order->id}/invoice/download")->assertRedirect();
        $this->get("/admin/orders/{$order->id}/packing-slip")->assertRedirect();
    }

    public function test_admin_can_download_pdf(): void
    {
        $order = $this->makeOrder();
        $admin = User::create(['name' => 'Admin', 'phone' => '01000000001', 'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active']);
        $res = $this->actingAs($admin)->get("/admin/orders/{$order->id}/invoice/download");
        $res->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
    }
}
