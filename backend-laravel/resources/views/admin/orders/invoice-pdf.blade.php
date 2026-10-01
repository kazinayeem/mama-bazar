<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { size: A4; margin: 12mm 11mm; }
    {{-- Hind Siliguri (Bengali) is registered programmatically in AdminOrderWebController::registerBengaliFont;
         do NOT add @font-face here: an unresolvable URL would shadow the registered font at render time. --}}
    body { font-family: 'Hind Siliguri', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.45; }
    .header { width: 100%; border-bottom: 3px solid #0f4d2c; padding-bottom: 10px; }
    .header td { vertical-align: top; }
    .brand { font-size: 20px; font-weight: bold; }
    .meta { font-size: 10px; color: #475569; }
    .title { text-align: right; }
    .title h1 { font-size: 24px; color: #0f4d2c; letter-spacing: 2px; margin: 0; }
    .invno { font-size: 12px; font-weight: bold; color: #15803d; }
    .cols { width: 100%; margin-top: 12px; }
    .cols td { vertical-align: top; width: 50%; }
    .label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10.5px; }
    table.items th { background: #0f4d2c; color: #fff; padding: 7px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
    table.items td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
    table.totals { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
    table.totals td { padding: 4px 8px; }
    table.totals .r { text-align: right; }
    .grand td { font-size: 14px; font-weight: bold; color: #0f4d2c; border-top: 2px solid #0f4d2c; padding-top: 8px; }
    .foot { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 10px; text-align: center; font-size: 10px; color: #64748b; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
</style>
</head>
<body>

<table class="header">
    <tr>
        <td>
            @if(!empty($store['logo_base64']))
                <img src="{{ $store['logo_base64'] }}" style="height:40px;width:40px;" alt="">
            @endif
            <div class="brand">{{ $store['name'] }}</div>
            <div class="meta">{{ $store['tagline'] }}<br>
            {{ $store['address'] }}<br>
            Phone: {{ $store['phone'] }} | Email: {{ $store['email'] }}<br>
            Web: {{ $store['website'] }}@if($store['tax_id']) | Reg: {{ $store['tax_id'] }}@endif</div>
        </td>
        <td class="title">
            <h1>INVOICE</h1>
            <div class="invno">{{ $order->invoice_number ?: $order->order_id }}</div>
            <div class="meta">Order {{ $order->order_id }}<br>{{ $order->created_at?->format('F d, Y h:i A') }}<br>Status: {{ ucfirst(str_replace('_', ' ', $order->status)) }}</div>
        </td>
    </tr>
</table>

<table class="cols">
    <tr>
        <td>
            <div class="label">Billed / Ship To</div>
            <strong>{{ $order->customer_name }}</strong><br>
            {{ $order->address }}@if($order->apartment), {{ $order->apartment }}@endif<br>
            @if($order->area){{ $order->area }},@endif @if($order->upazila){{ $order->upazila }},@endif {{ $order->district }}@if($order->postal_code) — {{ $order->postal_code }}@endif<br>
            Phone: {{ $order->phone }}<br>
            @if($order->display_alternative_phone)Alternative Phone: {{ $order->display_alternative_phone }}<br>@endif
            @if($order->email)Email: {{ $order->email }}<br>@endif
            <span style="color:#94a3b8;">{{ $order->user_id ? 'Registered' : 'Guest' }}</span>
        </td>
        <td style="text-align:right;">
            <div class="label">Payment &amp; Shipping</div>
            Payment: <strong>{{ strtoupper($order->payment_method) }}</strong><br>
            Pay status: {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}<br>
            Shipping: {{ $order->shipping_method_name ?: 'Standard' }}<br>
            @if($order->transaction_id)Trx: {{ $order->transaction_id }}<br>@endif
            @if($order->sender_number)Sender: {{ $order->sender_number }}<br>@endif
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr><th>Product</th><th>SKU</th><th>Variant</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit</th><th style="text-align:right;">Total</th></tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td><strong>{{ $item->product_title ?: ($item->product?->title ?: 'Product') }}</strong>@if($item->size || $item->color)<br><span style="color:#64748b;">{{ trim(($item->size ?: '') . ' / ' . ($item->color ?: ''), ' /') }}</span>@endif</td>
            <td>{{ $item->product_sku ?: 'MB-' . $item->product_id }}</td>
            <td>{{ $item->variant_name ?: ($item->variant?->name ?: '-') }}</td>
            <td style="text-align:center;">{{ $item->quantity }}</td>
            <td style="text-align:right;">Tk {{ number_format($item->price, 0) }}</td>
            <td style="text-align:right;"><strong>Tk {{ number_format($item->price * $item->quantity, 0) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td class="r" style="width:78%;">Subtotal</td><td class="r"><strong>Tk {{ number_format($order->subtotal, 0) }}</strong></td></tr>
    @if((float) $order->discount > 0)
    <tr><td class="r">Discount @if($order->coupon_code)({{ $order->coupon_code }})@endif</td><td class="r">- Tk {{ number_format($order->discount, 0) }}</td></tr>
    @endif
    <tr><td class="r">Delivery</td><td class="r">Tk {{ number_format($order->shipping_cost, 0) }}</td></tr>
    @if((float) $order->tax > 0)
    <tr><td class="r">VAT / Tax</td><td class="r">Tk {{ number_format($order->tax, 0) }}</td></tr>
    @endif
    <tr class="grand"><td class="r">Total Payable</td><td class="r">Tk {{ number_format($order->total_price, 0) }}</td></tr>
</table>

<div class="foot">
    <strong>Thank you for shopping with {{ $store['name'] }}!</strong><br>
    {{ $store['return_policy'] }}<br>
    Helpline: {{ $store['phone'] }} | {{ $store['email'] }}
</div>

@include('pdf.branding-footer')

</body>
</html>
