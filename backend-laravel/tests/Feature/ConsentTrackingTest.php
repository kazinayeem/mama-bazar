<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MarketingIntegration;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $over = []): Order
    {
        \App\Models\PaymentMethod::ensureDefaults();
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
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ], $over));

        return Order::where('order_id', $result['order']['orderId'])->firstOrFail();
    }

    public function test_pixels_load_only_after_consent(): void
    {
        MarketingIntegration::create([
            'name' => 'FB', 'type' => 'facebook_pixel', 'pixel_id' => '123456789', 'status' => 'active',
        ]);
        MarketingIntegration::create([
            'name' => 'GA', 'type' => 'google_analytics', 'pixel_id' => 'G-TEST123', 'status' => 'active',
        ]);

        $html = $this->get('/')->getContent();

        // Config is exposed for the consent-gated loader…
        $this->assertStringContainsString('mbTagConfig', $html);
        $this->assertStringContainsString('123456789', $html);
        // …but no third-party <script src> tag is emitted before consent
        // (URLs only appear inside the loader's JS strings).
        $this->assertStringNotContainsString('src="https://connect.facebook.net', $html);
        $this->assertStringNotContainsString('src="https://www.googletagmanager.com/gtag/js', $html);
        $this->assertStringNotContainsString('src="https://www.googletagmanager.com/gtm.js', $html);
        $this->assertStringNotContainsString('src="https://analytics.tiktok.com', $html);
        // Consent banner + fan-out helper are present.
        $this->assertStringContainsString('mb-consent-banner', $html);
        $this->assertStringContainsString('mbTrack', $html);
    }

    public function test_no_pixels_without_integrations(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('mb_consent', $html);
        $this->assertStringNotContainsString('src="https://connect.facebook.net', $html);
        // Empty tag config when nothing is configured.
        $this->assertStringContainsString("window.mbTagConfig = []", $html);
    }

    public function test_product_page_emits_view_item(): void
    {
        $cat = Category::firstOrCreate(['slug' => 't'], ['name' => 'T', 'status' => 'active']);
        $p = Product::firstOrCreate(['slug' => 'tp'], ['title' => 'Test Product', 'price' => 500, 'category_id' => $cat->id, 'status' => 'active', 'stock' => 50]);

        $this->get("/products/{$p->slug}")->assertStatus(200)->assertSee('view_item', false);
    }

    public function test_purchase_capi_dedupes_by_order(): void
    {
        $order = $this->makeOrder();

        $payload = [
            'value' => 560, 'currency' => 'BDT', 'contentIds' => ['1'],
            'eventId' => 'evt-test-1', 'orderId' => $order->order_id,
        ];
        // No CAPI configured → not sent, but order gets marked tracked.
        $first = $this->postJson('/api/analytics/purchase', $payload);
        $first->assertOk()->assertJsonPath('data.sent', false);

        $second = $this->postJson('/api/analytics/purchase', $payload);
        $second->assertOk()->assertJsonPath('data.reason', 'Already tracked');
    }

    public function test_marketing_admin_accepts_capi_type(): void
    {
        $admin = User::create(['name' => 'Admin', 'phone' => '01000000002', 'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->post('/admin/marketing', [
            'name' => 'FB CAPI', 'type' => 'facebook_conversion_api',
            'pixel_id' => '123', 'access_token' => 'tok', 'status' => 'active',
        ])->assertRedirect();
        $this->assertDatabaseHas('marketing_integrations', ['type' => 'facebook_conversion_api']);
    }

    public function test_invoice_pdf_bytes_are_valid(): void
    {
        $order = $this->makeOrder();
        $admin = User::create(['name' => 'Admin', 'phone' => '01000000003', 'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active']);
        $res = $this->actingAs($admin)->get("/admin/orders/{$order->id}/invoice/download");
        $res->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }
}
