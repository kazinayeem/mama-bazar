<?php

namespace App\Services;

use App\Models\User;
use App\Support\PdfBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerReportService
{
    /**
     * Export customer list as CSV.
     */
    public static function exportListCsv(iterable $customers): StreamedResponse
    {
        $filename = 'mama_bazar_customers_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Customer ID',
            'Name',
            'Phone',
            'Email',
            'Status',
            'Registration Date',
            'Last Login',
            'Total Orders',
            'Total Spent (BDT)',
            'Average Order Value (BDT)',
            'Last Order Date',
            'Shipping Area',
            'Shipping Address',
        ];

        return response()->stream(function () use ($columns, $customers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, $columns);

            foreach ($customers as $c) {
                $totalSpent = (float) ($c->total_spent ?? 0);
                $validOrders = (int) ($c->valid_orders_count ?? 0);
                $aov = $validOrders > 0 ? round($totalSpent / $validOrders, 2) : 0;

                fputcsv($handle, [
                    $c->id,
                    $c->name,
                    $c->phone,
                    $c->email ?: 'N/A',
                    ucfirst($c->status ?? 'active'),
                    $c->created_at ? $c->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $c->last_login_at ? $c->last_login_at->format('Y-m-d H:i:s') : 'Never',
                    $c->orders_count ?? 0,
                    number_format($totalSpent, 2, '.', ''),
                    number_format($aov, 2, '.', ''),
                    $c->last_order_at ? Carbon::parse($c->last_order_at)->format('Y-m-d H:i:s') : 'N/A',
                    $c->shipping_area ?: 'N/A',
                    $c->shipping_address ?: 'N/A',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export customer list as PDF.
     */
    public static function exportListPdf(iterable $customers, array $filters = [])
    {
        $filename = 'mama_bazar_customers_'.date('Ymd_His').'.pdf';
        $store = BusinessSettingService::forInvoice();

        $pdf = Pdf::loadView('admin.reports.customers-list-pdf', [
            'title' => 'Customer Accounts Directory & Financial Summary',
            'customers' => $customers,
            'filters' => $filters,
            'store' => $store,
            'branding' => PdfBranding::attribution(),
            'generatedAt' => now()->format('Y-m-d H:i:s T'),
            'generatedBy' => auth()->user()?->name ?? 'Administrator',
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Export individual Customer 360 profile as PDF.
     */
    public static function exportCustomerPdf(User $customer, array $metrics, $orders, $payments, $addresses, $notes)
    {
        $filename = 'mama_bazar_customer_'.$customer->id.'_360_'.date('Ymd_His').'.pdf';
        $store = BusinessSettingService::forInvoice();

        $pdf = Pdf::loadView('admin.reports.customer-360-pdf', [
            'title' => 'Customer 360° Profile & Account Dossier',
            'customer' => $customer,
            'metrics' => $metrics,
            'orders' => $orders,
            'payments' => $payments,
            'addresses' => $addresses,
            'notes' => $notes,
            'store' => $store,
            'branding' => PdfBranding::attribution(),
            'generatedAt' => now()->format('Y-m-d H:i:s T'),
            'generatedBy' => auth()->user()?->name ?? 'Administrator',
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Export individual customer order & spend history as CSV.
     */
    public static function exportCustomerCsv(User $customer, array $metrics, $orders): StreamedResponse
    {
        $filename = 'mama_bazar_customer_'.$customer->id.'_orders_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($customer, $metrics, $orders) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Section 1: Customer Profile Overview
            fputcsv($handle, ['CUSTOMER PROFILE 360 OVERVIEW']);
            fputcsv($handle, ['Customer ID', $customer->id]);
            fputcsv($handle, ['Name', $customer->name]);
            fputcsv($handle, ['Phone', $customer->phone]);
            fputcsv($handle, ['Email', $customer->email ?: 'N/A']);
            fputcsv($handle, ['Status', ucfirst($customer->status ?? 'active')]);
            fputcsv($handle, ['Registered', $customer->created_at ? $customer->created_at->format('Y-m-d H:i:s') : 'N/A']);
            fputcsv($handle, ['Total Orders', $metrics['totalOrders'] ?? 0]);
            fputcsv($handle, ['Total Spent (BDT)', number_format($metrics['totalSpent'] ?? 0, 2, '.', '')]);
            fputcsv($handle, ['Average Order Value (BDT)', number_format($metrics['aov'] ?? 0, 2, '.', '')]);
            fputcsv($handle, ['Pending Orders', $metrics['pendingOrders'] ?? 0]);
            fputcsv($handle, ['Completed Orders', $metrics['completedOrders'] ?? 0]);
            fputcsv($handle, ['Cancelled Orders', $metrics['cancelledOrders'] ?? 0]);
            fputcsv($handle, []);

            // Section 2: Order History
            fputcsv($handle, ['ORDER HISTORY']);
            fputcsv($handle, [
                'Order ID',
                'Invoice #',
                'Date',
                'Items Count',
                'Subtotal (BDT)',
                'Delivery (BDT)',
                'Discount (BDT)',
                'Total Price (BDT)',
                'Payment Method',
                'Payment Status',
                'Order Status',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_id,
                    $order->invoice_number ?: 'N/A',
                    $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $order->items ? $order->items->sum('quantity') : 0,
                    number_format((float) $order->subtotal, 2, '.', ''),
                    number_format((float) $order->shipping_cost, 2, '.', ''),
                    number_format((float) $order->discount, 2, '.', ''),
                    number_format((float) $order->total_price, 2, '.', ''),
                    strtoupper($order->payment_method ?? 'COD'),
                    ucfirst($order->payment_status ?? 'pending'),
                    ucfirst($order->status ?? 'pending'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
