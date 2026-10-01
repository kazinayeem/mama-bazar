<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Packing Slip {{ $order->order_id }} — {{ $store['name'] ?? 'Mama Bazar' }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, 'Noto Sans Bengali', sans-serif; background: #eef2ee; color: #1e293b; font-size: 14px; }
    @page { size: A4; margin: 12mm; }
    .toolbar { max-width: 210mm; margin: 16px auto 12px; display: flex; gap: 8px; justify-content: space-between; }
    .btn { padding: 9px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; border: 1px solid #cbd5c9; background: #fff; color: #0f4d2c; cursor: pointer; }
    .btn-dark { background: #0f4d2c; color: #fff; border-color: #0f4d2c; }
    .sheet { width: 210mm; max-width: calc(100% - 16px); margin: 0 auto 32px; background: #fff; border: 1px dashed #94a3b8; border-radius: 6px; padding: 30px 34px; }
    h1 { font-size: 24px; letter-spacing: 2px; }
    .addr { margin: 16px 0; padding: 16px; border: 2px solid #0f4d2c; border-radius: 8px; font-size: 16px; line-height: 1.7; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
    th { background: #0f4d2c; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
    td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
    tr { page-break-inside: avoid; } thead { display: table-header-group; }
    .check { width: 26px; height: 26px; border: 2px solid #94a3b8; border-radius: 6px; display: inline-block; }
    @media print { body { background: #fff; } .toolbar { display: none; } .sheet { width: auto; max-width: none; margin: 0; border: 2px dashed #000; } }
</style>
</head>
<body>
<div class="toolbar">
    <a class="btn" href="{{ route('admin.orders.show', $order->id) }}">&larr; Order</a>
    <div style="display:flex;gap:8px;">
        <a class="btn" href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank">Invoice</a>
        <button class="btn btn-dark" onclick="window.print()">Print Slip</button>
    </div>
</div>
<div class="sheet">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div>
            <h1>PACKING SLIP</h1>
            <div style="color:#15803d;font-weight:800;">{{ $order->order_id }} · {{ $order->invoice_number }}</div>
            <div style="font-size:12px;color:#64748b;">{{ $order->created_at?->format('F d, Y h:i A') }} · {{ ucfirst($order->status) }}</div>
        </div>
        <div style="text-align:right;font-weight:800;font-size:18px;"><span style="color:#16a34a;">{{ $store['name_first_part'] ?? 'Mama' }}</span><span style="color:#f97316;">{{ $store['name_second_part'] ?? 'Bazar' }}</span></div>
    </div>
    <div class="addr">
        <strong style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#64748b;">Deliver To</strong><br>
        <strong style="font-size:18px;">{{ $order->customer_name }}</strong> — {{ $order->phone }}@if($order->display_alternative_phone) / {{ $order->display_alternative_phone }}@endif<br>
        {{ $order->address }}@if($order->apartment), {{ $order->apartment }}@endif, {{ $order->district }}<br>
        <span style="font-size:13px;">{{ $order->shipping_method_name ?: 'Standard' }} · {{ strtoupper($order->payment_method) }} · ৳{{ number_format($order->total_price, 0) }} {{ $order->payment_status === 'success' ? '(Paid)' : '(Collect)' }}</span>
    </div>
    <table>
        <thead><tr><th style="width:36px;">✓</th><th>Item</th><th>Variant</th><th style="text-align:center;">Qty</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td><span class="check"></span></td>
                <td><strong>{{ $item->product_title ?: ($item->product?->title ?: 'Product') }}</strong><br><span style="font-size:11px;color:#64748b;">{{ $item->product_sku ?: 'MB-' . $item->product_id }}</span></td>
                <td>{{ $item->variant_name ?: trim(($item->size ?: '') . ' / ' . ($item->color ?: ''), ' /') ?: '—' }}</td>
                <td style="text-align:center;font-size:18px;font-weight:800;">{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($order->order_note)<p style="margin-top:12px;font-size:13px;"><strong>Note:</strong> {{ $order->order_note }}</p>@endif
    <div style="margin-top:20px;display:flex;gap:40px;font-size:12px;color:#475569;">
        <div>Packed by: __________</div><div>Checked by: __________</div><div style="margin-left:auto;">Total items: <strong>{{ $order->items->sum('quantity') }}</strong></div>
    </div>
</div>
@include('pdf.branding-footer')
</body>
</html>
