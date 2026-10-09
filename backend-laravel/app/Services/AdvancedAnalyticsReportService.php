<?php

namespace App\Services;

use App\Support\FinancialDataAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AdvancedAnalyticsReportService
{
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
        $orientation = (string) ($options['orientation'] ?? 'landscape');
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

        $filename = 'MamaBazar_Analytics_Report_'.$viewData['reportType'].'_'.date('Ymd_His').'.pdf';

        return $pdf->download($filename);
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

        $titles = [
            'complete' => 'Comprehensive Advanced Analytics & Inventory Intelligence Report',
            'executive' => 'Executive Analytics & Performance Summary',
            'inventory' => 'Inventory Valuation & Stock Intelligence Report',
            'sales' => 'Sales, Revenue & Growth Analytics Report',
            'pricing' => 'Product Pricing & Discount Intelligence Report',
            'profitability' => 'Gross Profit & Margin Analysis Report',
            'reorder' => 'Low Stock & Urgent Reorder Planning Report',
            'variants' => 'Product Variants & Stock Distribution Report',
        ];

        return [
            'reportTitle' => $titles[$reportType] ?? 'Advanced Analytics Report',
            'reportType' => $reportType,
            'store' => $store,
            'filters' => $filters,
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
}
