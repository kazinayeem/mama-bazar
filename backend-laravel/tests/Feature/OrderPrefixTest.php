<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPrefixTest extends TestCase
{
    use RefreshDatabase;

    private function orderPayload(array $over = []): array
    {
        PaymentMethod::ensureDefaults();
        $ship = ShippingMethod::firstOrCreate(['name' => 'Test Delivery'], ['charge' => 60, 'status' => 'active', 'cod_available' => true]);
        $cat = Category::firstOrCreate(['slug' => 't'], ['name' => 'T', 'status' => 'active']);
        $p = Product::firstOrCreate(['slug' => 'tp'], ['title' => 'Test Product', 'price' => 500, 'category_id' => $cat->id, 'status' => 'active', 'stock' => 100]);

        return array_merge([
            'customer_name' => 'Prefix User',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'address' => 'House 1, Road 2, Dhaka',
            'shipping_method_id' => $ship->id,
            'payment_method' => 'cod',
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ], $over);
    }

    public function test_new_orders_use_bs_prefix(): void
    {
        $result = OrderService::createOrder($this->orderPayload());
        $orderId = $result['order']['orderId'];
        $this->assertStringStartsWith('BS-', $orderId);
        $this->assertMatchesRegularExpression('/^BS-[A-Z0-9]{6}$/', $orderId);
    }

    public function test_generated_order_ids_are_unique(): void
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $r = OrderService::createOrder($this->orderPayload(['idempotency_key' => "pfx-$i"]));
            $ids[] = $r['order']['orderId'];
        }
        $this->assertCount(10, array_unique($ids));
        foreach ($ids as $id) {
            $this->assertStringStartsWith('BS-', $id);
        }
    }

    public function test_legacy_ghb_orders_still_trackable(): void
    {
        $legacy = Order::create([
            'order_id' => 'GHB-LEGACY',
            'customer_name' => 'Old Customer',
            'phone' => '01899998888',
            'address' => 'Old address',
            'shipping_cost' => 60,
            'subtotal' => 500,
            'total_price' => 560,
            'payment_method' => 'cod',
        ]);

        // Lowercase + legacy prefix both work.
        $this->assertNotNull(OrderService::trackOrder('ghb-legacy', '01899998888'));
        $this->assertNotNull(OrderService::trackOrder('GHB-LEGACY', '01899998888'));

        // New BS order trackable too.
        $new = OrderService::createOrder($this->orderPayload(['idempotency_key' => 'pfx-track']));
        $this->assertNotNull(OrderService::trackOrder($new['order']['orderId'], '01712345678'));
    }

    public function test_admin_search_finds_both_prefixes(): void
    {
        $admin = User::create(['name' => 'Admin', 'phone' => '01000000009', 'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active']);
        Order::create([
            'order_id' => 'GHB-OLD123', 'customer_name' => 'Old', 'phone' => '01899998888',
            'address' => 'Addr', 'shipping_cost' => 60, 'subtotal' => 100, 'total_price' => 160, 'payment_method' => 'cod',
        ]);
        $new = OrderService::createOrder($this->orderPayload(['idempotency_key' => 'pfx-search']));

        $this->actingAs($admin)->get('/admin/orders?search=GHB-OLD')->assertSee('GHB-OLD123');
        $this->actingAs($admin)->get('/admin/orders?search='.substr($new['order']['orderId'], 0, 8))->assertSee($new['order']['orderId']);
    }
}
