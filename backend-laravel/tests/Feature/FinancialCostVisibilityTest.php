<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Cost;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCostHistory;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AdvancedAnalyticsReportService;
use App\Services\AdvancedAnalyticsService;
use App\Support\FinancialDataAccess;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FinancialCostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const COST = '523.45';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $category = Category::create(['name' => 'Kitchen', 'slug' => 'kitchen', 'status' => 'active']);

        $this->product = Product::create([
            'title' => 'Steel Pressure Cooker',
            'slug' => 'steel-pressure-cooker',
            'price' => 999,
            'cost_price' => 523.45,
            'profit_margin' => 41.37,
            'stock' => 10,
            'category_id' => $category->id,
            'sku' => 'SPC-001',
            'status' => 'active',
            'product_status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => '5 Litre',
            'sku' => 'SPC-001-5L',
            'price' => 999,
            'stock' => 10,
            'status' => 'active',
            'availability' => true,
            'options' => ['size' => '5L'],
        ]);

        $order = Order::create([
            'order_id' => 'ORD-COST-001',
            'customer_name' => 'Karim',
            'phone' => '01700000009',
            'address' => 'Dhanmondi, Dhaka',
            'total_price' => 1998,
            'subtotal' => 1998,
            'discount' => 0,
            'shipping_cost' => 0,
            'payment_method' => 'cod',
            'status' => 'delivered',
            'created_at' => now()->subDays(2),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'quantity' => 2,
            'price' => 999,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function staffWith(array $permissions): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'custom_role' => 'CUSTOM',
            'permissions_json' => $permissions,
        ]);
    }

    private function superAdmin(): User
    {
        return User::where('role', 'admin')->where('custom_role', 'SUPER_ADMIN')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsFilters(): array
    {
        return app(AdvancedAnalyticsService::class)->parseFilters(Request::create('/', 'GET', ['preset' => '30d']));
    }

    public function test_authorized_user_sees_buying_price_on_product_pages_and_json(): void
    {
        $user = $this->staffWith(['products.view', 'products.update', FinancialDataAccess::VIEW_COST_PRICE, FinancialDataAccess::VIEW_PROFIT_MARGIN]);

        $this->actingAs($user)->get(route('admin.products.index'))->assertOk()->assertSee('Cost ৳'.self::COST);
        $this->actingAs($user)->get(route('admin.products.show', $this->product->id))->assertOk()->assertSee('৳'.self::COST);
        $this->actingAs($user)->get(route('admin.products.edit', $this->product->id))->assertOk()
            ->assertSee(self::COST)
            ->assertSee('Read-only — editing buying price requires a separate permission.')
            ->assertDontSee('name="cost_price"', false);

        $fullEditor = $this->staffWith(['products.update', FinancialDataAccess::VIEW_COST_PRICE, FinancialDataAccess::EDIT_COST_PRICE]);
        $this->actingAs($fullEditor)->get(route('admin.products.edit', $this->product->id))->assertOk()
            ->assertSee(self::COST)
            ->assertSee('name="cost_price"', false);

        $this->actingAs($user)->getJson(route('admin.products.index'))->assertOk()
            ->assertJsonPath('data.0.costPrice', self::COST)
            ->assertJsonPath('data.0.profitMargin', '41.37');
    }

    public function test_unauthorized_user_never_receives_buying_price_but_keeps_selling_price(): void
    {
        $user = $this->staffWith(['products.view', 'products.update', 'products.export']);

        foreach ([
            route('admin.products.index'),
            route('admin.products.show', $this->product->id),
            route('admin.products.edit', $this->product->id),
        ] as $url) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString(self::COST, $html, $url);
            $this->assertStringNotContainsString('41.37', $html, $url);
            $this->assertStringNotContainsString('costPrice', $html, $url);
            $this->assertStringNotContainsString('name="cost_price"', $html, $url);
            $this->assertStringContainsString('999', $html, $url);
        }

        $json = $this->actingAs($user)->getJson(route('admin.products.index'))->assertOk();
        $json->assertJsonPath('data.0.price', '999');
        $json->assertJsonMissingPath('data.0.costPrice');
        $json->assertJsonMissingPath('data.0.profitMargin');
    }

    public function test_editing_buying_price_requires_separate_permission(): void
    {
        $viewer = $this->staffWith(['products.view', 'products.update', FinancialDataAccess::VIEW_COST_PRICE]);

        $this->actingAs($viewer)
            ->put(route('admin.products.update', $this->product->id), ['title' => 'Steel Pressure Cooker', 'price' => 999, 'cost_price' => 1])
            ->assertForbidden();
        $this->actingAs($viewer)
            ->putJson('/api/products/'.$this->product->id, ['costPrice' => 1])
            ->assertForbidden();
        $this->actingAs($viewer)
            ->putJson('/api/products/'.$this->product->id, ['profitMargin' => 1])
            ->assertForbidden();
        $this->assertSame(523.45, $this->product->fresh()->cost_price);

        $editor = $this->staffWith(['products.update', FinancialDataAccess::EDIT_COST_PRICE]);

        $editForm = $this->actingAs($editor)->get(route('admin.products.edit', $this->product->id))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::COST, $editForm);
        $this->assertStringContainsString('Leave blank to keep it unchanged', $editForm);

        $this->actingAs($editor)
            ->putJson('/api/products/'.$this->product->id, ['costPrice' => 600])
            ->assertOk()
            ->assertJsonMissingPath('data.costPrice');
        $this->assertSame(600.0, $this->product->fresh()->cost_price);

        $history = ProductCostHistory::where('product_id', $this->product->id)->where('field', 'cost_price')->latest('id')->firstOrFail();
        $this->assertSame(523.45, $history->old_value);
        $this->assertSame(600.0, $history->new_value);
        $this->assertSame($editor->id, $history->user_id);

        $plainEditor = $this->staffWith(['products.view', 'products.update']);
        $this->actingAs($plainEditor)
            ->putJson('/api/products/'.$this->product->id, ['title' => 'Renamed Cooker'])
            ->assertOk();
        $this->assertSame(600.0, $this->product->fresh()->cost_price);
    }

    public function test_cost_history_is_restricted(): void
    {
        $this->product->update(['cost_price' => 550]);

        $withoutHistory = $this->staffWith(['products.view', FinancialDataAccess::VIEW_COST_PRICE]);
        $this->actingAs($withoutHistory)->get(route('admin.products.show', $this->product->id))
            ->assertOk()
            ->assertDontSee('Buying Price History');

        $withHistory = $this->staffWith(['products.view', FinancialDataAccess::VIEW_COST_PRICE, FinancialDataAccess::VIEW_COST_HISTORY]);
        $this->actingAs($withHistory)->get(route('admin.products.show', $this->product->id))
            ->assertOk()
            ->assertSee('Buying Price History')
            ->assertSee('৳'.self::COST)
            ->assertSee('৳550.00');
    }

    public function test_profitability_reports_respect_permissions(): void
    {
        $analyst = $this->staffWith(['analytics.view', 'reports.export']);

        $html = $this->actingAs($analyst)->get(route('admin.advanced-analytics.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Gross Profit & Margin', $html);
        $this->assertStringNotContainsString('Valuation at Cost', $html);
        $this->assertStringNotContainsString('Cost & Margin', $html);
        $this->assertStringNotContainsString('value="profitability"', $html);

        $this->actingAs($analyst)
            ->post(route('admin.advanced-analytics.export.pdf'), ['report_type' => 'profitability'])
            ->assertForbidden();

        $sales = app(AdvancedAnalyticsService::class)->getSalesKpis($this->analyticsFilters(), FinancialDataAccess::forUser($analyst));
        $this->assertNull($sales['gross_profit']);
        $this->assertNull($sales['gross_margin_pct']);
        $this->assertNull($sales['cogs_total']);
        $this->assertSame('restricted', $sales['profit_status']);

        $this->actingAs($analyst)->getJson('/api/expenses/profit')->assertForbidden();

        $financeLead = $this->staffWith(['analytics.view', 'reports.view', FinancialDataAccess::VIEW_COST_PRICE, FinancialDataAccess::VIEW_PROFIT_MARGIN, FinancialDataAccess::VIEW_COST_VALUATION]);
        $this->actingAs($financeLead)->get(route('admin.advanced-analytics.index'))->assertOk()
            ->assertSee('Gross Profit & Margin', false)
            ->assertSee('Valuation at Cost', false)
            ->assertSee('৳5,235', false);
        $this->actingAs($financeLead)->getJson('/api/expenses/profit')->assertOk();
    }

    public function test_csv_and_pdf_exports_do_not_leak_cost_without_export_permission(): void
    {
        $viewerWithoutExport = $this->staffWith([
            'products.export', 'analytics.view',
            FinancialDataAccess::VIEW_COST_PRICE, FinancialDataAccess::VIEW_PROFIT_MARGIN, FinancialDataAccess::VIEW_COST_VALUATION,
        ]);

        $productCsv = $this->actingAs($viewerWithoutExport)->get(route('admin.products.export'))->assertOk()->getContent();
        $this->assertStringNotContainsString('costPrice', $productCsv);
        $this->assertStringNotContainsString(self::COST, $productCsv);
        $this->assertStringContainsString('Steel Pressure Cooker', $productCsv);

        $analyticsCsv = $this->actingAs($viewerWithoutExport)->get(route('admin.advanced-analytics.export.csv'))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Cost Price', $analyticsCsv);
        $this->assertStringNotContainsString('Stock Cost Value', $analyticsCsv);
        $this->assertStringNotContainsString('Period Profit', $analyticsCsv);
        $this->assertStringNotContainsString(self::COST, $analyticsCsv);
        $this->assertStringContainsString('Selling Price (BDT)', $analyticsCsv);

        $this->actingAs($viewerWithoutExport);
        $pdfHtml = view(
            'admin.advanced-analytics.pdf-report',
            app(AdvancedAnalyticsReportService::class)->buildReportViewData($this->analyticsFilters(), ['report_type' => 'complete'], FinancialDataAccess::forUser($viewerWithoutExport))
        )->render();
        $this->assertStringNotContainsString(self::COST, $pdfHtml);
        $this->assertStringNotContainsString('5,235', $pdfHtml);
        $this->assertStringContainsString('Not included', $pdfHtml);

        $exporter = $this->staffWith([
            'products.export', 'analytics.view',
            FinancialDataAccess::VIEW_COST_PRICE, FinancialDataAccess::VIEW_PROFIT_MARGIN, FinancialDataAccess::VIEW_COST_VALUATION,
            FinancialDataAccess::EXPORT_COST_REPORTS,
        ]);

        $this->assertStringContainsString('costPrice', $this->actingAs($exporter)->get(route('admin.products.export'))->getContent());
        $exporterCsv = $this->actingAs($exporter)->get(route('admin.advanced-analytics.export.csv'))->streamedContent();
        $this->assertStringContainsString('Cost Price (BDT)', $exporterCsv);
        $this->assertStringContainsString('Period Profit (BDT)', $exporterCsv);
        $this->assertStringContainsString(self::COST, $exporterCsv);
    }

    public function test_api_cannot_bypass_cost_restrictions(): void
    {
        $this->getJson('/api/products')->assertOk()->assertJsonMissingPath('data.0.costPrice');
        $this->getJson('/api/products/'.$this->product->id)->assertOk()
            ->assertJsonMissingPath('data.costPrice')
            ->assertJsonMissingPath('data.profitMargin');

        $staff = $this->staffWith(['products.view', 'products.create', 'costs.view']);
        $this->actingAs($staff)->getJson('/api/products/'.$this->product->id)->assertJsonMissingPath('data.costPrice');
        $this->actingAs($staff)
            ->postJson('/api/products', ['title' => 'Sneaky', 'price' => 10, 'categoryId' => $this->product->category_id, 'costPrice' => 1])
            ->assertForbidden();

        $purchase = Cost::create([
            'title' => 'Cooker restock',
            'cost_type' => 'purchase',
            'quantity' => '10',
            'unit_cost' => '523.45',
            'total_cost' => '5234.50',
            'product_id' => $this->product->id,
            'cost_date' => now()->toDateString(),
        ]);
        Cost::create([
            'title' => 'Office rent',
            'cost_type' => 'operational',
            'quantity' => '1',
            'unit_cost' => '20000',
            'total_cost' => '20000',
            'cost_date' => now()->toDateString(),
        ]);

        $list = $this->actingAs($staff)->getJson('/api/costs')->assertOk()->json('data');
        $byTitle = collect($list)->keyBy('title');
        $this->assertNull($byTitle['Cooker restock']['unitCost']);
        $this->assertNull($byTitle['Cooker restock']['totalCost']);
        $this->assertTrue($byTitle['Cooker restock']['costRestricted']);
        $this->assertEquals(20000, $byTitle['Office rent']['unitCost']);
        $this->actingAs($staff)->getJson('/api/costs/'.$purchase->id)->assertOk()->assertJsonPath('data.unitCost', null);

        $purchasing = $this->staffWith(['costs.view', FinancialDataAccess::VIEW_COST_HISTORY]);
        $this->actingAs($purchasing)->getJson('/api/costs/'.$purchase->id)->assertOk()->assertJsonPath('data.unitCost', 523.45);
    }

    public function test_variants_and_valuations_follow_the_same_rules(): void
    {
        $analyst = $this->staffWith(['analytics.view', FinancialDataAccess::VIEW_COST_VALUATION]);

        $this->actingAs($analyst)->getJson(route('admin.advanced-analytics.product-variants', $this->product->id))
            ->assertOk()
            ->assertJsonMissingPath('product.cost_price')
            ->assertJsonPath('variants.0.name', '5 Litre');

        $service = app(AdvancedAnalyticsService::class);
        $filters = $this->analyticsFilters();

        $valuationOnly = FinancialDataAccess::forUser($analyst);
        $this->assertEquals(5234.5, $service->getInventoryKpis($filters, $valuationOnly)['cost_valuation']);
        $row = collect($service->getProductStockTable($filters, $valuationOnly)->items())->firstWhere('id', $this->product->id);
        $this->assertNull($row->cost_price);
        $this->assertNull($row->cost_valuation);
        $this->assertArrayNotHasKey('cost_price', $row->toArray());

        $noAccess = FinancialDataAccess::forUser($this->staffWith(['analytics.view']));
        $this->assertNull($service->getInventoryKpis($filters, $noAccess)['cost_valuation']);
        $this->assertArrayNotHasKey('cost_value', $service->getChartsData($filters, $noAccess)['category_inventory'][0]);

        $costViewer = $this->staffWith(['analytics.view', FinancialDataAccess::VIEW_COST_PRICE]);
        $this->actingAs($costViewer)->getJson(route('admin.advanced-analytics.product-variants', $this->product->id))
            ->assertJsonPath('product.cost_price', 523.45);
    }

    public function test_role_defaults_and_custom_overrides(): void
    {
        $this->assertTrue(FinancialDataAccess::forUser($this->superAdmin())->canViewCostPrice);

        $adminPreset = FinancialDataAccess::forUser(User::factory()->create(['role' => 'admin', 'status' => 'active', 'custom_role' => 'ADMIN']));
        $this->assertTrue($adminPreset->canViewCostPrice && $adminPreset->canEditCostPrice && $adminPreset->canExportCostReports);

        foreach (['manager', 'staff'] as $role) {
            $presetUser = User::factory()->create(['role' => $role, 'status' => 'active', 'permission_mode' => 'role']);
            $this->assertFalse(FinancialDataAccess::forUser($presetUser)->hasAnyAccess(), $role);
        }
        $this->assertFalse(FinancialDataAccess::forUser(User::factory()->create(['role' => 'user']))->hasAnyAccess());

        $custom = FinancialDataAccess::forUser($this->staffWith(['products.view', FinancialDataAccess::VIEW_COST_PRICE]));
        $this->assertTrue($custom->canViewCostPrice);
        $this->assertFalse($custom->canEditCostPrice);
        $this->assertFalse($custom->canViewProfitMargin);

        $this->assertSame(7, DB::table('role_permissions')->where('role_name', 'ADMIN')->whereIn('permission_code', FinancialDataAccess::ALL_CODES)->count());
        $this->assertSame(0, DB::table('role_permissions')->where('role_name', '!=', 'ADMIN')->whereIn('permission_code', FinancialDataAccess::ALL_CODES)->count());

        $member = User::factory()->create(['role' => 'staff', 'status' => 'active', 'permission_mode' => 'role']);
        $this->actingAs($this->superAdmin())->get(route('admin.members.index'))->assertOk()
            ->assertSee('Financial &amp; Cost Data', false)
            ->assertSee(FinancialDataAccess::EDIT_COST_PRICE);

        $this->actingAs($this->superAdmin())->put(route('admin.members.update', $member->id), [
            'name' => $member->name,
            'phone' => $member->phone,
            'email' => $member->email,
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions' => ['products.view', FinancialDataAccess::VIEW_PROFIT_MARGIN],
        ])->assertSessionHas('success');

        $updated = FinancialDataAccess::forUser($member->fresh());
        $this->assertTrue($updated->canViewProfitMargin);
        $this->assertFalse($updated->canViewCostPrice);
    }

    public function test_existing_calculations_are_unchanged_for_authorized_viewers(): void
    {
        $service = app(AdvancedAnalyticsService::class);
        $filters = $this->analyticsFilters();

        $full = FinancialDataAccess::all();
        $inventory = $service->getInventoryKpis($filters, $full);
        $sales = $service->getSalesKpis($filters, $full);

        $this->assertEquals(5234.5, $inventory['cost_valuation']);
        $this->assertEquals(9990, $inventory['retail_valuation']);
        $this->assertSame('exact', $sales['profit_status']);
        $this->assertEquals(round(1998 - 2 * 523.45, 2), $sales['gross_profit']);
        $this->assertEquals(round(((1998 - 2 * 523.45) / 1998) * 100, 1), $sales['gross_margin_pct']);
        $this->assertEquals(1046.9, $sales['cogs_total']);

        $restricted = $service->getSalesKpis($filters, FinancialDataAccess::none());
        foreach (['net_sales', 'gross_sales', 'orders_count', 'units_sold', 'avg_order_value'] as $key) {
            $this->assertSame($sales[$key], $restricted[$key], $key);
        }
        $this->assertSame($inventory['retail_valuation'], $service->getInventoryKpis($filters, FinancialDataAccess::none())['retail_valuation']);
    }

    public function test_aborts_profitability_pdf_view_data_without_export_right(): void
    {
        $this->expectException(HttpException::class);

        app(AdvancedAnalyticsReportService::class)->buildReportViewData(
            $this->analyticsFilters(),
            ['report_type' => 'profitability'],
            FinancialDataAccess::forUser($this->staffWith(['analytics.view', FinancialDataAccess::VIEW_PROFIT_MARGIN]))
        );
    }
}
