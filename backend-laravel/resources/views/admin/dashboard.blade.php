@extends('layouts.admin', ['headerTitle' => 'Dashboard'])

@section('content')
@php
    $lucide = \App\Support\AdminNav::lucide();
    $chartColors = ['#16a34a', '#f97316', '#0284c7', '#eab308', '#dc2626', '#64748b', '#0d9488'];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="admin-page-title">Dashboard</h1>
            <p class="text-sm text-slate-500">Business overview and performance metrics</p>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2">
            <select name="range" onchange="this.form.submit()"
                    class="w-full min-w-[9rem] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-500/20 sm:w-36">
                <option value="7" @selected($range === 7)>Last 7 days</option>
                <option value="30" @selected($range === 30)>Last 30 days</option>
                <option value="365" @selected($range === 365)>Last 365 days</option>
            </select>
            <a href="{{ route('admin.dashboard', ['range' => $range]) }}"
               class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                Refresh
            </a>
        </form>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $kpiCards = [
                [
                    'label' => 'Total Revenue',
                    'value' => '৳'.number_format($kpis['totalRevenue'], 0),
                    'sub' => '৳'.number_format($kpis['periodRevenue'], 0).' this period',
                    'hint' => 'Delivered orders',
                    'icon' => 'wallet',
                    'iconBg' => 'bg-brand-green-50 text-brand-green-600',
                ],
                [
                    'label' => 'Total Orders',
                    'value' => number_format($kpis['totalOrders']),
                    'sub' => $kpis['periodOrders'].' in last '.$range.' days',
                    'hint' => 'Delivered count',
                    'icon' => 'shopping-cart',
                    'iconBg' => 'bg-brand-orange-50 text-brand-orange-600',
                ],
                [
                    'label' => 'Avg Order Value',
                    'value' => '৳'.number_format($kpis['avgOrderValue'], 0),
                    'sub' => 'Per delivered order',
                    'hint' => '',
                    'icon' => 'credit-card',
                    'iconBg' => 'bg-slate-100 text-slate-600',
                ],
                [
                    'label' => 'Customers',
                    'value' => number_format($kpis['totalCustomers']),
                    'sub' => 'Registered accounts',
                    'hint' => '',
                    'icon' => 'users',
                    'iconBg' => 'bg-brand-green-50 text-brand-green-600',
                ],
                [
                    'label' => 'Products',
                    'value' => number_format($kpis['totalProducts']),
                    'sub' => $kpis['outOfStock'].' out of stock',
                    'hint' => $kpis['outOfStock'] > 0 ? 'Attention needed' : 'Healthy',
                    'icon' => 'package',
                    'iconBg' => 'bg-brand-orange-50 text-brand-orange-600',
                    'warn' => $kpis['outOfStock'] > 0,
                ],
                [
                    'label' => "Today's Orders",
                    'value' => number_format($kpis['todayOrders']),
                    'sub' => 'Orders placed today',
                    'hint' => 'Live',
                    'icon' => 'boxes',
                    'iconBg' => 'bg-brand-green-50 text-brand-green-600',
                ],
            ];
        @endphp

        @foreach($kpiCards as $card)
            <div class="admin-surface p-4 transition-shadow hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-1 admin-page-title">{{ $card['value'] }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                            @if(!empty($card['hint']))
                                <span class="font-medium {{ !empty($card['warn']) ? 'text-brand-orange-600' : 'text-brand-green-600' }}">{{ $card['hint'] }}</span>
                            @endif
                            <span class="text-slate-400">{{ $card['sub'] }}</span>
                        </div>
                    </div>
                    <div class="shrink-0 rounded-lg p-2.5 {{ $card['iconBg'] }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            {!! $lucide[$card['icon']] ?? $lucide['package'] !!}
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Revenue chart --}}
        <div class="admin-surface xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-bold text-slate-900">Revenue Overview</h2>
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    {{ $range }} days
                </span>
            </div>
            <div class="p-5">
                <div class="relative h-64 w-full">
                    <canvas id="revenueChart" aria-label="Revenue over time"></canvas>
                </div>
            </div>
        </div>

        {{-- Order status donut --}}
        <div class="admin-surface">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-bold text-slate-900">Order Status</h2>
            </div>
            <div class="p-5">
                @if(count($statusChart) === 0)
                    <p class="py-16 text-center text-sm text-slate-500">No orders yet</p>
                @else
                    <div class="mx-auto flex max-w-xs flex-col items-center gap-4">
                        <div class="relative h-44 w-44">
                            <canvas id="statusChart" aria-label="Orders by status"></canvas>
                        </div>
                        <div class="grid w-full grid-cols-2 gap-1.5">
                            @foreach($statusChart as $i => $entry)
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $chartColors[$i % count($chartColors)] }}"></span>
                                    <span class="min-w-0 flex-1 truncate text-slate-500">{{ $entry['name'] }}</span>
                                    <span class="font-semibold text-slate-800">{{ $entry['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($expenses)
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @php
                $expenseCards = [
                    ['label' => 'Total Expenses', 'value' => $expenses['total'], 'sub' => 'All time'],
                    ['label' => 'This Month', 'value' => $expenses['thisMonth'], 'sub' => $expenses['thisMonthCount'].' expenses'],
                    ['label' => 'This Week', 'value' => $expenses['thisWeek'], 'sub' => $expenses['thisWeekCount'].' expenses'],
                    ['label' => 'Today', 'value' => $expenses['today'], 'sub' => $expenses['todayCount'].' expenses'],
                ];
            @endphp
            @foreach($expenseCards as $ec)
                <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs text-slate-500 sm:text-sm">{{ $ec['label'] }}</p>
                            <p class="mt-1 text-lg font-bold tracking-tight text-slate-900 sm:text-xl">৳{{ number_format($ec['value'], 0) }}</p>
                            <p class="mt-1 text-[11px] text-slate-400 sm:text-xs">{{ $ec['sub'] }}</p>
                        </div>
                        <div class="rounded-lg bg-red-50 p-2 text-red-600">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $lucide['wallet'] !!}</svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Recent orders --}}
        <div class="admin-surface xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-bold text-slate-900">Recent Orders</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-brand-green-600 hover:text-brand-green-700">View all</a>
            </div>

            @if($recentOrders->isEmpty())
                <p class="p-6 text-sm text-slate-500">No orders yet</p>
            @else
                {{-- Mobile cards --}}
                <div class="divide-y md:hidden">
                    @foreach($recentOrders as $order)
                        @php
                            $badge = $statusBadges[$order->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                            $label = $statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status));
                            $initial = strtoupper(substr($order->customer_name ?? '?', 0, 1));
                            $created = \Carbon\Carbon::parse($order->created_at);
                        @endphp
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ $initial }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $order->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->order_id }} · {{ $created->format('M j, Y') }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $badge }}">{{ $label }}</span>
                                <span class="text-sm font-semibold text-slate-900">৳{{ number_format($order->total_price, 0) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Order</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                                <th class="px-5 py-3 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentOrders as $order)
                                @php
                                    $badge = $statusBadges[$order->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                                    $label = $statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status));
                                    $initial = strtoupper(substr($order->customer_name ?? '?', 0, 1));
                                    $created = \Carbon\Carbon::parse($order->created_at);
                                @endphp
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ $initial }}</div>
                                            <span class="font-medium text-slate-900">{{ $order->customer_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-xs text-brand-green-700">{{ $order->order_id }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $badge }}">{{ $label }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-900">৳{{ number_format($order->total_price, 0) }}</td>
                                    <td class="px-5 py-3 text-right text-xs text-slate-500">{{ $created->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Top products --}}
        <div class="admin-surface">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-bold text-slate-900">Top Products</h2>
            </div>
            @if(empty($topProducts))
                <p class="p-6 text-sm text-slate-500">{{ $hasOrderItems ? 'No sales yet' : 'No order line items in database' }}</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($topProducts as $i => $product)
                        <div class="flex items-center gap-3 px-5 py-2.5">
                            <span class="w-4 text-xs font-bold text-slate-400">{{ $i + 1 }}</span>
                            @if($product['image'])
                                <img src="{{ $product['image'] }}" alt="" class="h-9 w-9 shrink-0 rounded-md object-cover bg-slate-100" loading="lazy">
                            @else
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $lucide['package'] !!}</svg>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $product['title'] }}</p>
                                <p class="text-xs text-slate-500">{{ number_format($product['quantity']) }} sold</p>
                            </div>
                            <span class="shrink-0 text-xs font-semibold text-slate-800">৳{{ number_format($product['revenue'], 0) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Low stock --}}
    <div class="admin-surface">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-bold text-slate-900">Low Stock Alerts</h2>
            <a href="{{ route('admin.inventory.index') }}" class="text-xs font-semibold text-brand-green-600 hover:text-brand-green-700">Manage inventory</a>
        </div>
        @if(empty($lowStockProducts))
            <p class="p-6 text-sm text-slate-500">All products are well stocked</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($lowStockProducts as $product)
                    <div class="flex items-center gap-3 px-4 py-3 sm:px-5">
                        @if($product['image'])
                            <img src="{{ $product['image'] }}" alt="" class="h-9 w-9 shrink-0 rounded-md object-cover bg-slate-100" loading="lazy">
                        @else
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $lucide['package'] !!}</svg>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $product['title'] }}</p>
                            <p class="text-xs text-slate-500">৳{{ number_format($product['price'], 0) }}</p>
                        </div>
                        @if(($product['stock'] ?? 0) <= 0)
                            <span class="shrink-0 rounded-full bg-red-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-700 ring-1 ring-inset ring-red-200">Out of stock</span>
                        @else
                            <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-bold text-amber-800 ring-1 ring-inset ring-amber-200">{{ $product['stock'] }} left</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    const brandGreen = '#16a34a';
    const chartColors = @json($chartColors);
    const revenueData = @json($revenueChart);
    const statusData = @json($statusChart);

    const fmtCurrency = (v) => '৳' + Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 });

    const revEl = document.getElementById('revenueChart');
    if (revEl && revenueData.length) {
        const gradient = revEl.getContext('2d').createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(22, 163, 74, 0.25)');
        gradient.addColorStop(1, 'rgba(22, 163, 74, 0)');

        new Chart(revEl, {
            type: 'line',
            data: {
                labels: revenueData.map(d => d.label),
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(d => d.revenue),
                    borderColor: brandGreen,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    pointRadius: revenueData.length > 60 ? 0 : 2,
                    pointHoverRadius: 4,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => fmtCurrency(ctx.parsed.y),
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 8, font: { size: 11 } },
                    },
                    y: {
                        grid: { color: 'rgba(148, 163, 184, 0.2)' },
                        ticks: {
                            font: { size: 11 },
                            callback: (v) => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v,
                        },
                    },
                },
            },
        });
    }

    const statusEl = document.getElementById('statusChart');
    if (statusEl && statusData.length) {
        new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.name),
                datasets: [{
                    data: statusData.map(d => d.value),
                    backgroundColor: statusData.map((_, i) => chartColors[i % chartColors.length]),
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                },
            },
        });
    }
})();
</script>
@endpush
