<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{{ $reportTitle }} — MamaBazar</title>
<style>
    @page {
        size: A4 {{ $options['orientation'] ?? 'landscape' }};
        margin: 10mm 10mm 12mm 10mm;
    }
    body {
        font-family: 'Hind Siliguri', 'DejaVu Sans', sans-serif;
        font-size: 10px;
        color: #1e293b;
        line-height: 1.4;
        margin: 0;
        padding: 0;
    }
    .header-table {
        width: 100%;
        border-bottom: 2px solid #0f4d2c;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .header-table td {
        vertical-align: top;
    }
    .brand-title {
        font-size: 18px;
        font-weight: bold;
        color: #0f4d2c;
    }
    .brand-meta {
        font-size: 9px;
        color: #64748b;
        margin-top: 2px;
    }
    .report-title-box {
        text-align: right;
    }
    .report-title {
        font-size: 16px;
        font-weight: bold;
        color: #0f4d2c;
        margin: 0;
    }
    .report-badge {
        display: inline-block;
        background: #f1f5f9;
        color: #334155;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 8.5px;
        font-weight: 600;
        margin-top: 3px;
    }
    .section-title {
        font-size: 11px;
        font-weight: bold;
        color: #0f4d2c;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #cbd5e1;
        padding-bottom: 4px;
        margin-top: 14px;
        margin-bottom: 6px;
    }
    .kpi-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .kpi-table td {
        padding: 6px 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        vertical-align: top;
    }
    .kpi-label {
        font-size: 8.5px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 600;
    }
    .kpi-value {
        font-size: 14px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 2px;
    }
    .kpi-sub {
        font-size: 8px;
        color: #94a3b8;
        margin-top: 1px;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
        margin-bottom: 12px;
        font-size: 9px;
    }
    .data-table thead th {
        background: #0f4d2c;
        color: #ffffff;
        padding: 5px 6px;
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        text-align: left;
    }
    .data-table tbody td {
        padding: 5px 6px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .data-table tbody tr:nth-child(even) td {
        background: #f8fafc;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-emerald { color: #15803d; font-weight: 600; }
    .text-amber { color: #b45309; font-weight: 600; }
    .text-rose { color: #be123c; font-weight: 600; }
    .badge {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 8px;
        font-weight: bold;
    }
    .badge-in { background: #dcfce7; color: #15803d; }
    .badge-low { background: #fef3c7; color: #b45309; }
    .badge-out { background: #fee2e2; color: #b91c1c; }
    .badge-over { background: #e0e7ff; color: #4338ca; }
    .notes-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 3px solid #0f4d2c;
        padding: 6px 10px;
        font-size: 8px;
        color: #475569;
        margin-top: 10px;
        line-height: 1.4;
    }
    .footer {
        position: fixed;
        bottom: -8mm;
        left: 0;
        right: 0;
        font-size: 8px;
        color: #94a3b8;
        border-top: 1px solid #e2e8f0;
        padding-top: 4px;
        text-align: center;
    }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
</style>
</head>
<body>

<div class="footer">
    MamaBazar Enterprise Analytics & Inventory Intelligence — Generated on {{ $generatedAt }} by {{ $generatedBy }}
</div>

<table class="header-table">
    <tr>
        <td style="width: 55%;">
            @if(!empty($store['logo_base64']))
                <img src="{{ $store['logo_base64'] }}" style="height: 32px; width: 32px; vertical-align: middle; margin-right: 6px;" alt="">
            @endif
            <span class="brand-title">{{ $store['name'] ?? 'MamaBazar' }}</span>
            <div class="brand-meta">
                {{ $store['address'] ?? 'Online E-Commerce Platform' }} | Phone: {{ $store['phone'] ?? '+880' }} | Email: {{ $store['email'] ?? 'support@mama-bazar.com' }}
            </div>
        </td>
        <td class="report-title-box" style="width: 45%;">
            <h1 class="report-title">{{ $reportTitle }}</h1>
            <div class="report-badge">
                Period: {{ $filters['start_date']->format('d M, Y') }} — {{ $filters['end_date']->format('d M, Y') }} ({{ $filters['preset'] }})
            </div>
            <div style="font-size: 8px; color: #64748b; margin-top: 3px;">
                Generated: {{ $generatedAt }} | Admin: {{ $generatedBy }}
            </div>
        </td>
    </tr>
</table>

{{-- Section: Executive KPI Summary --}}
<div class="section-title">1. Executive Overview & Inventory Valuation</div>
<table class="kpi-table">
    <tr>
        <td style="width: 25%;">
            <div class="kpi-label">Active Products & Variants</div>
            <div class="kpi-value">{{ number_format($inventoryKpis['active_products']) }} / {{ number_format($inventoryKpis['total_products']) }}</div>
            <div class="kpi-sub">Variants: {{ number_format($inventoryKpis['active_variants']) }} active</div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-label">Units in Stock</div>
            <div class="kpi-value">{{ number_format($inventoryKpis['total_units_in_stock']) }}</div>
            <div class="kpi-sub">
                Low: <span class="text-amber">{{ $inventoryKpis['low_stock_count'] }}</span> | 
                Out: <span class="text-rose">{{ $inventoryKpis['out_of_stock_count'] }}</span>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-label">Potential Retail Valuation</div>
            <div class="kpi-value">৳{{ number_format($inventoryKpis['retail_valuation'], 0) }}</div>
            <div class="kpi-sub">Based on present selling prices</div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-label">Inventory Valuation at Cost</div>
            <div class="kpi-value">
                @if($inventoryKpis['cost_valuation'] !== null)
                    ৳{{ number_format($inventoryKpis['cost_valuation'], 0) }}
                @else
                    <span style="font-size: 10px; color: #94a3b8;">Cost data unavailable</span>
                @endif
            </div>
            <div class="kpi-sub">
                @if($inventoryKpis['unrealized_margin'] !== null)
                    Est. Margin: <span class="text-emerald">{{ $inventoryKpis['unrealized_margin'] }}%</span>
                @else
                    Coverage: {{ $inventoryKpis['cost_coverage_pct'] }}% of products
                @endif
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="kpi-label">Gross Sales (Period)</div>
            <div class="kpi-value">৳{{ number_format($salesKpis['gross_sales'], 0) }}</div>
            <div class="kpi-sub">
                Growth: 
                @if($salesKpis['gross_sales_growth'] !== null)
                    <span class="{{ $salesKpis['gross_sales_growth'] >= 0 ? 'text-emerald' : 'text-rose' }}">{{ $salesKpis['gross_sales_growth'] > 0 ? '+' : '' }}{{ $salesKpis['gross_sales_growth'] }}%</span>
                @else
                    —
                @endif
            </div>
        </td>
        <td>
            <div class="kpi-label">Net Sales (Valid Orders)</div>
            <div class="kpi-value">৳{{ number_format($salesKpis['net_sales'], 0) }}</div>
            <div class="kpi-sub">
                Growth: 
                @if($salesKpis['sales_growth'] !== null)
                    <span class="{{ $salesKpis['sales_growth'] >= 0 ? 'text-emerald' : 'text-rose' }}">{{ $salesKpis['sales_growth'] > 0 ? '+' : '' }}{{ $salesKpis['sales_growth'] }}%</span>
                @else
                    —
                @endif
            </div>
        </td>
        <td>
            <div class="kpi-label">Orders & Units Sold</div>
            <div class="kpi-value">{{ number_format($salesKpis['orders_count']) }} / {{ number_format($salesKpis['units_sold']) }}</div>
            <div class="kpi-sub">AOV: ৳{{ number_format($salesKpis['avg_order_value'], 0) }}</div>
        </td>
        <td>
            <div class="kpi-label">Gross Profit & Margin</div>
            <div class="kpi-value">
                @if($salesKpis['gross_profit'] !== null)
                    ৳{{ number_format($salesKpis['gross_profit'], 0) }}
                    <span style="font-size: 10px; color: #15803d;">({{ $salesKpis['gross_margin_pct'] }}%)</span>
                @else
                    <span style="font-size: 10px; color: #94a3b8;">Cost data unavailable</span>
                @endif
            </div>
            <div class="kpi-sub">
                @if($salesKpis['profit_status'] === 'partial')
                    Partial: {{ $salesKpis['items_with_cost'] }} of {{ $salesKpis['total_items_sold_count'] }} items
                @elseif($salesKpis['profit_status'] === 'exact')
                    100% item cost verified
                @else
                    COGS missing on products
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- Section: Category Breakdown --}}
@if(!empty($chartsData['category_inventory']))
<div class="section-title">2. Category Inventory & Valuation Breakdown</div>
<table class="data-table">
    <thead>
        <tr>
            <th>Category Name</th>
            <th class="text-center">Products</th>
            <th class="text-center">Current Stock</th>
            <th class="text-right">Potential Retail Value</th>
            <th class="text-right">Inventory Cost Value</th>
            <th class="text-right">Est. Margin</th>
        </tr>
    </thead>
    <tbody>
        @foreach($chartsData['category_inventory'] as $cat)
            @php
                $margin = ($cat['retail_value'] > 0 && $cat['cost_value'] > 0)
                    ? round((($cat['retail_value'] - $cat['cost_value']) / $cat['retail_value']) * 100, 1)
                    : null;
            @endphp
            <tr>
                <td><strong>{{ $cat['category'] }}</strong></td>
                <td class="text-center">{{ number_format($cat['products']) }}</td>
                <td class="text-center font-bold">{{ number_format($cat['stock']) }}</td>
                <td class="text-right">৳{{ number_format($cat['retail_value'], 0) }}</td>
                <td class="text-right">
                    {{ $cat['cost_value'] > 0 ? '৳'.number_format($cat['cost_value'], 0) : '—' }}
                </td>
                <td class="text-right">
                    {{ $margin !== null ? $margin.'%' : '—' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Section: Product and Stock Intelligence Table --}}
<div class="section-title">3. Product Performance & Stock Status ({{ count($products) }} Records)</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 28%;">Product Name</th>
            <th>SKU</th>
            <th>Category</th>
            <th class="text-center">Stock</th>
            <th>Status</th>
            <th class="text-right">Selling Price</th>
            <th class="text-right">Cost Price</th>
            <th class="text-center">Units Sold</th>
            <th class="text-right">Revenue</th>
            <th class="text-right">Profit</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
            @php
                $stock = (int) $p->stock;
                $sellPrice = (float) ($p->sale_price ?: $p->price);
                $costPrice = (float) $p->cost_price;
                $units = (int) $p->period_units_sold;
                $revenue = (float) $p->period_revenue;
                $profit = ($costPrice > 0) ? round($revenue - ($units * $costPrice), 0) : null;

                $badgeClass = 'badge-in';
                $badgeText = 'In Stock';
                if ($stock <= 0) {
                    $badgeClass = 'badge-out';
                    $badgeText = 'Out of Stock';
                } elseif ($stock <= (int) ($p->low_stock_alert ?? 10)) {
                    $badgeClass = 'badge-low';
                    $badgeText = 'Low Stock';
                } elseif ($stock >= 100) {
                    $badgeClass = 'badge-over';
                    $badgeText = 'Overstocked';
                }
            @endphp
            <tr>
                <td>
                    <strong>{{ $p->title }}</strong>
                    @if($p->variants->isNotEmpty())
                        <div style="font-size: 7.5px; color: #64748b;">{{ $p->variants->count() }} Variants</div>
                    @endif
                </td>
                <td style="font-family: monospace; font-size: 8px;">{{ $p->sku ?: '—' }}</td>
                <td>{{ $p->category?->name ?? 'Uncategorized' }}</td>
                <td class="text-center"><strong>{{ $stock }}</strong></td>
                <td><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                <td class="text-right">৳{{ number_format($sellPrice, 0) }}</td>
                <td class="text-right">{{ $costPrice > 0 ? '৳'.number_format($costPrice, 0) : '—' }}</td>
                <td class="text-center">{{ number_format($units) }}</td>
                <td class="text-right">৳{{ number_format($revenue, 0) }}</td>
                <td class="text-right">
                    @if($profit !== null)
                        <span class="{{ $profit >= 0 ? 'text-emerald' : 'text-rose' }}">৳{{ number_format($profit, 0) }}</span>
                    @else
                        <span style="color: #94a3b8;">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 16px; color: #94a3b8;">
                    No product records matching current filter selection.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Notes Box --}}
<div class="notes-box">
    <strong>Data & Business Logic Notes:</strong><br>
    1. <em>Inventory Snapshot:</em> Current stock quantities and inventory valuations reflect the present system state as of {{ $generatedAt }} and are not reconstructed historical records.<br>
    2. <em>Net Sales & Orders:</em> Net sales include valid orders only and exclude cancelled, refunded, and returned orders.<br>
    3. <em>Profitability & Cost Data:</em> Gross profit and cost valuation are computed strictly from confirmed purchase/cost prices recorded on products. Items lacking recorded cost are labeled as unavailable rather than assuming zero cost.
</div>

</body>
</html>
