<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\AdvancedAnalyticsReportService;
use App\Services\AdvancedAnalyticsService;
use App\Support\FinancialDataAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAdvancedAnalyticsController extends Controller
{
    public function __construct(
        protected AdvancedAnalyticsService $analyticsService,
        protected AdvancedAnalyticsReportService $reportService
    ) {}

    /**
     * Display the Advanced Analytics & Inventory Intelligence Dashboard.
     */
    public function index(Request $request)
    {
        $filters = $this->analyticsService->parseFilters($request);
        $financialAccess = FinancialDataAccess::forUser($request->user());

        $inventoryKpis = $this->analyticsService->getInventoryKpis($filters, $financialAccess);
        $salesKpis = $this->analyticsService->getSalesKpis($filters, $financialAccess);
        $chartsData = $this->analyticsService->getChartsData($filters, $financialAccess);
        $productsPaginator = $this->analyticsService->getProductStockTable($filters, $financialAccess);

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        $monthlyAnalysis = $this->analyticsService->getMonthlyHistoricalAnalysis($filters['year'], $filters['month']);

        return view('admin.advanced-analytics.index', [
            'filters' => $filters,
            'inventoryKpis' => $inventoryKpis,
            'salesKpis' => $salesKpis,
            'chartsData' => $chartsData,
            'products' => $productsPaginator,
            'categories' => $categories,
            'brands' => $brands,
            'monthlyAnalysis' => $monthlyAnalysis,
            'financialAccess' => $financialAccess,
        ]);
    }

    /**
     * Export filtered products and stock analytics to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->analyticsService->parseFilters($request);

        return $this->analyticsService->exportCsv($filters, FinancialDataAccess::forUser($request->user()));
    }

    /**
     * Generate and export branded PDF report.
     */
    public function exportPdf(Request $request): Response
    {
        $filters = $this->analyticsService->parseFilters($request);

        return $this->reportService->generatePdf($filters, $request->all(), FinancialDataAccess::forUser($request->user()));
    }

    /**
     * Get variant details for product drill-down modal.
     */
    public function productVariants(Request $request, int $id): JsonResponse
    {
        $product = Product::with(['variants', 'category:id,name'])->findOrFail($id);

        $productData = [
            'id' => $product->id,
            'title' => $product->title,
            'sku' => $product->sku,
            'category' => $product->category?->name ?? 'Uncategorized',
            'stock' => (int) $product->stock,
            'price' => (float) $product->price,
            'sale_price' => (float) ($product->sale_price ?: $product->price),
        ];
        if (FinancialDataAccess::forUser($request->user())->canViewCostPrice) {
            $productData['cost_price'] = (float) $product->cost_price;
        }

        return response()->json([
            'success' => true,
            'product' => $productData,
            'variants' => $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'sku' => $v->sku ?: '—',
                'stock' => (int) $v->stock,
                'price' => (float) ($v->price ?: $product->price),
                'discount_price' => $v->discount_price ? (float) $v->discount_price : null,
                'status' => $v->status,
                'availability' => (bool) $v->availability,
                'options' => $v->options,
            ]),
        ]);
    }
}
