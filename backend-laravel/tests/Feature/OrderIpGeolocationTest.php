<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\IpLocationService;
use App\Services\OrderService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderIpGeolocationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('role', 'admin')->first();
        PaymentMethod::ensureDefaults();
    }

    private function createTestOrder(array $attributes = []): Order
    {
        $ship = ShippingMethod::firstOrCreate(
            ['name' => 'Standard Delivery'],
            ['charge' => 60, 'status' => 'active', 'cod_available' => true]
        );
        $cat = Category::firstOrCreate(['slug' => 'test-cat'], ['name' => 'Test Cat', 'status' => 'active']);
        $product = Product::firstOrCreate(
            ['slug' => 'test-prod'],
            ['title' => 'Test Product', 'price' => 250, 'category_id' => $cat->id, 'status' => 'active', 'stock' => 20, 'sku' => 'TP-1']
        );

        $result = OrderService::createOrder(array_merge([
            'customer_name' => 'Rahim Ahmed',
            'phone' => '01711223344',
            'country' => 'Bangladesh',
            'division' => 'Dhaka',
            'district' => 'Dhaka',
            'address' => 'House 12, Road 4, Dhanmondi, Dhaka',
            'shipping_method_id' => $ship->id,
            'payment_method' => 'cod',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            '_user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            '_client_ip' => '103.108.144.1',
        ], $attributes));

        return Order::where('order_id', $result['order']['orderId'])->firstOrFail();
    }

    public function test_checkout_captures_client_ip_address_on_order(): void
    {
        $order = $this->createTestOrder(['_client_ip' => '103.108.144.50']);

        $this->assertEquals('103.108.144.50', $order->ip_address);
        $this->assertEquals('103.108.144.0', $order->ip_truncated);
        $this->assertNotNull($order->ip_hash);
    }

    public function test_orders_with_recorded_public_ip_display_ip_and_geolocation_details(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Bangladesh',
                'countryCode' => 'BD',
                'regionName' => 'Dhaka Division',
                'city' => 'Dhaka',
                'isp' => 'Link3 Technologies Ltd.',
                'timezone' => 'Asia/Dhaka',
            ], 200),
        ]);

        $order = $this->createTestOrder(['_client_ip' => '103.108.144.10']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('IP Address &amp; Location', false);
        $response->assertSee('103.108.144.10');
        $response->assertSee('IPv4');
        $response->assertSee('Bangladesh');
        $response->assertSee('Dhaka Division');
        $response->assertSee('Dhaka');
        $response->assertSee('Link3 Technologies Ltd.');
        $response->assertSee('Asia/Dhaka');
        $response->assertSee('Approximate location');
        $response->assertSee('Broadly consistent');
    }

    public function test_ipv6_address_version_detected(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'country' => 'Bangladesh',
                'countryCode' => 'BD',
                'regionName' => 'Dhaka Division',
                'city' => 'Dhaka',
                'isp' => 'Fiber@Home',
                'timezone' => 'Asia/Dhaka',
            ], 200),
        ]);

        $order = $this->createTestOrder(['_client_ip' => '2400:c320:100::1']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('2400:c320:100::1');
        $response->assertSee('IPv6');
    }

    public function test_location_comparison_detects_broadly_consistent_match(): void
    {
        $geo = [
            'ip' => '103.108.144.1',
            'status' => 'success',
            'country' => 'Bangladesh',
            'country_code' => 'BD',
            'region' => 'Dhaka Division',
            'city' => 'Dhaka',
            'is_private' => false,
        ];

        $order = $this->createTestOrder([
            'division' => 'Dhaka',
            'district' => 'Dhaka',
            'address' => 'Uttara, Sector 3, Dhaka',
        ]);

        $comparison = IpLocationService::compareLocation($geo, $order);

        $this->assertEquals('likely_match', $comparison['status']);
        $this->assertEquals('Likely Match', $comparison['label']);
        $this->assertEquals('Broadly consistent', $comparison['headline']);
        $this->assertStringContainsString('broadly consistent with the shipping destination', $comparison['description']);
    }

    public function test_location_comparison_flags_possible_mismatch_without_automatic_fraud(): void
    {
        $geo = [
            'ip' => '198.51.100.10',
            'status' => 'success',
            'country' => 'United States',
            'country_code' => 'US',
            'region' => 'California',
            'city' => 'San Jose',
            'is_private' => false,
        ];

        $order = $this->createTestOrder([
            'country' => 'Bangladesh',
            'division' => 'Dhaka',
            'district' => 'Dhaka',
        ]);

        $comparison = IpLocationService::compareLocation($geo, $order);

        $this->assertEquals('possible_mismatch', $comparison['status']);
        $this->assertEquals('Possible Mismatch', $comparison['label']);
        $this->assertEquals('Possible mismatch — manual review recommended', $comparison['headline']);
        $this->assertStringContainsString('manual review recommended', $comparison['headline']);
        // Crucial requirement: do not claim fraud automatically
        $this->assertStringNotContainsString('Fraudulent Order', $comparison['headline']);
        $this->assertStringContainsString('Note: VPN, roaming, corporate network routing', $comparison['description']);
    }

    public function test_missing_ip_data_displays_not_recorded(): void
    {
        // Old order without raw ip_address
        $order = $this->createTestOrder(['_client_ip' => null]);
        $order->update(['ip_address' => null]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('Not recorded');
        $response->assertSee('Insufficient Data');
    }

    public function test_failed_geolocation_lookup_displays_unavailable_without_crashing_page(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response('Service unavailable', 500),
        ]);

        $order = $this->createTestOrder(['_client_ip' => '103.199.168.10']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('103.199.168.10');
        $response->assertSee('Location lookup unavailable');
        $response->assertSee('Insufficient Data');
    }

    public function test_private_and_loopback_ips_are_not_geolocated_externally(): void
    {
        Http::fake(); // If any HTTP request is triggered, it will be caught

        $order = $this->createTestOrder(['_client_ip' => '127.0.0.1']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('127.0.0.1');
        $response->assertSee('Private Network');
        $response->assertSee('Local / Private Network');
        $response->assertSee('Insufficient Data');

        // Confirm zero external HTTP requests were dispatched
        Http::assertNothingSent();
    }

    public function test_unauthorized_guests_cannot_access_order_ip_details(): void
    {
        $order = $this->createTestOrder();

        $response = $this->get(route('admin.orders.show', $order->id));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_customer_cannot_access_admin_order_details(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createTestOrder();

        $response = $this->actingAs($customer)->get(route('admin.orders.show', $order->id));

        // EnsureAdminAccess will redirect or forbid non-admins
        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    public function test_trusted_proxies_protection_against_spoofed_headers(): void
    {
        // When request comes from an untrusted client with spoofed X-Forwarded-For header,
        // Laravel's request->ip() ignores untrusted X-Forwarded-For when TRUSTED_PROXIES is not set.
        $request = Request::create('/checkout', 'POST', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.195',
            'HTTP_X_FORWARDED_FOR' => '1.1.1.1',
        ]);

        $this->assertEquals('203.0.113.195', $request->ip());
    }
}
