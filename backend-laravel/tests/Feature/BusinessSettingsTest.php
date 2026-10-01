<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\BusinessSettingService;
use App\Services\OrderService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminSeeder::class);

        $this->admin = User::firstOrCreate(
            ['phone' => '01700000001'],
            ['name' => 'System Admin', 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active']
        );

        $this->customer = User::create([
            'name' => 'Regular Customer',
            'phone' => '01799999999',
            'password' => bcrypt('password'),
            'role' => 'user',
            'status' => 'active',
        ]);
    }

    private function makeOrder(): Order
    {
        \App\Models\PaymentMethod::ensureDefaults();
        $ship = ShippingMethod::firstOrCreate(
            ['name' => 'Standard Delivery'],
            ['charge' => 60, 'status' => 'active', 'cod_available' => true]
        );
        $cat = Category::firstOrCreate(['slug' => 'grocery'], ['name' => 'Grocery', 'status' => 'active']);
        $prod = Product::firstOrCreate(
            ['slug' => 'premium-rice'],
            ['title' => 'Premium Rice 5kg', 'price' => 450, 'category_id' => $cat->id, 'status' => 'active', 'product_status' => 'published', 'stock' => 100, 'sku' => 'RICE-01']
        );

        $result = OrderService::createOrder([
            'customer_name' => 'Habib Rahman',
            'phone' => '01711223344',
            'district' => 'Dhaka',
            'address' => 'House 5, Road 2, Dhanmondi',
            'shipping_method_id' => $ship->id,
            'payment_method' => 'cod',
            'items' => [['product_id' => $prod->id, 'quantity' => 1]],
        ]);

        return Order::where('order_id', $result['order']['orderId'])->firstOrFail();
    }

    public function test_guests_and_customers_cannot_access_business_settings(): void
    {
        $this->get('/admin/settings/business')->assertRedirect();

        $this->actingAs($this->customer)
            ->get('/admin/settings/business')
            ->assertStatus(403);

        $this->actingAs($this->customer)
            ->post('/admin/settings/business', ['business_name' => 'Hacked Name'])
            ->assertStatus(403);
    }

    public function test_admin_can_view_business_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/settings/business');
        $response->assertStatus(200);
        $response->assertSee('Business Information');
        $response->assertSee('Basic Information');
        $response->assertSee('Contact & Helpline');
        $response->assertSee('Business Address');
        $response->assertSee('Online Presence');
        $response->assertSee('Legal & Footer');
        $response->assertSee('Software Attribution Guarantee');
        $response->assertSee('https://bornosoft.bd/');
    }

    public function test_validation_rejects_invalid_emails_and_urls(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/settings/business', [
            'business_name' => 'Mama Bazar',
            'site_name' => 'Mama Bazar',
            'primary_phone' => '01711223344',
            'primary_email' => 'invalid-email-string',
            'support_email' => 'not-an-email',
            'website_url' => 'not-a-valid-url',
            'facebook_url' => 'not-a-valid-url',
        ]);

        $response->assertSessionHasErrors(['primary_email', 'support_email', 'website_url', 'facebook_url']);
    }

    public function test_admin_can_update_business_settings_and_cache_updates_immediately(): void
    {
        $payload = [
            'business_name' => 'Mama Bazar Superstore',
            'site_name' => 'Mama Bazar Online',
            'tagline' => 'Fresh Groceries Daily to Your Door',
            'business_description' => 'Fastest grocery delivery service in Bangladesh.',
            'primary_phone' => '01888-777666',
            'secondary_phone' => '01888-555444',
            'support_phone' => '01888-333222',
            'primary_email' => 'contact@mamabazar.com',
            'support_email' => 'help@mamabazar.com',
            'sales_email' => 'sales@mamabazar.com',
            'whatsapp_number' => '01888-777666',
            'address_line1' => 'Plot 45, Gulshan Avenue',
            'address_line2' => 'Level 4, Commercial Tower',
            'city' => 'Gulshan-2',
            'district' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'Bangladesh',
            'website_url' => 'https://mamabazar.com.bd',
            'facebook_url' => 'https://facebook.com/mamabazarofficial',
            'instagram_url' => 'https://instagram.com/mamabazarofficial',
            'youtube_url' => 'https://youtube.com/@mamabazar',
            'copyright_text' => '© :year Mama Bazar Superstore. All rights reserved.',
            'footer_description' => 'Your trusted family grocery partner.',
            'return_policy_short' => '7-day replacement guarantee on fresh products.',
            'business_registration' => 'BIN-987654321',
        ];

        $res = $this->from(route('admin.settings.business'))
            ->actingAs($this->admin)
            ->post('/admin/settings/business', $payload);
        $res->assertRedirect(route('admin.settings.business'));
        $res->assertSessionHas('success');

        // Check database persistence
        $this->assertDatabaseHas('site_settings', ['key' => 'business_name', 'value' => 'Mama Bazar Superstore']);
        $this->assertDatabaseHas('site_settings', ['key' => 'primary_phone', 'value' => '01888-777666']);
        $this->assertDatabaseHas('site_settings', ['key' => 'support_email', 'value' => 'help@mamabazar.com']);
        $this->assertDatabaseHas('site_settings', ['key' => 'business_registration', 'value' => 'BIN-987654321']);

        // Check service retrieval & computed properties
        $info = BusinessSettingService::all();
        $this->assertEquals('Mama Bazar Superstore', $info['business_name']);
        $this->assertEquals('01888-777666', $info['primary_phone']);
        $this->assertEquals('01888777666', $info['phone_raw']);
        $this->assertEquals('https://wa.me/8801888777666', $info['whatsapp_url']);
        $this->assertEquals('help@mamabazar.com', $info['support_email']);
        $this->assertStringContainsString('Plot 45, Gulshan Avenue', $info['formatted_address']);
        $this->assertStringContainsString('1212', $info['formatted_address']);
        $this->assertStringContainsString((string) date('Y'), $info['copyright_rendered']);
    }

    public function test_storefront_header_and_footer_reflect_updated_business_information(): void
    {
        BusinessSettingService::setMany([
            'business_name' => 'Mama Bazar Global',
            'primary_phone' => '01799-887766',
            'support_email' => 'care@mamabazar.com',
            'whatsapp_number' => '01799-887766',
            'address_line1' => 'Block C, Banani',
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'postal_code' => '1213',
            'country' => 'Bangladesh',
            'copyright_text' => '© :year Mama Bazar Global Corp.',
            'footer_description' => 'Fastest groceries across Dhaka.',
        ]);

        $res = $this->get('/');
        $res->assertStatus(200);

        // Header helpline link
        $res->assertSee('01799-887766');
        $res->assertSee('tel:01799887766');

        // Footer information
        $res->assertSee('care@mamabazar.com');
        $res->assertSee('mailto:care@mamabazar.com');
        $res->assertSee('Block C, Banani');
        $res->assertSee('https://wa.me/8801799887766');
        $res->assertSee('Fastest groceries across Dhaka.');
        $res->assertSee('© ' . date('Y') . ' Mama Bazar Global Corp.');

        // Bornosoft software attribution remains strictly intact and clickable
        $res->assertSee('https://bornosoft.bd/');
        $res->assertSee('Crafted by');
        $res->assertSee('Bornosoft');
    }

    public function test_contact_and_faq_and_track_pages_reflect_updated_business_information(): void
    {
        BusinessSettingService::setMany([
            'primary_phone' => '01611-223344',
            'secondary_phone' => '01611-556677',
            'support_email' => 'support@mamabazar.com',
            'sales_email' => 'corporate@mamabazar.com',
            'whatsapp_number' => '01611-223344',
            'address_line1' => '100 Pragati Sarani',
            'city' => 'Badda',
            'district' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'Bangladesh',
        ]);

        // Contact Page
        $contactRes = $this->get('/contact');
        $contactRes->assertStatus(200);
        $contactRes->assertSee('01611-223344');
        $contactRes->assertSee('tel:01611223344');
        $contactRes->assertSee('01611-556677');
        $contactRes->assertSee('support@mamabazar.com');
        $contactRes->assertSee('corporate@mamabazar.com');
        $contactRes->assertSee('100 Pragati Sarani');
        $contactRes->assertSee('https://wa.me/8801611223344');

        // FAQ Page
        $faqRes = $this->get('/faq');
        $faqRes->assertStatus(200);
        $faqRes->assertSee('01611-223344');
        $faqRes->assertSee('support@mamabazar.com');
        $faqRes->assertSee('https://wa.me/8801611223344');

        // Track Order Page
        $trackRes = $this->get('/track?order_id=UNKNOWN&phone=01700000000');
        $trackRes->assertStatus(200);
        $trackRes->assertSee('01611-223344');
        $trackRes->assertSee('support@mamabazar.com');
    }

    public function test_admin_invoice_and_packing_slip_use_updated_business_information(): void
    {
        $order = $this->makeOrder();

        BusinessSettingService::setMany([
            'business_name' => 'Mama Bazar Enterprise',
            'tagline' => 'Wholesale & Retail Groceries',
            'primary_phone' => '01300-998877',
            'support_email' => 'billing@mamabazar.com',
            'business_registration' => 'BIN-1122334455',
            'address_line1' => 'Level 8, Commerce Center',
            'city' => 'Motijheel',
            'district' => 'Dhaka',
            'postal_code' => '1000',
            'country' => 'Bangladesh',
            'return_policy_short' => 'Report damaged products within 48 hours for immediate replacement.',
        ]);

        // HTML Invoice
        $invRes = $this->actingAs($this->admin)->get("/admin/orders/{$order->id}/invoice");
        $invRes->assertStatus(200);
        $invRes->assertSee('Mama Bazar Enterprise');
        $invRes->assertSee('Wholesale &amp; Retail Groceries', false);
        $invRes->assertSee('01300-998877');
        $invRes->assertSee('billing@mamabazar.com');
        $invRes->assertSee('BIN-1122334455');
        $invRes->assertSee('Level 8, Commerce Center');
        $invRes->assertSee('Report damaged products within 48 hours');
        // Bornosoft link intact in PDF branding footer
        $invRes->assertSee('https://bornosoft.bd/');

        // PDF Invoice View
        $pdfHtml = view(
            'admin.orders.invoice-pdf',
            ['order' => $order->load(['items.product', 'items.variant']), 'store' => \App\Http\Controllers\Admin\AdminOrderWebController::storeInfo()]
        )->render();
        $this->assertStringContainsString('Mama Bazar Enterprise', $pdfHtml);
        $this->assertStringContainsString('01300-998877', $pdfHtml);
        $this->assertStringContainsString('billing@mamabazar.com', $pdfHtml);
        $this->assertStringContainsString('BIN-1122334455', $pdfHtml);
        $this->assertStringContainsString('https://bornosoft.bd/', $pdfHtml);

        // Packing Slip
        $slipRes = $this->actingAs($this->admin)->get("/admin/orders/{$order->id}/packing-slip");
        $slipRes->assertStatus(200);
        $slipRes->assertSee('Mama Bazar Enterprise');
    }

    public function test_empty_optional_fields_fallback_safely_without_errors(): void
    {
        BusinessSettingService::setMany([
            'secondary_phone' => '',
            'support_phone' => '',
            'sales_email' => '',
            'whatsapp_number' => '',
            'address_line2' => '',
            'facebook_url' => '',
            'instagram_url' => '',
            'youtube_url' => '',
            'linkedin_url' => '',
            'business_registration' => '',
        ]);

        $info = BusinessSettingService::all();
        $this->assertEmpty($info['secondary_phone']);
        $this->assertEmpty($info['sales_email']);
        $this->assertIsArray($info['social_links']);

        // Home page loads cleanly with empty optional fields
        $homeRes = $this->get('/');
        $homeRes->assertStatus(200);

        // Contact page loads cleanly with empty optional fields
        $contactRes = $this->get('/contact');
        $contactRes->assertStatus(200);
    }
}
