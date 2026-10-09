@extends('layouts.admin', ['headerTitle' => 'Incomplete Orders & Abandoned Checkout Analytics'])

@section('content')
<div class="admin-page space-y-6" x-data="{ retentionModalOpen: false }">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Incomplete Orders &amp; Abandoned Checkouts</h1>
            <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                Privacy-conscious analytics and drop-off milestone tracking to optimize conversion without storing sensitive customer PII.
            </p>
        </div>
        <div class="flex items-center gap-2">
            @adminCan('incomplete_orders.export')
            <a href="{{ route('admin.incomplete-orders.export', request()->query()) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-green-500/20">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </a>
            @endadminCan

            @adminCan('incomplete_orders.manage_retention')
            <button type="button" @click="retentionModalOpen = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-green-500/20">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Data Retention
            </button>
            @endadminCan
        </div>
    </div>

    @if(session('success'))
        <div role="alert" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Top KPI Metrics Grid --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Checkouts</span>
            <div class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($metrics['total_sessions']) }}</div>
            <p class="mt-1 text-[11px] text-slate-400">Total tracked sessions</p>
        </div>

        <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Currently Active</span>
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-500"></span>
                </span>
            </div>
            <div class="mt-2 text-2xl font-extrabold text-blue-900">{{ number_format($metrics['active_sessions']) }}</div>
            <p class="mt-1 text-[11px] text-blue-600/80">&lt; 30 mins inactivity</p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-4 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-800">Incomplete Orders</span>
            <div class="mt-2 text-2xl font-extrabold text-amber-900">{{ number_format($metrics['incomplete_sessions']) }}</div>
            <p class="mt-1 text-[11px] text-amber-700/80">&gt; 30 mins paused</p>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-800">Converted</span>
            <div class="mt-2 text-2xl font-extrabold text-emerald-900">{{ number_format($metrics['converted_sessions']) }}</div>
            <p class="mt-1 text-[11px] text-emerald-700/80">Completed purchases</p>
        </div>

        <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-4 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-800">Abandonment Rate</span>
            <div class="mt-2 text-2xl font-extrabold text-rose-900">{{ $metrics['abandonment_rate'] }}%</div>
            <p class="mt-1 text-[11px] text-rose-700/80">Of concluded sessions</p>
        </div>

        <div class="rounded-2xl border border-purple-200 bg-purple-50/40 p-4 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-purple-800">Avg Completion</span>
            <div class="mt-2 text-2xl font-extrabold text-purple-900">{{ $metrics['avg_completion_percent'] }}%</div>
            <p class="mt-1 text-[11px] text-purple-700/80">Prior to abandonment</p>
        </div>
    </div>

    {{-- Funnel & Drop-off Breakdown --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Funnel Column (2 cols) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Checkout Conversion Funnel</h2>
                    <p class="text-xs text-slate-500">Step progression from checkout open to completed order</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                    {{ $metrics['conversion_rate'] }}% Overall Conversion
                </span>
            </div>

            @php
                $baseCount = max($metrics['funnel']['opened'], 1);
                $funnelSteps = [
                    ['label' => '1. Checkout Opened', 'key' => 'opened', 'color' => 'bg-slate-400', 'desc' => 'Session initialized'],
                    ['label' => '2. Information Entered (≥25%)', 'key' => 'info_entered', 'color' => 'bg-blue-500', 'desc' => 'Customer name or phone input'],
                    ['label' => '3. Shipping Details (≥50%)', 'key' => 'shipping', 'color' => 'bg-indigo-500', 'desc' => 'Address and district specified'],
                    ['label' => '4. Payment Selected (≥75%)', 'key' => 'payment', 'color' => 'bg-amber-500', 'desc' => 'COD or mobile banking method chosen'],
                    ['label' => '5. Converted / Order Placed', 'key' => 'converted', 'color' => 'bg-emerald-600', 'desc' => 'Successful purchase created'],
                ];
            @endphp

            <div class="mt-5 space-y-4">
                @foreach($funnelSteps as $idx => $step)
                    @php
                        $count = $metrics['funnel'][$step['key']] ?? 0;
                        $pctOfBase = round(($count / $baseCount) * 100, 1);
                        $prevCount = $idx > 0 ? ($metrics['funnel'][$funnelSteps[$idx - 1]['key']] ?? $count) : $count;
                        $dropOff = $prevCount > 0 ? round((($prevCount - $count) / $prevCount) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800">{{ $step['label'] }}</span>
                            <div class="flex items-center gap-3">
                                @if($idx > 0 && $dropOff > 0)
                                    <span class="text-[11px] font-medium text-rose-500">-{{ $dropOff }}% drop-off</span>
                                @endif
                                <span class="font-bold text-slate-900">{{ number_format($count) }}</span>
                                <span class="w-12 text-right text-slate-500">({{ $pctOfBase }}%)</span>
                            </div>
                        </div>
                        <div class="mt-1.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $step['color'] }} transition-all duration-500" style="width: {{ $pctOfBase }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- 7-Day Trend Mini-bars --}}
            <div class="mt-6 border-t border-slate-100 pt-4">
                <span class="text-xs font-semibold text-slate-700">7-Day Activity &amp; Conversion Trend</span>
                <div class="mt-3 grid grid-cols-7 gap-2">
                    @foreach($metrics['trends'] as $day)
                        @php
                            $maxDay = max(collect($metrics['trends'])->max('total'), 1);
                            $heightPct = min(max(round(($day['total'] / $maxDay) * 100), 12), 100);
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex h-20 w-full items-end justify-center rounded-lg bg-slate-50 p-1">
                                <div class="w-full rounded bg-brand-green-500 transition-all hover:opacity-80"
                                     style="height: {{ $heightPct }}%"
                                     title="{{ $day['date'] }}: {{ $day['total'] }} total, {{ $day['converted'] }} converted, {{ $day['incomplete'] }} incomplete">
                                </div>
                            </div>
                            <span class="mt-1.5 text-[10px] font-medium text-slate-500">{{ $day['date'] }}</span>
                            <span class="text-[10px] font-bold text-slate-700">{{ $day['total'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Insights & Breakdowns (1 col) --}}
        <div class="space-y-6">

            {{-- Abandonment by Progress Bracket --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">Drop-off by Progress Bracket</h2>
                <p class="text-xs text-slate-500">Where customers abandoned during incomplete sessions</p>

                @php
                    $brackets = [
                        ['label' => '0% – 25% (Cart / First Field)', 'count' => $metrics['progress_brackets']['0_25'] ?? 0, 'color' => 'bg-amber-400'],
                        ['label' => '26% – 50% (Contact Details)', 'count' => $metrics['progress_brackets']['26_50'] ?? 0, 'color' => 'bg-orange-400'],
                        ['label' => '51% – 75% (Address & Shipping)', 'count' => $metrics['progress_brackets']['51_75'] ?? 0, 'color' => 'bg-rose-400'],
                        ['label' => '76% – 99% (Payment Selection)', 'count' => $metrics['progress_brackets']['76_99'] ?? 0, 'color' => 'bg-purple-500'],
                    ];
                    $totalIncomplete = max($metrics['incomplete_sessions'], 1);
                @endphp

                <div class="mt-4 space-y-3">
                    @foreach($brackets as $b)
                        @php $bPct = round(($b['count'] / $totalIncomplete) * 100, 1); @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700">{{ $b['label'] }}</span>
                                <span class="font-bold text-slate-900">{{ $b['count'] }} <span class="font-normal text-slate-400">({{ $bPct }}%)</span></span>
                            </div>
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $b['color'] }}" style="width: {{ $bPct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Device Category Breakdown --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-900">Device Breakdown</h2>
                <p class="text-xs text-slate-500">Checkout sessions across customer devices</p>

                @php
                    $deviceTotal = max(array_sum($metrics['devices']), 1);
                @endphp

                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-2.5">
                        <span class="text-[11px] font-semibold text-slate-500">Desktop</span>
                        <div class="mt-1 text-base font-extrabold text-slate-900">{{ $metrics['devices']['desktop'] }}</div>
                        <span class="text-[10px] text-slate-400">{{ round(($metrics['devices']['desktop'] / $deviceTotal) * 100) }}%</span>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-2.5">
                        <span class="text-[11px] font-semibold text-slate-500">Mobile</span>
                        <div class="mt-1 text-base font-extrabold text-slate-900">{{ $metrics['devices']['mobile'] }}</div>
                        <span class="text-[10px] text-slate-400">{{ round(($metrics['devices']['mobile'] / $deviceTotal) * 100) }}%</span>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-2.5">
                        <span class="text-[11px] font-semibold text-slate-500">Tablet</span>
                        <div class="mt-1 text-base font-extrabold text-slate-900">{{ $metrics['devices']['tablet'] }}</div>
                        <span class="text-[10px] text-slate-400">{{ round(($metrics['devices']['tablet'] / $deviceTotal) * 100) }}%</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('admin.incomplete-orders.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">

            {{-- Date Range --}}
            <div>
                <label for="filter_date_range" class="block text-xs font-semibold text-slate-700">Time Range</label>
                <select id="filter_date_range" name="date_range" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    <option value="30_days" @selected(($filters['date_range'] ?? '') === '30_days' || empty($filters['date_range']))>Last 30 Days</option>
                    <option value="today" @selected(($filters['date_range'] ?? '') === 'today')>Today</option>
                    <option value="7_days" @selected(($filters['date_range'] ?? '') === '7_days')>Last 7 Days</option>
                    <option value="all" @selected(($filters['date_range'] ?? '') === 'all')>All Time</option>
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label for="filter_status" class="block text-xs font-semibold text-slate-700">Status</label>
                <select id="filter_status" name="status" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active (&lt;30m)</option>
                    <option value="incomplete" @selected(($filters['status'] ?? '') === 'incomplete')>Incomplete / Abandoned</option>
                    <option value="converted" @selected(($filters['status'] ?? '') === 'converted')>Converted</option>
                    <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Expired (&gt;7d)</option>
                </select>
            </div>

            {{-- Progress Range --}}
            <div>
                <label for="filter_progress" class="block text-xs font-semibold text-slate-700">Progress</label>
                <select id="filter_progress" name="progress_range" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    <option value="">All Progress</option>
                    <option value="0-25" @selected(($filters['progress_range'] ?? '') === '0-25')>0% – 25% (Opened / Started)</option>
                    <option value="26-50" @selected(($filters['progress_range'] ?? '') === '26-50')>26% – 50% (Contact Entered)</option>
                    <option value="51-75" @selected(($filters['progress_range'] ?? '') === '51-75')>51% – 75% (Address Entered)</option>
                    <option value="76-99" @selected(($filters['progress_range'] ?? '') === '76-99')>76% – 99% (Ready to Submit)</option>
                    <option value="100" @selected(($filters['progress_range'] ?? '') === '100')>100% (Converted)</option>
                </select>
            </div>

            {{-- Device --}}
            <div>
                <label for="filter_device" class="block text-xs font-semibold text-slate-700">Device</label>
                <select id="filter_device" name="device" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    <option value="">All Devices</option>
                    <option value="desktop" @selected(($filters['device'] ?? '') === 'desktop')>Desktop</option>
                    <option value="mobile" @selected(($filters['device'] ?? '') === 'mobile')>Mobile</option>
                    <option value="tablet" @selected(($filters['device'] ?? '') === 'tablet')>Tablet</option>
                </select>
            </div>

            {{-- Search & Submit --}}
            <div>
                <label for="filter_search" class="block text-xs font-semibold text-slate-700">Search</label>
                <div class="mt-1 flex items-center gap-1.5">
                    <input id="filter_search" type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Session ID or District…"
                           class="w-full rounded-xl border border-slate-200 p-2 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    <button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
                        Filter
                    </button>
                    @if(!empty(array_filter($filters)))
                        <a href="{{ route('admin.incomplete-orders.index') }}" class="rounded-xl border border-slate-200 p-2 text-xs font-medium text-slate-500 hover:bg-slate-100" title="Clear Filters">
                            ✕
                        </a>
                    @endif
                </div>
            </div>

        </div>
    </form>

    {{-- Sessions Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3.5 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Checkout Sessions ({{ number_format($sessions->total()) }})</h2>
            <span class="text-xs text-slate-400">Paging {{ $sessions->perPage() }} per page</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Session ID</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Progress</th>
                        <th class="px-4 py-3">Step</th>
                        <th class="px-4 py-3">Cart / Subtotal</th>
                        <th class="px-4 py-3">Device / OS</th>
                        <th class="px-4 py-3">Location</th>
                        @if($canViewIp)
                            <th class="px-4 py-3">Client IP</th>
                        @endif
                        <th class="px-4 py-3">Activity</th>
                        <th class="px-4 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sessions as $s)
                        <tr class="hover:bg-slate-50/60 transition">
                            {{-- Session ID --}}
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-700">
                                <span title="{{ $s->session_id }}">
                                    {{ Str::limit($s->session_id, 16) }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                @if($s->status === 'active')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 border border-blue-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Active
                                    </span>
                                @elseif($s->status === 'incomplete')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 border border-amber-200">
                                        Incomplete
                                    </span>
                                @elseif($s->status === 'converted')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200">
                                        ✓ Converted
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                        Expired
                                    </span>
                                @endif
                            </td>

                            {{-- Progress --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $s->progress_percent >= 80 ? 'bg-emerald-500' : ($s->progress_percent >= 50 ? 'bg-indigo-500' : 'bg-amber-500') }}"
                                             style="width: {{ $s->progress_percent }}%"></div>
                                    </div>
                                    <span class="font-bold text-slate-800">{{ $s->progress_percent }}%</span>
                                </div>
                            </td>

                            {{-- Current Step --}}
                            <td class="px-4 py-3 capitalize text-slate-700">
                                {{ str_replace('_', ' ', $s->current_step) }}
                            </td>

                            {{-- Cart --}}
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-800">{{ $s->cart_item_count }} items</span>
                                @if($s->cart_subtotal > 0)
                                    <span class="text-slate-400">· ৳{{ number_format($s->cart_subtotal) }}</span>
                                @endif
                            </td>

                            {{-- Device --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1 text-slate-700 capitalize">
                                    <span>{{ $s->device_category }}</span>
                                    @if($s->os)
                                        <span class="text-[10px] text-slate-400">({{ $s->os }})</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Location --}}
                            <td class="px-4 py-3">
                                @php
                                    $loc = trim(($s->city ? $s->city.', ' : '').($s->country ?: ($s->district ?: '')));
                                @endphp
                                @if($loc)
                                    <span class="text-slate-800">{{ $loc }}</span>
                                @else
                                    <span class="text-slate-400 italic">Location unavailable</span>
                                @endif
                            </td>

                            {{-- IP Address --}}
                            @if($canViewIp)
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-600">
                                    {{ $s->ip_address ?: '—' }}
                                </td>
                            @endif

                            {{-- Activity --}}
                            <td class="px-4 py-3 text-slate-500">
                                <div title="First: {{ $s->first_active_at?->format('Y-m-d H:i') }}">
                                    {{ $s->last_active_at ? $s->last_active_at->diffForHumans() : '—' }}
                                </div>
                            </td>

                            {{-- Action --}}
                            <td class="px-4 py-3 text-right">
                                @if($s->isConverted() && $s->order_id)
                                    <a href="{{ route('admin.orders.show', $s->order_id) }}"
                                       class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100">
                                        View Order #{{ $s->order_id }} →
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canViewIp ? 10 : 9 }}" class="px-4 py-12 text-center text-slate-400">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    🔍
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-700">No checkout sessions found</p>
                                <p class="mt-1 text-xs text-slate-500">Try adjusting your filters or date range.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

    {{-- Retention Settings Modal --}}
    @adminCan('incomplete_orders.manage_retention')
    <div x-show="retentionModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm"
         @keydown.escape.window="retentionModalOpen = false">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
             @click.away="retentionModalOpen = false">
            <h3 class="text-base font-bold text-slate-900">Checkout Analytics Retention Policy</h3>
            <p class="mt-1.5 text-xs text-slate-500">
                To comply with privacy practices and minimize database growth, old checkout sessions and associated IP logs can be safely purged.
            </p>

            <form method="POST" action="{{ route('admin.incomplete-orders.prune') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="retention_days" class="block text-xs font-semibold text-slate-700">Purge sessions older than</label>
                    <select id="retention_days" name="retention_days" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-xs font-semibold focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                        <option value="14">14 days (Strict privacy)</option>
                        <option value="30" selected>30 days (Recommended)</option>
                        <option value="60">60 days</option>
                        <option value="90">90 days (Quarterly)</option>
                    </select>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] text-amber-800">
                    ⚠️ This action permanently deletes checkout sessions older than the chosen retention period. Converted order records are kept safe in the main orders table.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="retentionModalOpen = false"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700">
                        Run Prune Now
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endadminCan

</div>
@endsection
