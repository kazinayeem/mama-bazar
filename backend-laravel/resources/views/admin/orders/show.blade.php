@extends('layouts.admin', ['headerTitle' => 'Order #' . $order->order_id])

@section('content')
<div class="max-w-5xl mx-auto space-y-5" x-data="{ actionsOpen: false, customerOpen: false }">

    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
        <div>
            <span class="text-xs text-slate-400 font-semibold uppercase">Order Overview</span>
            <h2 class="text-xl font-black text-slate-900">{{ $order->order_id }}</h2>
            <p class="text-xs text-slate-500">{{ $order->invoice_number }} · {{ $order->created_at?->format('M d, Y h:i A') }} ·
                <span class="font-bold {{ $order->user_id ? 'text-brand-green-700' : 'text-amber-600' }}">{{ $order->user_id ? 'Registered' : 'Guest' }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <button @click="actionsOpen = !actionsOpen" @click.away="actionsOpen = false"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
                    Invoice Actions ▾
                </button>
                <div x-show="actionsOpen" x-cloak class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl py-1 text-xs">
                    <a href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">View Invoice</a>
                    <a href="{{ route('admin.orders.invoice.download', $order->id) }}" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">Download PDF</a>
                    <a href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank" onclick="event.preventDefault(); window.open(this.href,'_blank').print();" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">Print Invoice</a>
                    <a href="{{ route('admin.orders.packing-slip', $order->id) }}" target="_blank" class="block px-4 py-2.5 font-semibold hover:bg-slate-50">Print Packing Slip</a>
                    <button type="button" onclick="copyOrderInfo()" class="block w-full text-left px-4 py-2.5 font-semibold hover:bg-slate-50">Copy Order Info</button>
                    <button type="button" @click="customerOpen = true; actionsOpen = false" class="block w-full text-left px-4 py-2.5 font-semibold hover:bg-slate-50">View Customer Details</button>
                </div>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <div class="admin-surface p-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Update Order Status</h3>
                <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="w-52">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                        <select name="status" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 bg-white">
                            @foreach(['pending','payment_pending','payment_verification','confirmed','processing','packed','shipped','out_for_delivery','delivered','cancelled','returned','refunded'] as $st)
                                <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Note (optional)</label>
                        <input type="text" name="note" placeholder="e.g. Courier tracking assigned" class="w-full text-xs rounded-xl border border-slate-200 p-2.5">
                    </div>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold transition">Update</button>
                </form>
                <form action="{{ route('admin.orders.payment', $order->id) }}" method="POST" class="flex flex-wrap items-end gap-3 mt-3 pt-3 border-t border-slate-100">
                    @csrf
                    <div class="w-52">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Payment status</label>
                        <select name="payment_status" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 bg-white">
                            @foreach(['pending','payment_pending','payment_verification','verified','success','failed','rejected','refunded'] as $ps)
                                <option value="{{ $ps }}" {{ $order->payment_status === $ps ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$ps)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Note (optional)</label>
                        <input type="text" name="note" placeholder="e.g. bKash verified" class="w-full text-xs rounded-xl border border-slate-200 p-2.5">
                    </div>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">Save Payment</button>
                </form>
            </div>

            <div class="admin-table-wrap">
                <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Order Items ({{ $order->items->sum('quantity') }} pcs)</div>
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                        <tr><th class="p-4">Item / SKU / Variant</th><th class="p-4 text-center">Qty</th><th class="p-4 text-right">Unit</th><th class="p-4 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="p-4">
                                <span class="font-bold text-slate-800">{{ $item->product_title ?: ($item->product?->title ?: 'Product') }}</span><br>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $item->product_sku ?: 'MB-' . $item->product_id }}</span>
                                @if($item->variant_name || $item->size || $item->color)
                                <br><span class="text-[11px] text-slate-500">{{ $item->variant_name ?: trim(($item->size ?: '') . ' / ' . ($item->color ?: ''), ' /') }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-center font-bold">{{ $item->quantity }}</td>
                            <td class="p-4 text-right text-slate-600">৳{{ number_format($item->price, 0) }}</td>
                            <td class="p-4 font-extrabold text-slate-900 text-right">৳{{ number_format($item->price * $item->quantity, 0) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50/60 font-bold border-t border-slate-100">
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">Subtotal</td><td class="p-3 text-right">৳{{ number_format($order->subtotal, 0) }}</td></tr>
                        @if((float)$order->discount > 0)
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">Discount @if($order->coupon_code) ({{ $order->coupon_code }})@endif</td><td class="p-3 text-right text-red-600">− ৳{{ number_format($order->discount, 0) }}</td></tr>
                        @endif
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">Delivery ({{ $order->shipping_method_name ?: 'Standard' }})</td><td class="p-3 text-right">৳{{ number_format($order->shipping_cost, 0) }}</td></tr>
                        @if((float)$order->tax > 0)
                        <tr><td colspan="3" class="p-3 text-right text-slate-600">VAT / Tax</td><td class="p-3 text-right">৳{{ number_format($order->tax, 0) }}</td></tr>
                        @endif
                        <tr class="text-sm"><td colspan="3" class="p-4 text-right text-brand-green-700 font-black">Grand Total</td><td class="p-4 text-right text-brand-orange-600 font-black">৳{{ number_format($order->total_price, 0) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="admin-surface p-4" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-700">
                    <span>Order Analytics &amp; Source (privacy-conscious)</span><span x-text="open ? '−' : '+'"></span>
                </button>
                <div x-show="open" x-cloak class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div><span class="text-slate-400 font-semibold block">Device</span><span class="font-bold">{{ $order->device_type ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Browser</span><span class="font-bold">{{ $order->browser ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">OS</span><span class="font-bold">{{ $order->os_platform ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Source</span><span class="font-bold">{{ $order->utm_source ?: ($order->order_source ?: 'Direct') }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Campaign</span><span class="font-bold">{{ $order->utm_campaign ?: '—' }}</span></div>
                    <div><span class="text-slate-400 font-semibold block">Medium</span><span class="font-bold">{{ $order->utm_medium ?: '—' }}</span></div>
                    <div class="col-span-2 sm:col-span-3"><span class="text-slate-400 font-semibold block">Landing page</span><span class="font-mono text-[11px] break-all">{{ $order->landing_page ?: '—' }}</span></div>
                    <div class="col-span-2 sm:col-span-3"><span class="text-slate-400 font-semibold block">Created</span>{{ $order->created_at?->format('M d, Y h:i A') }} · IP area {{ $order->ip_truncated ?: '—' }} (truncated for privacy)</div>
                </div>
            </div>

            <div class="admin-surface p-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Order History</h3>
                <div class="space-y-3">
                    @forelse($order->statusHistory->sortByDesc('created_at') as $h)
                    <div class="flex gap-3 text-xs">
                        <div class="w-2 h-2 rounded-full bg-brand-green-500 mt-1.5 shrink-0"></div>
                        <div>
                            <p class="font-bold">{{ ucfirst(str_replace('_',' ',$h->status)) }} <span class="font-normal text-slate-400">· {{ $h->created_at?->format('M d, h:i A') }}</span></p>
                            @if($h->note)<p class="text-slate-500">{{ $h->note }}</p>@endif
                            @if($h->user)<p class="text-slate-400">by {{ $h->user->name }}</p>@endif
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400">No history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="admin-surface p-4 space-y-2 text-xs">
                <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs border-b border-slate-100 pb-2">Customer &amp; Shipping</h3>
                <p><span class="text-slate-400 font-semibold">Name:</span> <span class="font-bold text-slate-800">{{ $order->customer_name }}</span></p>
                <p><span class="text-slate-400 font-semibold">Phone:</span> <a href="tel:{{ $order->phone }}" class="font-semibold text-brand-green-700">{{ $order->phone }}</a></p>
                @if($order->display_alternative_phone)<p><span class="text-slate-400 font-semibold">Alt:</span> {{ $order->display_alternative_phone }}</p>@endif
                @if($order->email)<p><span class="text-slate-400 font-semibold">Email:</span> {{ $order->email }}</p>@endif
                <p><span class="text-slate-400 font-semibold">Address:</span> {{ $order->address }}@if($order->apartment), {{ $order->apartment }}@endif, {{ $order->district }}</p>
                <p><span class="text-slate-400 font-semibold">Method:</span> {{ $order->shipping_method_name ?: 'Standard Delivery' }}</p>
                @if($order->courier_tracking_number)<p><span class="text-slate-400 font-semibold">Courier:</span> <span class="font-mono">{{ $order->courier_tracking_number }}</span></p>@endif
                @if($order->order_note)<p><span class="text-slate-400 font-semibold">Note:</span> {{ $order->order_note }}</p>@endif
            </div>

            <div class="admin-surface p-4 space-y-2 text-xs">
                <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs border-b border-slate-100 pb-2">Payment Info</h3>
                <p><span class="text-slate-400 font-semibold">Method:</span> <span class="uppercase font-bold">{{ $order->payment_method }}</span></p>
                <p><span class="text-slate-400 font-semibold">Status:</span> <span class="font-bold {{ in_array($order->payment_status,['success','verified']) ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst(str_replace('_',' ',$order->payment_status)) }}</span></p>
                @if($order->transaction_id)<p><span class="text-slate-400 font-semibold">Trx ID:</span> <span class="font-mono">{{ $order->transaction_id }}</span></p>@endif
                @if($order->sender_number)<p><span class="text-slate-400 font-semibold">Sender:</span> {{ $order->sender_number }}</p>@endif
            </div>

            <div class="admin-surface p-4 space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="font-bold text-slate-900 uppercase tracking-wider text-xs">Customer Emails</h3>
                    @if(\App\Http\Middleware\EnsureAdminPermission::allows(auth()->user(), ['email.logs.view']))
                        <a href="{{ route('admin.email.logs.index', ['order' => $order->order_id]) }}" class="text-[11px] font-semibold text-brand-green-700 hover:underline">All logs</a>
                    @endif
                </div>
                @if($order->email && $invoiceReady)
                    <form action="{{ route('admin.orders.email-invoice', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold">Email Invoice PDF to Customer</button>
                    </form>
                @elseif(! $order->email)
                    <p class="text-slate-400">No email address on this order — emails cannot be sent.</p>
                @else
                    <p class="text-slate-400">Invoice email is available once payment is verified (or for Cash on Delivery orders).</p>
                @endif
                @forelse($emailLogs as $log)
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-700">{{ $log->subject }}</p>
                            <p class="text-[11px] text-slate-400">{{ $log->created_at?->format('M d, h:i A') }}@if(! empty($log->metadata['invoice_attachment_failed'])) · PDF failed @endif</p>
                        </div>
                        @include('admin.email.partials.status-badge', ['status' => $log->status])
                    </div>
                @empty
                    <p class="text-slate-400">No emails sent for this order yet.</p>
                @endforelse
            </div>

            <div class="admin-surface p-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Internal Notes</h3>
                @if($order->admin_notes)<pre class="text-[11px] text-slate-600 whitespace-pre-wrap bg-slate-50 rounded-lg p-3 mb-3">{{ $order->admin_notes }}</pre>@endif
                <form action="{{ route('admin.orders.notes', $order->id) }}" method="POST" class="space-y-2">
                    @csrf
                    <textarea name="admin_notes" rows="2" required placeholder="Add internal note (not printed)..." class="w-full text-xs rounded-xl border border-slate-200 p-2.5"></textarea>
                    <button class="w-full px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold">Add Note</button>
                </form>
            </div>
        </div>
    </div>

    <div x-show="customerOpen" x-cloak class="fixed inset-0 z-[300] flex items-center justify-center bg-black/40 p-4" @click.self="customerOpen = false">
        <div class="w-full max-w-md bg-white rounded-2xl p-6 space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-black">Customer Details</h3>
                <button @click="customerOpen = false" class="text-slate-400 hover:text-slate-800">✕</button>
            </div>
            <p><strong>{{ $order->customer_name }}</strong> <span class="text-xs px-2 py-0.5 rounded-full {{ $order->user_id ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $order->user_id ? 'Registered' : 'Guest' }}</span></p>
            <p class="text-xs">Phone: {{ $order->phone }}@if($order->display_alternative_phone) · Alt: {{ $order->display_alternative_phone }}@endif</p>
            @if($order->email)<p class="text-xs">Email: {{ $order->email }}</p>@endif
            <p class="text-xs">{{ $order->address }}, {{ $order->district }}</p>
            <button onclick="copyOrderInfo()" class="w-full py-2 rounded-xl bg-slate-800 text-white text-xs font-bold">Copy Order Information</button>
        </div>
    </div>

</div>

<script>
function copyOrderInfo() {
    const text = [
        'Order: {{ $order->order_id }} ({{ $order->invoice_number }})',
        'Customer: {{ addslashes($order->customer_name) }} — {{ $order->phone }}',
        'Address: {{ addslashes($order->address) }}, {{ addslashes($order->district ?? '') }}',
        'Total: ৳{{ number_format($order->total_price, 0) }} via {{ strtoupper($order->payment_method) }}',
    ].join('\n');
    navigator.clipboard?.writeText(text).then(() => alert('Order information copied.'));
}
</script>
@endsection
