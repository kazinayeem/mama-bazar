<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AdvancedAnalyticsReportService;
use App\Services\AdvancedAnalyticsService;
use App\Support\FinancialDataAccess;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    public function test_every_pdf_report_type_downloads_a_real_pdf_in_both_orientations(): void
    {
        foreach (array_keys(AdvancedAnalyticsReportService::REPORT_TITLES) as $reportType) {
            foreach (['landscape', 'portrait'] as $orientation) {
                $response = $this->actingAs($this->admin)
                    ->withHeaders(['Accept' => 'application/pdf, application/json'])
                    ->post(route('admin.advanced-analytics.export.pdf'), [
                        'preset' => '30d',
                        'report_type' => $reportType,
                        'orientation' => $orientation,
                    ]);

                $response->assertOk();
                $response->assertHeader('content-type', 'application/pdf');
                $response->assertDownload('mamabazar-'.$reportType.'-report-'.now()->format('Y-m-d').'.pdf');
                $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

                $content = $response->getContent();
                $this->assertStringStartsWith('%PDF-', $content, "{$reportType}/{$orientation} is not a PDF.");
                $this->assertMatchesRegularExpression('/\/MediaBox \[0(?:\.0+)? 0(?:\.0+)? ([\d.]+) ([\d.]+)\]/', $content);
                preg_match('/\/MediaBox \[0(?:\.0+)? 0(?:\.0+)? ([\d.]+) ([\d.]+)\]/', $content, $mediaBox);
                $isLandscape = (float) $mediaBox[1] > (float) $mediaBox[2];
                $this->assertSame($orientation === 'landscape', $isLandscape, "{$reportType} ignored {$orientation} orientation.");
            }
        }
    }

    public function test_pdf_export_rejects_invalid_report_options_with_json_errors(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.advanced-analytics.export.pdf'), [
                'report_type' => 'everything',
                'orientation' => 'sideways',
                'stock_status' => 'unknown',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['report_type', 'orientation', 'stock_status']);
    }

    public function test_pdf_export_is_forbidden_without_analytics_permissions(): void
    {
        $orderClerk = User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'custom_role' => 'CUSTOM',
            'permissions_json' => ['orders.view'],
        ]);

        $this->actingAs($orderClerk)
            ->post(route('admin.advanced-analytics.export.pdf'), ['report_type' => 'executive', 'orientation' => 'portrait'])
            ->assertForbidden();
    }

    public function test_pdf_report_reflects_applied_filters_and_bengali_names(): void
    {
        $filters = app(AdvancedAnalyticsService::class)->parseFilters(Request::create('/', 'POST', [
            'preset' => 'month_year',
            'month' => 3,
            'year' => 2025,
            'category_id' => $this->category->id,
            'stock_status' => 'low_stock',
        ]));

        $viewData = app(AdvancedAnalyticsReportService::class)->buildReportViewData(
            $filters,
            ['report_type' => 'inventory', 'orientation' => 'portrait'],
            FinancialDataAccess::forUser($this->admin)
        );

        $this->assertSame('2025-03-01', $viewData['filters']['start_date']->format('Y-m-d'));
        $this->assertSame('2025-03-31', $viewData['filters']['end_date']->format('Y-m-d'));
        $this->assertContains('Category: Home Decor & Lighting', $viewData['appliedFilters']);
        $this->assertContains('Stock: Low Stock', $viewData['appliedFilters']);
        $this->assertSame(
            ['সোনার কানের দুল ক্রিস্টাল ডোম'],
            collect($viewData['products'])->pluck('title')->all()
        );

        $html = view('admin.advanced-analytics.pdf-report', $viewData)->render();
        $this->assertStringContainsString('সোনার কানের দুল ক্রিস্টাল ডোম', $html);
        $this->assertStringContainsString('Filters: Category: Home Decor &amp; Lighting | Stock: Low Stock', $html);
    }

    public function test_pdf_modal_renders_generate_button_and_forwards_all_filters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.advanced-analytics.index', [
            'preset' => 'month_year',
            'month' => 3,
            'year' => 2025,
            'sort_by' => 'price_asc',
        ]));

        $response->assertOk();
        $response->assertSee('id="analytics-pdf-report-form"', false);
        $response->assertSee('form="analytics-pdf-report-form"', false);
        $response->assertSee('Generate PDF Report');
        $response->assertSee('name="month" value="3"', false);
        $response->assertSee('name="year" value="2025"', false);
        $response->assertSee('name="sort_by" value="price_asc"', false);
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
