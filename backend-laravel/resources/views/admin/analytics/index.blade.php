@extends('layouts.admin', ['headerTitle' => 'Analytics'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Analytics" subtitle="Orders, revenue & marketing performance">
        <x-slot:actions>
            <form method="GET" class="flex gap-2">
                <select name="range" onchange="this.form.submit()" class="admin-control">
                    <option value="7" @selected($range===7)>Last 7 days</option>
                    <option value="30" @selected($range===30)>Last 30 days</option>
                    <option value="90" @selected($range===90)>Last 90 days</option>
                    <option value="365" @selected($range===365)>Last 365 days</option>
                </select>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-metric-grid">
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Revenue (excl. cancelled)</p><p class="mt-1 text-2xl font-bold">৳{{ number_format($revenue, 0) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Orders Created</p><p class="mt-1 text-2xl font-bold">{{ number_format($orderCount) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Paid Orders</p><p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($paidOrders ?? 0) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Cancelled / Refunded</p><p class="mt-1 text-2xl font-bold text-red-500">{{ number_format($cancelledOrders ?? 0) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Avg Order Value</p><p class="mt-1 text-2xl font-bold">৳{{ number_format($avgOrder, 0) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Guest vs Registered</p><p class="mt-1 text-lg font-bold">{{ number_format($guestOrders ?? 0) }} <span class="text-xs font-normal text-slate-400">guest</span> / {{ number_format($registeredOrders ?? 0) }} <span class="text-xs font-normal text-slate-400">registered</span></p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Attributed Conversions</p><p class="mt-1 text-lg font-bold">{{ number_format($attributed ?? 0) }} <span class="text-xs font-normal text-slate-400">vs {{ number_format($unattributed ?? 0) }} unattributed</span></p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">New Customers</p><p class="mt-1 text-2xl font-bold">{{ number_format($customers) }}</p></div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="admin-surface p-4 xl:col-span-2">
            <h3 class="mb-4 text-sm font-bold">Revenue Trend</h3>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($revenueTrend as $row)
                    <div class="flex items-center gap-3 text-xs">
                        <span class="w-24 text-slate-500">{{ $row->day }}</span>
                        <div class="h-2 flex-1 rounded-full bg-slate-100 overflow-hidden">
                            @php $max = max(1, $revenueTrend->max('total')); $w = min(100, ($row->total / $max) * 100); @endphp
                            <div class="h-full rounded-full bg-blue-500" style="width: {{ $w }}%"></div>
                        </div>
                        <span class="w-24 text-right font-semibold">৳{{ number_format($row->total, 0) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No revenue in this range</p>
                @endforelse
            </div>
        </div>
        <div class="admin-surface p-4">
            <h3 class="mb-4 text-sm font-bold">Order Status</h3>
            <div class="space-y-2">
                @foreach($statusBreakdown as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="capitalize text-slate-600">{{ str_replace('_', ' ', $row->status) }}</span>
                        <span class="font-bold">{{ $row->count }}</span>
                    </div>
                @endforeach
            </div>
            <h3 class="mb-3 mt-6 text-sm font-bold">Payment Methods</h3>
            <div class="space-y-2">
                @foreach($paymentBreakdown as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="uppercase text-slate-600">{{ $row->method }}</span>
                        <span class="font-bold">{{ $row->count }}</span>
                    </div>
                @endforeach
            </div>
            <h3 class="mb-3 mt-6 text-sm font-bold">Shipping Methods</h3>
            <div class="space-y-2">
                @foreach($shippingBreakdown ?? [] as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">{{ $row->name }}</span>
                        <span class="font-bold">{{ $row->count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div id="sources" class="mt-4 grid gap-4 xl:grid-cols-3">
        <div class="admin-surface p-4">
            <h3 class="mb-4 text-sm font-bold">Orders by Device</h3>
            <div class="space-y-2">
                @forelse($deviceBreakdown ?? [] as $row)
                    @php $max = max(1, ($deviceBreakdown ?? collect())->max('count')); $w = min(100, ($row->count / $max) * 100); @endphp
                    <div class="flex items-center gap-3 text-xs">
                        <span class="w-20 text-slate-600">{{ $row->name }}</span>
                        <div class="h-2 flex-1 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $w }}%"></div></div>
                        <span class="w-10 text-right font-bold">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No device data yet — collected on new orders.</p>
                @endforelse
            </div>
            <h3 class="mb-3 mt-6 text-sm font-bold">Orders by Browser</h3>
            <div class="space-y-2">
                @forelse($browserBreakdown ?? [] as $row)
                    <div class="flex items-center justify-between text-xs"><span class="text-slate-600">{{ $row->name }}</span><span class="font-bold">{{ $row->count }}</span></div>
                @empty
                    <p class="text-xs text-slate-400">No browser data yet.</p>
                @endforelse
            </div>
        </div>
        <div id="campaigns" class="admin-surface p-4">
            <h3 class="mb-4 text-sm font-bold">Orders by Source</h3>
            <div class="space-y-2">
                @forelse($sourceBreakdown ?? [] as $row)
                    <div class="flex items-center justify-between text-xs"><span class="text-slate-600">{{ $row->name }}</span><span class="font-bold">{{ $row->count }}</span></div>
                @empty
                    <p class="text-xs text-slate-400">No source data yet.</p>
                @endforelse
            </div>
            <h3 class="mb-3 mt-6 text-sm font-bold">Campaign Performance</h3>
            <div class="space-y-2">
                @forelse($campaignBreakdown ?? [] as $row)
                    <div class="flex items-center justify-between text-xs"><span class="text-slate-600">{{ $row->name }}</span><span class="font-bold">{{ $row->count }} · ৳{{ number_format($row->revenue, 0) }}</span></div>
                @empty
                    <p class="text-xs text-slate-400">No UTM campaigns attributed yet. Share links with ?utm_source=…&amp;utm_campaign=… to measure.</p>
                @endforelse
            </div>
        </div>
        <div id="events" class="admin-surface p-4">
            <h3 class="mb-4 text-sm font-bold">Conversion Events</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between"><span class="text-slate-600">Orders created</span><span class="font-bold">{{ number_format($orderCount) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Successfully paid</span><span class="font-bold text-emerald-600">{{ number_format($paidOrders ?? 0) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Cancelled / refunded</span><span class="font-bold text-red-500">{{ number_format($cancelledOrders ?? 0) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Attributed</span><span class="font-bold">{{ number_format($attributed ?? 0) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Unattributed</span><span class="font-bold">{{ number_format($unattributed ?? 0) }}</span></div>
            </div>
            <p class="mt-4 text-[11px] text-slate-400">Counts reflect orders only — never labelled as unique visitors. Server-side purchase events deduplicate by transaction ID.</p>
            <a href="{{ route('admin.marketing.index') }}" class="mt-3 inline-block text-xs font-bold text-brand-green-700 hover:underline">Manage pixels &amp; integrations →</a>
        </div>
    </div>

    <div class="mt-4 admin-table-wrap">
        <div class="border-b px-5 py-3 text-sm font-bold">Top Products</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Product</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Revenue</th></tr></thead>
            <tbody class="divide-y">
                @forelse($topProducts as $i => $p)
                    <tr>
                        <td class="px-4 py-3"><span class="mr-2 text-xs text-slate-400">#{{ $i+1 }}</span>{{ $p->title }}</td>
                        <td class="px-4 py-3 text-right">{{ $p->qty }}</td>
                        <td class="px-4 py-3 text-right font-bold">৳{{ number_format($p->revenue, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No product sales yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
