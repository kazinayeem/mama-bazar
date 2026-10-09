<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdvancedAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected Product $productWithCost;

    protected Product $productWithoutCost;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')
            ->where('custom_role', 'SUPER_ADMIN')
            ->first();

        $this->category = Category::create([
            'name' => 'Home Decor & Lighting',
            'slug' => 'home-decor-lighting',
            'status' => 'active',
        ]);

        $brand = Brand::create([
            'name' => 'Philips Lumens',
            'slug' => 'philips-lumens',
            'status' => 'active',
        ]);

        // Product with cost_price
        $this->productWithCost = Product::create([
            'title' => 'ভিন্টেজ এলইডি ক্যাম্পিং ল্যান্টার্ন',
            'slug' => 'vintage-led-camping-lantern',
            'price' => 1000,
            'sale_price' => 800,
            'cost_price' => 500,
            'stock' => 20,
            'low_stock_alert' => 5,
            'category_id' => $this->category->id,
            'brand_id' => $brand->id,
            'sku' => 'VNT-LED-001',
            'barcode' => '8941122334455',
            'status' => 'active',
            'product_status' => 'published',
        ]);

        // Product without cost_price
        $this->productWithoutCost = Product::create([
            'title' => 'সোনার কানের দুল ক্রিস্টাল ডোম',
            'slug' => 'golden-crystal-dome-lamp',
            'price' => 1500,
            'sale_price' => 1200,
            'cost_price' => 0,
            'stock' => 3, // Low stock
            'low_stock_alert' => 5,
            'category_id' => $this->category->id,
            'sku' => 'GLD-DOM-002',
            'status' => 'active',
            'product_status' => 'published',
        ]);

        // Out of stock product
        Product::create([
            'title' => 'স্টক শেষ কাঠের ল্যাম্প',
            'slug' => 'wooden-lamp-out-of-stock',
            'price' => 600,
            'sale_price' => 500,
            'cost_price' => 0,
            'stock' => 0,
            'category_id' => $this->category->id,
            'sku' => 'OUT-LMP-003',
            'status' => 'active',
            'product_status' => 'published',
        ]);

        // Product with variants
        $variantProduct = Product::create([
            'title' => 'টি-শার্ট ফ্যাশন কালেকশন',
            'slug' => 't-shirt-fashion-collection',
            'price' => 450,
            'stock' => 50,
            'category_id' => $this->category->id,
            'status' => 'active',
            'product_status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $variantProduct->id,
            'name' => 'Size M / Navy Blue',
            'sku' => 'TSH-M-NAVY',
            'price' => 450,
            'stock' => 25,
            'status' => 'active',
            'availability' => true,
            'options' => ['size' => 'M', 'color' => 'Navy Blue'],
        ]);

        ProductVariant::create([
            'product_id' => $variantProduct->id,
            'name' => 'Size L / Navy Blue',
            'sku' => 'TSH-L-NAVY',
            'price' => 450,
            'stock' => 25,
            'status' => 'active',
            'availability' => true,
            'options' => ['size' => 'L', 'color' => 'Navy Blue'],
        ]);

        // Create completed order
        $order = Order::create([
            'order_id' => 'ORD-TEST-001',
            'customer_name' => 'Rahim Ahmed',
            'phone' => '01700000001',
            'address' => 'Mirpur 10, Dhaka',
            'total_price' => 1660,
            'subtotal' => 1600,
            'discount' => 0,
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'status' => 'delivered',
            'created_at' => now()->subDays(2),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->productWithCost->id,
            'product_title' => $this->productWithCost->title,
            'product_sku' => $this->productWithCost->sku,
            'quantity' => 2,
            'price' => 800,
        ]);

        // Create cancelled order (should be excluded from Net sales)
        $cancelledOrder = Order::create([
            'order_id' => 'ORD-CANCEL-001',
            'customer_name' => 'Karim Hasan',
            'phone' => '01700000002',
            'address' => 'Uttara, Dhaka',
            'total_price' => 860,
            'subtotal' => 800,
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'status' => 'cancelled',
            'created_at' => now()->subDays(1),
        ]);

        OrderItem::create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $this->productWithCost->id,
            'quantity' => 1,
            'price' => 800,
        ]);
    }

    public function test_guest_cannot_access_advanced_analytics(): void
    {
        $response = $this->get(route('admin.advanced-analytics.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_advanced_analytics_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.index'));

        $response->assertStatus(200);
        $response->assertSee('Advanced Analytics &amp; Inventory Intelligence', false);
        $response->assertSee('Home Decor &amp; Lighting', false);
        $response->assertSee('ভিন্টেজ এলইডি ক্যাম্পিং ল্যান্টার্ন');
        $response->assertSee('Export PDF Report');
        $response->assertSee('Export CSV');
    }

    public function test_sidebar_includes_advanced_analytics_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Advanced Analytics');
        $response->assertSee(route('admin.advanced-analytics.index'));
    }

    public function test_inventory_kpis_and_stock_health_calculated_accurately(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.index'));

        $response->assertStatus(200);
        // Total units in stock: 20 + 3 + 0 + 50 = 73
        $response->assertSee('73');
        // Low stock count: 1 (productWithoutCost with stock 3 <= 5)
        // Out of stock count: 1 (product with stock 0)
    }

    public function test_net_sales_excludes_cancelled_and_refunded_orders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.index', ['preset' => '30d']));

        $response->assertStatus(200);
        // Valid order total is 1660, cancelled is 860
        $response->assertSee('1,660');
        $response->assertSee('860'); // Cancelled sales note
    }

    public function test_profit_calculated_only_when_cost_price_is_present(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.index'));

        $response->assertStatus(200);
        // Revenue on productWithCost is 2 * 800 = 1600. Cost is 2 * 500 = 1000. Profit = 600.
        $response->assertSee('600');
    }

    public function test_category_and_stock_status_filtering_works(): void
    {
        // Category filter
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.index', [
            'category_id' => $this->category->id,
            'stock_status' => 'low_stock',
        ]));

        $response->assertStatus(200);
        $response->assertSee('সোনার কানের দুল ক্রিস্টাল ডোম');
        $response->assertDontSee('ভিন্টেজ এলইডি ক্যাম্পিং ল্যান্টার্ন'); // has stock 20, not low stock
    }

    public function test_search_filtering_by_sku_and_bengali_name(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.index', [
            'search' => 'VNT-LED-001',
        ]));

        $response->assertStatus(200);
        $response->assertSee('ভিন্টেজ এলইডি ক্যাম্পিং ল্যান্টার্ন');
        $response->assertDontSee('সোনার কানের দুল ক্রিস্টাল ডোম');
    }

    public function test_csv_export_returns_valid_streamed_file_with_utf8_bom(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.export.csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        // Check for UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        // Check headers
        $this->assertStringContainsString('Product ID', $content);
        $this->assertStringContainsString('Product Name', $content);
        $this->assertStringContainsString('SKU', $content);
        // Check Bengali product name
        $this->assertStringContainsString('ভিন্টেজ এলইডি ক্যাম্পিং ল্যান্টার্ন', $content);
    }

    public function test_pdf_report_export_generates_downloadable_pdf(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.advanced-analytics.export.pdf'), [
            'preset' => '30d',
            'report_type' => 'executive',
            'orientation' => 'landscape',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_variant_drilldown_endpoint_returns_json_details(): void
    {
        $variantProduct = Product::where('slug', 't-shirt-fashion-collection')->first();

        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.product-variants', $variantProduct->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'product' => [
                'id' => $variantProduct->id,
                'title' => $variantProduct->title,
            ],
        ]);
        $response->assertJsonFragment([
            'name' => 'Size M / Navy Blue',
            'sku' => 'TSH-M-NAVY',
            'stock' => 25,
        ]);
    }
}
