@extends('layouts.admin', ['headerTitle' => 'Order #' . $order->order_id])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
        <div>
            <span class="text-xs text-slate-400 font-semibold uppercase">Order Overview</span>
            <h2 class="text-xl font-black text-slate-900">{{ $order->order_id }}</h2>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
                Print Invoice 🖨️
            </a>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                &larr; Back to Orders
            </a>
        </div>
    </div>

    <!-- Status Update Form -->
    <div class="admin-surface p-4">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Update Order Status</h3>
        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="flex flex-wrap items-end gap-3">
            @csrf

            <div class="w-48">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none bg-white">
                    @foreach(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $st)
                        <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status Note (Optional)</label>
                <input type="text" name="note" placeholder="e.g. Courier tracking assigned" 
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold shadow-sm transition">
                Update Status
            </button>
        </form>
    </div>

    <!-- Customer & Shipping Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="admin-surface p-4 space-y-2 text-xs">
            <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs border-b border-slate-100 pb-2">Customer & Shipping</h3>
            <p><span class="text-slate-400 font-semibold">Name:</span> <span class="font-bold text-slate-800">{{ $order->customer_name }}</span></p>
            <p><span class="text-slate-400 font-semibold">Phone:</span> <span class="font-semibold text-slate-800">{{ $order->phone }}</span></p>
            @if($order->alternative_phone)
                <p><span class="text-slate-400 font-semibold">Alternative Phone:</span> {{ $order->alternative_phone }}</p>
            @endif
            <p><span class="text-slate-400 font-semibold">Address:</span> {{ $order->address }}, {{ $order->district }}</p>
            <p><span class="text-slate-400 font-semibold">Method:</span> {{ $order->shipping_method_name ?: 'Standard Delivery' }}</p>
        </div>

        <div class="admin-surface p-4 space-y-2 text-xs">
            <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs border-b border-slate-100 pb-2">Payment Info</h3>
            <p><span class="text-slate-400 font-semibold">Method:</span> <span class="uppercase font-bold text-slate-800">{{ $order->payment_method }}</span></p>
            <p><span class="text-slate-400 font-semibold">Status:</span> <span class="font-bold {{ $order->payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($order->payment_status) }}</span></p>
            @if($order->transaction_id)
                <p><span class="text-slate-400 font-semibold">Transaction ID:</span> <span class="font-mono">{{ $order->transaction_id }}</span></p>
            @endif
            @if($order->sender_number)
                <p><span class="text-slate-400 font-semibold">Sender Number:</span> {{ $order->sender_number }}</p>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <div class="admin-table-wrap">
        <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Order Items</div>
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                <tr>
                    <th class="p-4">Item</th>
                    <th class="p-4">Unit Price</th>
                    <th class="p-4">Qty</th>
                    <th class="p-4 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($order->items as $item)
                    <tr>
                        <td class="p-4 font-bold text-slate-800">
                            {{ $item->product ? $item->product->title : 'Product' }}
                        </td>
                        <td class="p-4 text-slate-600">৳{{ number_format($item->price, 0) }}</td>
                        <td class="p-4 font-bold text-slate-800">{{ $item->quantity }}</td>
                        <td class="p-4 font-extrabold text-slate-900 text-right">৳{{ number_format($item->price * $item->quantity, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50/60 font-bold border-t border-slate-100">
                <tr>
                    <td colspan="3" class="p-4 text-right text-slate-600">Subtotal:</td>
                    <td class="p-4 text-right text-slate-900 font-extrabold">৳{{ number_format($order->subtotal, 0) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="p-4 text-right text-slate-600">Shipping Charge:</td>
                    <td class="p-4 text-right text-slate-900 font-extrabold">৳{{ number_format($order->shipping_cost, 0) }}</td>
                </tr>
                <tr class="text-sm">
                    <td colspan="3" class="p-4 text-right text-brand-green-700 font-black">Grand Total:</td>
                    <td class="p-4 text-right text-brand-orange-600 font-black">৳{{ number_format($order->total_price, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
@endsection
