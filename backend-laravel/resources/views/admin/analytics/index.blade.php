@extends('layouts.admin', ['headerTitle' => 'Analytics'])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Analytics</h1>
        <p class="text-sm text-slate-500">Business performance from live SQLite data</p>
    </div>
    <form method="GET">
        <select name="range" onchange="this.form.submit()" class="rounded-lg border bg-white px-3 py-2 text-sm">
            <option value="7" @selected($range===7)>Last 7 days</option>
            <option value="30" @selected($range===30)>Last 30 days</option>
            <option value="365" @selected($range===365)>Last 365 days</option>
        </select>
    </form>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Revenue</p><p class="mt-1 text-2xl font-bold">৳{{ number_format($revenue, 0) }}</p></div>
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Orders</p><p class="mt-1 text-2xl font-bold">{{ number_format($orderCount) }}</p></div>
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">New Customers</p><p class="mt-1 text-2xl font-bold">{{ number_format($customers) }}</p></div>
    <div class="rounded-xl border bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">Avg Order</p><p class="mt-1 text-2xl font-bold">৳{{ number_format($avgOrder, 0) }}</p></div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-3">
    <div class="rounded-xl border bg-white p-5 shadow-soft xl:col-span-2">
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
    <div class="rounded-xl border bg-white p-5 shadow-soft">
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
    </div>
</div>

<div class="mt-4 overflow-hidden rounded-xl border bg-white shadow-soft">
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
@endsection
