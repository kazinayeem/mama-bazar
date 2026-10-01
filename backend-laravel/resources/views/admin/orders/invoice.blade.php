<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->invoice_number ?: $order->order_id }} — {{ $store['name'] ?? 'Mama Bazar' }}</title>
    <link rel="icon" type="image/png" href="{{ ($store['favicon_url'] ?? null) ?: '/brandlogo.png' }}">
    <style>
        @font-face {
            font-family: 'Inter';
            src: url('/fonts/inter-400-normal.woff2') format('woff2');
            font-weight: 400;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', 'Noto Sans Bengali', 'DejaVu Sans', Arial, sans-serif;
            background: #eef2ee;
            color: #1e293b;
            font-size: 13px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @page { size: A4; margin: 12mm 11mm; }
        .toolbar {
            max-width: 210mm;
            margin: 16px auto 12px;
            display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between;
            padding: 0 4px;
        }
        .toolbar .group { display: flex; flex-wrap: wrap; gap: 8px; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: 10px; font-size: 12px; font-weight: 700;
            text-decoration: none; border: 1px solid transparent; cursor: pointer;
        }
        .btn-dark { background: #0f4d2c; color: #fff; }
        .btn-outline { background: #fff; color: #0f4d2c; border-color: #cbd5c9; }
        .btn-orange { background: #f97316; color: #fff; }
        .btn:hover { opacity: .92; }
        .sheet {
            width: 210mm; max-width: calc(100% - 16px);
            margin: 0 auto 32px; background: #fff;
            border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;
        }
        .sheet-inner { padding: 32px 36px 28px; }
        .inv-header { display: table; width: 100%; padding-bottom: 18px; border-bottom: 3px solid #0f4d2c; }
        .inv-header > div { display: table-cell; vertical-align: top; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand img { height: 44px; width: 44px; object-fit: contain; }
        .brand-name { font-size: 22px; font-weight: 800; letter-spacing: -.5px; }
        .brand-name .g { color: #16a34a; } .brand-name .o { color: #f97316; }
        .brand-sub { font-size: 11px; color: #64748b; }
        .store-meta { font-size: 11px; color: #475569; margin-top: 8px; line-height: 1.6; }
        .inv-title { text-align: right; }
        .inv-title h1 { font-size: 28px; font-weight: 800; letter-spacing: 2px; color: #0f4d2c; }
        .inv-no { font-size: 13px; font-weight: 800; color: #15803d; margin-top: 2px; }
        .inv-date { font-size: 11px; color: #64748b; }
        .badge {
            display: inline-block; padding: 2px 10px; border-radius: 999px;
            font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
            background: #f1f5f9; color: #334155; margin-top: 6px;
        }
        .badge.paid { background: #dcfce7; color: #15803d; }
        .badge.unpaid { background: #fef3c7; color: #b45309; }
        .two-col { display: table; width: 100%; margin: 18px 0 6px; }
        .two-col > div { display: table-cell; width: 50%; vertical-align: top; }
        .box-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px; }
        .cust-name { font-size: 14px; font-weight: 800; }
        .cust-lines { font-size: 12px; color: #475569; margin-top: 2px; }
        .meta-table { width: 100%; font-size: 11.5px; border-collapse: collapse; }
        .meta-table td { padding: 3px 0; vertical-align: top; }
        .meta-table td:first-child { color: #94a3b8; font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: .5px; width: 130px; }
        .meta-table td:last-child { font-weight: 700; text-align: right; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 12px; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; }
        table.items th {
            background: #0f4d2c; color: #fff; text-align: left;
            padding: 9px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: .6px;
        }
        table.items th.num, table.items td.num { text-align: right; }
        table.items th.c, table.items td.c { text-align: center; }
        table.items td { padding: 9px 10px; border-bottom: 1px solid #eef2ee; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #f8faf8; }
        .p-title { font-weight: 700; }
        .p-sub { font-size: 11px; color: #64748b; }
        table.totals { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 12.5px; }
        table.totals td { padding: 5px 10px; }
        table.totals td:first-child { text-align: right; color: #475569; width: 75%; }
        table.totals td:last-child { text-align: right; font-weight: 800; width: 25%; }
        table.totals tr.grand td { font-size: 16px; color: #0f4d2c; border-top: 2px solid #0f4d2c; padding-top: 10px; }
        table.totals tr.grand td:last-child { color: #ea580c; }
        .notes { margin-top: 16px; display: table; width: 100%; font-size: 11px; color: #475569; }
        .notes > div { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
        .notes h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #0f4d2c; margin-bottom: 4px; }
        .footer { margin-top: 20px; padding-top: 14px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 11px; color: #64748b; }
        .footer .thanks { font-size: 13px; font-weight: 800; color: #0f4d2c; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { width: auto; max-width: none; margin: 0; border: none; border-radius: 0; }
            .sheet-inner { padding: 0; }
        }
        @media (max-width: 700px) {
            .toolbar { margin: 10px 8px; }
            .sheet-inner { padding: 20px 16px; }
            .inv-header > div, .two-col > div, .notes > div { display: block; width: 100%; }
            .inv-title { text-align: left; margin-top: 12px; }
            table.items { font-size: 11px; }
            table.items th:nth-child(2), table.items td:nth-child(2) { display: none; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div class="group">
        <a class="btn btn-outline" href="{{ route('admin.orders.show', $order->id) }}">&larr; Order</a>
        <a class="btn btn-outline" href="{{ route('admin.orders.index') }}">All Orders</a>
    </div>
    <div class="group">
        <button class="btn btn-outline" onclick="copyOrderInfo()">Copy Info</button>
        <a class="btn btn-outline" target="_blank" href="{{ route('admin.orders.packing-slip', $order->id) }}">Packing Slip</a>
        <a class="btn btn-orange" href="{{ route('admin.orders.invoice.download', $order->id) }}">Download PDF</a>
        <button class="btn btn-dark" onclick="window.print()">Print Invoice</button>
    </div>
</div>

<div class="sheet">
<div class="sheet-inner">

    <div class="inv-header">
        <div>
            <div class="brand">
                <img src="{{ $store['logo_url'] ?: '/brandlogo.png' }}" alt="{{ $store['name'] }}">
                <div>
                    <div class="brand-name"><span class="g">{{ $store['name_first_part'] ?? 'Mama' }}</span><span class="o">{{ $store['name_second_part'] ?? 'Bazar' }}</span></div>
                    <div class="brand-sub">{{ $store['tagline'] }}</div>
                </div>
            </div>
            <div class="store-meta">
                {{ $store['address'] }}<br>
                Phone: {{ $store['phone'] }} &nbsp;·&nbsp; Email: {{ $store['email'] }}<br>
                Web: {{ $store['website'] }}
                @if($store['tax_id'])<br>Reg / Tax: {{ $store['tax_id'] }}@endif
            </div>
        </div>
        <div class="inv-title">
            <h1>INVOICE</h1>
            <div class="inv-no">{{ $order->invoice_number ?: $order->order_id }}</div>
            <div class="inv-date">Order {{ $order->order_id }} · {{ $order->created_at?->format('F d, Y h:i A') }}</div>
            <div><span class="badge">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span></div>
        </div>
    </div>

    <div class="two-col">
        <div>
            <div class="box-label">Billed / Ship To</div>
            <div class="cust-name">{{ $order->customer_name }}</div>
            <div class="cust-lines">
                {{ $order->address }}@if($order->apartment), {{ $order->apartment }}@endif<br>
                @if($order->area){{ $order->area }}, @endif
                @if($order->upazila){{ $order->upazila }}, @endif
                {{ $order->district ?: $order->division }}@if($order->postal_code) — {{ $order->postal_code }}@endif<br>
                Phone: <strong>{{ $order->phone }}</strong>
                @if($order->display_alternative_phone)<br>Alternative Phone: {{ $order->display_alternative_phone }}@endif
                @if($order->email)<br>Email: {{ $order->email }}@endif
                <br><span style="font-size:11px;color:#94a3b8;">{{ $order->user_id ? 'Registered customer' : 'Guest checkout' }}</span>
            </div>
        </div>
        <div>
            <table class="meta-table">
                <tr><td>Invoice No</td><td>{{ $order->invoice_number ?: $order->order_id }}</td></tr>
                <tr><td>Order Date</td><td>{{ $order->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><td>Payment</td><td style="text-transform:uppercase;">{{ $order->payment_method }}</td></tr>
                <tr><td>Pay Status</td><td>{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</td></tr>
                <tr><td>Shipping</td><td>{{ $order->shipping_method_name ?: 'Standard' }}</td></tr>
                @if($order->transaction_id)<tr><td>Trx ID</td><td style="font-family:monospace;">{{ $order->transaction_id }}</td></tr>@endif
            </table>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:34%;">Product</th>
                <th style="width:14%;">SKU</th>
                <th style="width:16%;">Variant</th>
                <th class="c" style="width:7%;">Qty</th>
                <th class="num" style="width:14%;">Unit Price</th>
                <th class="num" style="width:15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
            <tr>
                <td>
                    <div class="p-title">{{ $item->product_title ?: ($item->product?->title ?: 'Product') }}</div>
                    @if($item->size || $item->color)
                    <div class="p-sub">{{ trim(($item->size ?: '') . ($item->size && $item->color ? ' / ' : '') . ($item->color ?: '')) }}</div>
                    @endif
                </td>
                <td style="font-family:monospace;font-size:11px;">{{ $item->product_sku ?: ($item->product?->sku ?: 'MB-' . $item->product_id) }}</td>
                <td class="p-sub">{{ $item->variant_name ?: ($item->variant?->name ?: '—') }}</td>
                <td class="c"><strong>{{ $item->quantity }}</strong></td>
                <td class="num">৳{{ number_format($item->price, 0) }}</td>
                <td class="num"><strong>৳{{ number_format($item->price * $item->quantity, 0) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td>৳{{ number_format($order->subtotal, 0) }}</td></tr>
        @if((float) $order->discount > 0)
        <tr><td>Discount @if($order->coupon_code) ({{ $order->coupon_code }}) @endif</td><td>− ৳{{ number_format($order->discount, 0) }}</td></tr>
        @endif
        <tr><td>Delivery ({{ $order->shipping_method_name ?: 'Standard' }})</td><td>৳{{ number_format($order->shipping_cost, 0) }}</td></tr>
        @if((float) $order->tax > 0)
        <tr><td>VAT / Tax</td><td>৳{{ number_format($order->tax, 0) }}</td></tr>
        @endif
        <tr class="grand"><td>Total Payable</td><td>৳{{ number_format($order->total_price, 0) }}</td></tr>
    </table>

    <div class="notes">
        <div>
            <h4>Payment Instructions</h4>
            @if(strtolower($order->payment_method) === 'cod')
                Please pay ৳{{ number_format($order->total_price, 0) }} in cash on delivery.
            @else
                @if($order->transaction_id)Trx ID: {{ $order->transaction_id }}<br>@endif
                @if($order->sender_number)Sender: {{ $order->sender_number }}<br>@endif
                Payment method: {{ strtoupper($order->payment_method) }} ({{ $order->payment_status }}).
            @endif
            @if($order->order_note)<br>Note: {{ $order->order_note }}@endif
        </div>
        <div>
            <h4>Return & Support</h4>
            {{ $store['return_policy'] }}<br>
            Helpline: {{ $store['phone'] }} · {{ $store['email'] }}
        </div>
    </div>

    <div class="footer">
        <div class="thanks">Thank you for shopping with {{ $store['name'] }}!</div>
        <div>This is a system-generated invoice. No signature required.</div>
        @include('pdf.branding-footer')
    </div>

</div>
</div>

<script>
function copyOrderInfo() {
    const text = [
        'Order: {{ $order->order_id }}',
        'Invoice: {{ $order->invoice_number ?: $order->order_id }}',
        'Customer: {{ addslashes($order->customer_name) }} ({{ $order->phone }})',
        'Address: {{ addslashes($order->address) }}, {{ addslashes($order->district ?? '') }}',
        'Items: @foreach($order->items as $it){{ addslashes($it->product_title ?: ($it->product?->title ?: 'Item')) }} x{{ $it->quantity }} (৳{{ number_format($it->price * $it->quantity, 0) }}); @endforeach',
        'Total: ৳{{ number_format($order->total_price, 0) }} [{{ strtoupper($order->payment_method) }}/{{ $order->payment_status }}]',
    ].join('\n');
    navigator.clipboard?.writeText(text).then(() => alert('Order information copied.')).catch(() => alert(text));
}
</script>

</body>
</html>
