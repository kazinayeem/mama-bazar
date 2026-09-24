<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_id }} - Mama Bazar</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-6 sm:p-12 font-body text-slate-800 antialiased">

    <div class="max-w-3xl mx-auto bg-white p-8 sm:p-12 rounded-3xl border border-slate-200 shadow-sm space-y-8">
        
        <div class="no-print flex justify-end mb-4">
            <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-md hover:bg-slate-800">
                Print Invoice 🖨️
            </button>
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between pb-6 border-b border-slate-200">
            <div class="flex items-center gap-2">
                <img src="/brandlogo.png" alt="Mama Bazar" class="h-10 w-10 object-contain">
                <div>
                    <span class="font-extrabold text-xl tracking-tight block">
                        <span class="text-brand-green-600">Mama</span><span class="text-brand-orange-500">Bazar</span>
                    </span>
                    <span class="text-[10px] text-slate-400">Online Grocery & Essentials</span>
                </div>
            </div>
            <div class="text-right">
                <h1 class="text-xl font-black text-slate-900">INVOICE</h1>
                <p class="text-xs font-bold text-brand-green-700 mt-0.5">{{ $order->order_id }}</p>
                <p class="text-[11px] text-slate-400">{{ $order->created_at->format('F d, Y') }}</p>
            </div>
        </div>

        <!-- Customer & Order Meta -->
        <div class="grid grid-cols-2 gap-8 text-xs">
            <div>
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">Invoiced To:</span>
                <p class="font-bold text-slate-800 text-sm">{{ $order->customer_name }}</p>
                <p class="text-slate-600 mt-0.5">{{ $order->address }}</p>
                <p class="text-slate-600">{{ $order->district }}</p>
                <p class="text-slate-600 mt-1 font-semibold">📞 {{ $order->phone }}</p>
            </div>
            <div class="text-right space-y-1">
                <p><span class="font-bold text-slate-400 uppercase">Payment Method:</span> <span class="font-bold uppercase text-slate-800">{{ $order->payment_method }}</span></p>
                <p><span class="font-bold text-slate-400 uppercase">Payment Status:</span> <span class="font-bold text-emerald-600">{{ ucfirst($order->payment_status) }}</span></p>
                <p><span class="font-bold text-slate-400 uppercase">Shipping:</span> <span class="text-slate-700">{{ $order->shipping_method_name ?: 'Standard' }}</span></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full text-left text-xs border border-slate-200 rounded-xl overflow-hidden">
            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                <tr>
                    <th class="p-3">Item Description</th>
                    <th class="p-3 text-center">Qty</th>
                    <th class="p-3 text-right">Unit Price</th>
                    <th class="p-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($order->items as $item)
                    <tr>
                        <td class="p-3 font-bold text-slate-800">{{ $item->product ? $item->product->title : 'Product' }}</td>
                        <td class="p-3 text-center font-bold text-slate-800">{{ $item->quantity }}</td>
                        <td class="p-3 text-right text-slate-600">৳{{ number_format($item->price, 0) }}</td>
                        <td class="p-3 text-right font-bold text-slate-900">৳{{ number_format($item->price * $item->quantity, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 font-bold text-xs border-t border-slate-200">
                <tr>
                    <td colspan="3" class="p-3 text-right text-slate-600">Subtotal:</td>
                    <td class="p-3 text-right text-slate-900">৳{{ number_format($order->subtotal, 0) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="p-3 text-right text-slate-600">Shipping Delivery:</td>
                    <td class="p-3 text-right text-slate-900">৳{{ number_format($order->shipping_cost, 0) }}</td>
                </tr>
                <tr class="text-sm">
                    <td colspan="3" class="p-3 text-right text-brand-green-700 font-black">Grand Total:</td>
                    <td class="p-3 text-right text-brand-orange-600 font-black">৳{{ number_format($order->total_price, 0) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Notice -->
        <div class="pt-6 border-t border-slate-200 text-center text-xs text-slate-400 space-y-1">
            <p class="font-bold text-slate-600">Thank you for ordering with Mama Bazar!</p>
            <p>For any queries regarding this order, please reach our helpline: 01700-000000</p>
        </div>

    </div>

</body>
</html>
