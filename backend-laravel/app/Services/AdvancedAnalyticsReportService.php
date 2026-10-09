<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\FinancialDataAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AdvancedAnalyticsReportService
{
    /**
     * Supported PDF report types and their document titles.
     *
     * @var array<string, string>
     */
    public const REPORT_TITLES = [
        'complete' => 'Comprehensive Advanced Analytics & Inventory Intelligence Report',
        'executive' => 'Executive Analytics & Performance Summary',
        'inventory' => 'Inventory Valuation & Stock Intelligence Report',
        'sales' => 'Sales, Revenue & Growth Analytics Report',
        'pricing' => 'Product Pricing & Discount Intelligence Report',
        'profitability' => 'Gross Profit & Margin Analysis Report',
        'reorder' => 'Low Stock & Urgent Reorder Planning Report',
        'variants' => 'Product Variants & Stock Distribution Report',
    ];

    private const STOCK_STATUS_LABELS = [
        'in_stock' => 'In Stock',
        'low_stock' => 'Low Stock',
        'out_of_stock' => 'Out of Stock',
        'overstock' => 'Overstocked',
    ];

    public function __construct(
        protected AdvancedAnalyticsService $analyticsService
    ) {}

    /**
     * Generate branded PDF report for download.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $options
     */
    public function generatePdf(array $filters, array $options = [], ?FinancialDataAccess $financialAccess = null): Response
    {
        $orientation = ($options['orientation'] ?? 'landscape') === 'portrait' ? 'portrait' : 'landscape';
        $options['orientation'] = $orientation;
        $viewData = $this->buildReportViewData($filters, $options, $financialAccess);

        $pdf = Pdf::loadView('admin.advanced-analytics.pdf-report', $viewData)
            ->setPaper('a4', $orientation)
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        // Register Bengali Hind Siliguri font
        InvoicePdfService::registerBengaliFont($pdf->getDomPDF());

        return $pdf->download(self::filenameFor($viewData['reportType']))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public static function filenameFor(string $reportType): string
    {
        return 'mamabazar-'.$reportType.'-report-'.now()->format('Y-m-d').'.pdf';
    }

    /**
     * Build the PDF view data. Cost and profit figures are included only when
     * the viewer may export them; profitability reports require that right.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function buildReportViewData(array $filters, array $options = [], ?FinancialDataAccess $financialAccess = null): array
    {
        $reportType = (string) ($options['report_type'] ?? 'complete');
        if (! array_key_exists($reportType, self::REPORT_TITLES)) {
            $reportType = 'complete';
        }
        $exportAccess = ($financialAccess ?? FinancialDataAccess::none())->forExport();

        if ($reportType === 'profitability' && ! $exportAccess->canViewProfitMargin) {
            abort(403, 'You do not have permission to export profit reports.');
        }

        $store = BusinessSettingService::forInvoice();
        $store['logo_base64'] = BusinessSettingService::logoBase64();

        // Fetch metrics using existing service
        $inventoryKpis = $this->analyticsService->getInventoryKpis($filters, $exportAccess);
        $salesKpis = $this->analyticsService->getSalesKpis($filters, $exportAccess);
        $chartsData = $this->analyticsService->getChartsData($filters, $exportAccess);

        // Fetch products based on report type limit (up to 150 for PDF to avoid memory exhaustion)
        $filtersForPdf = array_merge($filters, ['per_page' => 100, 'page' => 1]);
        if ($reportType === 'reorder') {
            $filtersForPdf['stock_status'] = 'low_stock';
        }
        $productsPaginator = $this->analyticsService->getProductStockTable($filtersForPdf, $exportAccess);
        $products = $productsPaginator->items();

        return [
            'reportTitle' => self::REPORT_TITLES[$reportType],
            'reportType' => $reportType,
            'store' => $store,
            'filters' => $filters,
            'appliedFilters' => $this->describeAppliedFilters($filtersForPdf),
            'inventoryKpis' => $inventoryKpis,
            'salesKpis' => $salesKpis,
            'chartsData' => $chartsData,
            'products' => $products,
            'financialAccess' => $exportAccess,
            'generatedAt' => now()->format('Y-m-d h:i A T'),
            'generatedBy' => auth()->user()?->name ?? 'Administrator',
            'options' => $options,
        ];
    }

    /**
     * Human-readable list of the non-default filters a report was generated with.
     *
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    private function describeAppliedFilters(array $filters): array
    {
        $applied = [];

        if (! empty($filters['category_id'])) {
            $applied[] = 'Category: '.(Category::whereKey($filters['category_id'])->value('name') ?? '#'.$filters['category_id']);
        }
        if (! empty($filters['brand_id'])) {
            $applied[] = 'Brand: '.(Brand::whereKey($filters['brand_id'])->value('name') ?? '#'.$filters['brand_id']);
        }
        if (! empty($filters['product_id'])) {
            $applied[] = 'Product: '.(Product::whereKey($filters['product_id'])->value('title') ?? '#'.$filters['product_id']);
        }
        if (isset(self::STOCK_STATUS_LABELS[$filters['stock_status'] ?? ''])) {
            $applied[] = 'Stock: '.self::STOCK_STATUS_LABELS[$filters['stock_status']];
        }
        if (! empty($filters['product_status']) && $filters['product_status'] !== 'all') {
            $applied[] = 'Catalog: '.ucfirst((string) $filters['product_status']);
        }
        if (($filters['search'] ?? '') !== '') {
            $applied[] = 'Search: “'.$filters['search'].'”';
        }

        return $applied;
    }
}
