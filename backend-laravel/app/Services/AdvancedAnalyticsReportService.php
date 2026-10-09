<?php

namespace App\Services;

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
    public function generatePdf(array $filters, array $options = []): Response
    {
        $reportType = (string) ($options['report_type'] ?? 'complete');
        $orientation = (string) ($options['orientation'] ?? 'landscape');

        $store = BusinessSettingService::forInvoice();
        $store['logo_base64'] = BusinessSettingService::logoBase64();

        // Fetch metrics using existing service
        $inventoryKpis = $this->analyticsService->getInventoryKpis($filters);
        $salesKpis = $this->analyticsService->getSalesKpis($filters);
        $chartsData = $this->analyticsService->getChartsData($filters);

        // Fetch products based on report type limit (up to 150 for PDF to avoid memory exhaustion)
        $filtersForPdf = array_merge($filters, ['per_page' => 100, 'page' => 1]);
        if ($reportType === 'reorder') {
            $filtersForPdf['stock_status'] = 'low_stock';
        }
        $productsPaginator = $this->analyticsService->getProductStockTable($filtersForPdf);
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

        $reportTitle = $titles[$reportType] ?? 'Advanced Analytics Report';

        $pdf = Pdf::loadView('admin.advanced-analytics.pdf-report', [
            'reportTitle' => $reportTitle,
            'reportType' => $reportType,
            'store' => $store,
            'filters' => $filters,
            'inventoryKpis' => $inventoryKpis,
            'salesKpis' => $salesKpis,
            'chartsData' => $chartsData,
            'products' => $products,
            'generatedAt' => now()->format('Y-m-d h:i A T'),
            'generatedBy' => auth()->user()?->name ?? 'Administrator',
            'options' => $options,
        ])
            ->setPaper('a4', $orientation)
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        // Register Bengali Hind Siliguri font
        InvoicePdfService::registerBengaliFont($pdf->getDomPDF());

        $filename = 'MamaBazar_Analytics_Report_'.$reportType.'_'.date('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }
}
