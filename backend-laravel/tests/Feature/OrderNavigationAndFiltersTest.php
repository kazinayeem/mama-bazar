<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNavigationAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected Product $product;

    protected ShippingMethod $shippingMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('role', 'admin')->first();

        PaymentMethod::ensureDefaults();
        $this->shippingMethod = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'charge' => 60,
            'status' => 'active',
            'cod_available' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Organic Foods',
            'slug' => 'organic-foods',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'title' => 'Pure Mustard Oil 1L',
            'slug' => 'pure-mustard-oil-1l',
            'price' => 350,
            'sale_price' => 320,
            'stock' => 50,
            'sku' => 'OIL-MST-01',
            'category_id' => $this->category->id,
            'status' => 'active',
            'product_status' => 'published',
        ]);
    }

    private function createOrder(array $attributes = []): Order
    {
        static $seq = 100;
        $seq++;

        $order = Order::create(array_merge([
            'order_id' => 'MB-ORD-'.$seq,
            'invoice_number' => 'INV-2026-000'.$seq,
            'customer_name' => 'Customer '.$seq,
            'phone' => '01710000'.$seq,
            'address' => 'Mirpur '.$seq.', Dhaka',
            'district' => 'Dhaka',
            'shipping_method_id' => $this->shippingMethod->id,
            'shipping_method_name' => $this->shippingMethod->name,
            'shipping_cost' => 60,
            'subtotal' => 320,
            'total_price' => 380,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'created_at' => now()->subMinutes(100 - $seq),
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'quantity' => 1,
            'price' => 320,
        ]);

        return $order;
    }

    public function test_admin_orders_list_renders_summary_cards_and_presets(): void
    {
        $this->createOrder(['status' => 'pending', 'payment_status' => 'pending', 'total_price' => 500]);
        $this->createOrder(['status' => 'delivered', 'payment_status' => 'success', 'total_price' => 1200]);
        $this->createOrder(['status' => 'cancelled', 'payment_status' => 'refunded', 'total_price' => 700]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Total Orders');
        $response->assertSee('Completed Revenue');
        $response->assertSee('All Orders');
        $response->assertSee('Ready to Ship');
        $response->assertSee('Delivered');
    }

    public function test_admin_orders_list_filters_by_status_and_payment_status(): void
    {
        $ord1 = $this->createOrder(['status' => 'confirmed', 'payment_status' => 'pending', 'customer_name' => 'Alice Confirmed']);
        $ord2 = $this->createOrder(['status' => 'delivered', 'payment_status' => 'success', 'customer_name' => 'Bob Delivered']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'status' => 'confirmed',
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('orders')->contains('id', $ord1->id));
        $this->assertFalse($response->viewData('orders')->contains('id', $ord2->id));
        $response->assertSee('Alice Confirmed');
        $response->assertDontSee('Bob Delivered');

        $responseSuccess = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'payment_status' => 'success',
        ]));

        $responseSuccess->assertStatus(200);
        $this->assertTrue($responseSuccess->viewData('orders')->contains('id', $ord2->id));
        $this->assertFalse($responseSuccess->viewData('orders')->contains('id', $ord1->id));
        $responseSuccess->assertSee('Bob Delivered');
        $responseSuccess->assertDontSee('Alice Confirmed');
    }

    public function test_admin_orders_list_filters_by_amount_range_and_customer_search(): void
    {
        $lowOrder = $this->createOrder(['status' => 'delivered', 'total_price' => 250, 'customer_name' => 'Rahim Small']);
        $highOrder = $this->createOrder(['status' => 'delivered', 'total_price' => 3500, 'customer_name' => 'Karim Big']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'min_amount' => 1000,
        ]));

        $response->assertStatus(200);
        $orders = $response->viewData('orders');
        $this->assertTrue($orders->contains('id', $highOrder->id));
        $this->assertFalse($orders->contains('id', $lowOrder->id));
        $response->assertSee('Karim Big');
        $response->assertDontSee('Rahim Small');

        $responseSearch = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'search' => 'Rahim',
        ]));

        $responseSearch->assertStatus(200);
        $searchOrders = $responseSearch->viewData('orders');
        $this->assertTrue($searchOrders->contains('id', $lowOrder->id));
        $this->assertFalse($searchOrders->contains('id', $highOrder->id));
        $responseSearch->assertSee('Rahim Small');
        $responseSearch->assertDontSee('Karim Big');
    }

    public function test_admin_orders_list_sorts_by_amount_deterministically(): void
    {
        $o1 = $this->createOrder(['total_price' => 100]);
        $o2 = $this->createOrder(['total_price' => 900]);
        $o3 = $this->createOrder(['total_price' => 450]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'sort' => 'highest_amount',
        ]));

        $response->assertStatus(200);
        $orders = $response->viewData('orders');
        $this->assertEquals($o2->id, $orders->first()->id);
        $this->assertEquals($o1->id, $orders->last()->id);
    }

    public function test_order_details_shows_previous_and_next_navigation(): void
    {
        $o1 = $this->createOrder(['created_at' => now()->subHours(3)]);
        $o2 = $this->createOrder(['created_at' => now()->subHours(2)]);
        $o3 = $this->createOrder(['created_at' => now()->subHours(1)]);

        // In default 'newest' sort: order is o3 (newest), o2, o1 (oldest)
        // Viewing middle order o2:
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $o2->id));

        $response->assertStatus(200);
        $nav = $response->viewData('navigation');

        $this->assertEquals(2, $nav['position']);
        $this->assertEquals(3, $nav['total']);
        $this->assertEquals($o3->id, $nav['previous']['id']);
        $this->assertEquals($o1->id, $nav['next']['id']);

        $response->assertSee('Order 2 of 3');
        $response->assertSee($nav['previous']['url']);
        $response->assertSee($nav['next']['url']);
    }

    public function test_order_details_navigation_boundary_disables_on_first_and_last(): void
    {
        $o1 = $this->createOrder(['created_at' => now()->subHours(2)]);
        $o2 = $this->createOrder(['created_at' => now()->subHours(1)]);

        // Viewing o2 (newest, position 1 of 2):
        $responseFirst = $this->actingAs($this->admin)->get(route('admin.orders.show', $o2->id));
        $navFirst = $responseFirst->viewData('navigation');

        $this->assertEquals(1, $navFirst['position']);
        $this->assertNull($navFirst['previous']);
        $this->assertEquals($o1->id, $navFirst['next']['id']);

        // Viewing o1 (oldest, position 2 of 2):
        $responseLast = $this->actingAs($this->admin)->get(route('admin.orders.show', $o1->id));
        $navLast = $responseLast->viewData('navigation');

        $this->assertEquals(2, $navLast['position']);
        $this->assertEquals($o2->id, $navLast['previous']['id']);
        $this->assertNull($navLast['next']);
    }

    public function test_order_details_navigation_respects_filter_and_sort_context(): void
    {
        $pending1 = $this->createOrder(['status' => 'pending', 'total_price' => 500]);
        $delivered = $this->createOrder(['status' => 'delivered', 'total_price' => 600]);
        $pending2 = $this->createOrder(['status' => 'pending', 'total_price' => 700]);

        // Filter by status=pending: only pending1 and pending2 exist in this subset
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', [
            'id' => $pending2->id,
            'status' => 'pending',
            'sort' => 'newest',
        ]));

        $response->assertStatus(200);
        $nav = $response->viewData('navigation');

        $this->assertEquals(1, $nav['position']);
        $this->assertEquals(2, $nav['total']);
        $this->assertEquals($pending1->id, $nav['next']['id']);
        $this->assertStringContainsString('status=pending', $nav['next']['url']);
        $this->assertStringContainsString('Back to Filtered (2)', $response->getContent());
    }

    public function test_unauthorized_guests_cannot_access_orders(): void
    {
        $order = $this->createOrder();

        $this->get(route('admin.orders.index'))->assertRedirect('/login');
        $this->get(route('admin.orders.show', $order->id))->assertRedirect('/login');
    }
}
