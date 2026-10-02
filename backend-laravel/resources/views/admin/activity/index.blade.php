@extends('layouts.admin', ['headerTitle' => 'Super Admin Activity Monitor'])

@section('content')
<div class="admin-page space-y-6" x-data="{
    detailModalOpen: false,
    scheduledModalOpen: false,
    activeLog: null,
    loadingDetail: false,
    openDetail(log) {
        this.activeLog = log;
        this.detailModalOpen = true;
    },
    async fetchDetail(uuid) {
        this.loadingDetail = true;
        this.detailModalOpen = true;
        try {
            const res = await fetch('{{ url('admin/activity-monitor/entry') }}/' + uuid, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                this.activeLog = data.activity;
            }
        } catch (e) {
            console.error('Failed to load entry details:', e);
        } finally {
            this.loadingDetail = false;
        }
    }
}">
    <!-- Page Header with Export Controls -->
    <x-admin.page-header title="Activity Monitor & Security Audit" subtitle="Real-time visibility into administrative actions, customer events, orders, products, and system operations">
        <x-slot:actions>
            <button type="button" @click="scheduledModalOpen = true" class="inline-flex items-center gap-1.5 rounded-[6px] border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Scheduled Reports ({{ $scheduledReports->count() }})
            </button>

            <!-- Export Actions Dropdown / Buttons -->
            <div class="inline-flex rounded-[6px] shadow-xs" role="group">
                <a href="{{ route('admin.activity.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center gap-1 rounded-l-[6px] border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition" title="Download PDF Report">
                    <svg class="h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    PDF
                </a>
                <a href="{{ route('admin.activity.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex items-center gap-1 border-y border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition" title="Export as CSV">
                    <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </a>
                <a href="{{ route('admin.activity.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center gap-1 rounded-r-[6px] border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition" title="Export as Excel">
                    <svg class="h-3.5 w-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Excel
                </a>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if(session('success'))
        <div class="rounded-[8px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-[8px] border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Time Period Filter Tabs -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[8px] border border-slate-200 bg-white p-3 shadow-panel">
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="mr-2 font-bold uppercase tracking-wider text-slate-400 text-[10px]">Time Period:</span>
            @php
                $presets = [
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'last_7_days' => 'Last 7 Days',
                    'last_30_days' => 'Last 30 Days',
                    'this_month' => 'This Month',
                    'prev_month' => 'Previous Month',
                ];
            @endphp
            @foreach($presets as $key => $label)
                <a href="{{ route('admin.activity.index', array_merge(request()->except(['from', 'to', 'page']), ['preset' => $key])) }}"
                   class="rounded px-2.5 py-1 font-semibold transition {{ ($preset === $key && !request('from')) ? 'bg-brand-green-500 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="text-[11px] font-medium text-slate-500">
            Window: <span class="font-bold text-slate-700">{{ $from }}</span> to <span class="font-bold text-slate-700">{{ $to }}</span>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        <div class="rounded-[8px] border border-slate-200 bg-white p-3.5 shadow-panel">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Activities</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($statistics['total_activities']) }}</p>
            <p class="mt-1 text-[10.5px] text-slate-400">All recorded operational events</p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-3.5 shadow-panel">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Orders Received</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-brand-green-700">{{ number_format($statistics['orders_received']) }}</p>
            <p class="mt-1 text-[10.5px] text-slate-500">Today: <strong class="text-slate-800">{{ $statistics['orders_today'] }}</strong> orders</p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-3.5 shadow-panel">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Payments Received</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">৳{{ number_format($statistics['payments_received'], 0) }}</p>
            <p class="mt-1 text-[10.5px] text-slate-400">Successful order settlements</p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-3.5 shadow-panel">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Products (New / Mod)</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $statistics['products_created'] }} <span class="text-xs font-normal text-slate-400">/ {{ $statistics['products_updated'] }}</span></p>
            <p class="mt-1 text-[10.5px] text-slate-400">Catalog modifications</p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-3.5 shadow-panel">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Customers & Team</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $statistics['customers_registered'] }} <span class="text-xs font-normal text-slate-400">cust / {{ $statistics['active_team_members'] }} staff</span></p>
            <p class="mt-1 text-[10.5px] text-slate-400">New team: +{{ $statistics['team_members_added'] }}</p>
        </div>

        <div class="rounded-[8px] border border-slate-200 bg-white p-3.5 shadow-panel">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Email & Security</p>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-lg font-bold text-sky-700">{{ $statistics['email_operations'] }}</span>
                <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800" title="Failed Operations">{{ $statistics['failed_operations'] }} fail</span>
                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800" title="Security Events">{{ $statistics['security_events'] }} sec</span>
            </div>
            <p class="mt-1 text-[10.5px] text-slate-400">{{ $statistics['login_sessions'] }} total login sessions</p>
        </div>
    </div>

    <!-- Analytics Visualizations Cards -->
    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Daily Activity Trend -->
        <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel lg:col-span-2">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Activity Volume Trend</h3>
                    <p class="text-[11px] text-slate-400">Event distribution over time</p>
                </div>
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">Daily Events</span>
            </div>

            <div class="mt-4">
                @php
                    $maxVolume = max(array_values($analytics['daily_volume']) ?: [1]);
                    if ($maxVolume < 1) $maxVolume = 1;
                @endphp
                @if(empty($analytics['daily_volume']))
                    <div class="py-12 text-center text-xs text-slate-400">No activity recorded for this date window.</div>
                @else
                    <div class="space-y-2">
                        @foreach($analytics['daily_volume'] as $day => $count)
                            @php $pct = round(($count / $maxVolume) * 100); @endphp
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-20 font-mono text-[11px] text-slate-500 whitespace-nowrap">{{ $day }}</span>
                                <div class="flex-1 rounded-full bg-slate-100 h-3 overflow-hidden">
                                    <div class="h-full rounded-full bg-brand-green-500 transition-all duration-300" style="width: {{ max($pct, 2) }}%;"></div>
                                </div>
                                <span class="w-12 text-right font-bold text-slate-800 font-mono text-[11px]">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Module & Status Distribution -->
        <div class="space-y-4">
            <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 pb-2 border-b border-slate-100">
                    Module Distribution
                </h3>
                <div class="space-y-2 text-xs">
                    @forelse($analytics['modules'] as $mod => $cnt)
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 font-medium text-slate-700 capitalize">
                                <span class="h-2 w-2 rounded-full {{ match($mod) {
                                    'orders' => 'bg-emerald-500',
                                    'products' => 'bg-sky-500',
                                    'customers' => 'bg-purple-500',
                                    'team' => 'bg-amber-500',
                                    'email' => 'bg-blue-500',
                                    'security' => 'bg-rose-500',
                                    default => 'bg-slate-500',
                                } }}"></span>
                                {{ $mod }}
                            </span>
                            <span class="font-bold text-slate-800 font-mono text-[11px]">{{ number_format($cnt) }}</span>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs">No module activities.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 pb-2 border-b border-slate-100">
                    Top Operational Events
                </h3>
                <div class="space-y-1.5 text-xs">
                    @forelse($analytics['top_events'] as $evt => $cnt)
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[10.5px] text-slate-600 truncate max-w-[170px]" title="{{ $evt }}">{{ $evt }}</span>
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700 font-mono">{{ $cnt }}</span>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs">No events logged.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filter Toolbar -->
    <div class="rounded-[8px] border border-slate-200 bg-white p-4 shadow-panel">
        <form method="GET" action="{{ route('admin.activity.index') }}" class="space-y-3">
            <input type="hidden" name="preset" value="custom">

            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                <!-- Keyword Search -->
                <div class="lg:col-span-2">
                    <label class="mb-1 block text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Keyword Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search description, actor, IP, target..."
                        class="admin-control w-full text-xs">
                </div>

                <!-- Module -->
                <div>
                    <label class="mb-1 block text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Module</label>
                    <select name="module" class="admin-control w-full text-xs">
                        <option value="">All Modules</option>
                        <option value="orders" {{ ($filters['module'] ?? '') === 'orders' ? 'selected' : '' }}>Orders</option>
                        <option value="products" {{ ($filters['module'] ?? '') === 'products' ? 'selected' : '' }}>Products</option>
                        <option value="customers" {{ ($filters['module'] ?? '') === 'customers' ? 'selected' : '' }}>Customers</option>
                        <option value="team" {{ ($filters['module'] ?? '') === 'team' ? 'selected' : '' }}>Team</option>
                        <option value="email" {{ ($filters['module'] ?? '') === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="security" {{ ($filters['module'] ?? '') === 'security' ? 'selected' : '' }}>Security</option>
                        <option value="system" {{ ($filters['module'] ?? '') === 'system' ? 'selected' : '' }}>System</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="mb-1 block text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Status</label>
                    <select name="status" class="admin-control w-full text-xs">
                        <option value="">All Statuses</option>
                        <option value="success" {{ ($filters['status'] ?? '') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failure" {{ ($filters['status'] ?? '') === 'failure' ? 'selected' : '' }}>Failure</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="mb-1 block text-[10.5px] font-bold uppercase tracking-wider text-slate-500">From Date</label>
                    <input type="date" name="from" value="{{ $from }}" class="admin-control w-full text-xs">
                </div>

                <!-- Date To -->
                <div>
                    <label class="mb-1 block text-[10.5px] font-bold uppercase tracking-wider text-slate-500">To Date</label>
                    <input type="date" name="to" value="{{ $to }}" class="admin-control w-full text-xs">
                </div>
            </div>

            <!-- Second Row Filters -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                <div class="flex flex-wrap items-center gap-4 text-xs">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-slate-700">
                        <input type="checkbox" name="failed_only" value="1" {{ !empty($filters['failed_only']) ? 'checked' : '' }} class="rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                        <span>Failed Operations Only</span>
                    </label>

                    <label class="inline-flex items-center gap-1.5 cursor-pointer font-medium text-slate-700">
                        <input type="checkbox" name="security_only" value="1" {{ !empty($filters['security_only']) ? 'checked' : '' }} class="rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                        <span>Security Events Only</span>
                    </label>

                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400">Sort:</span>
                        <select name="sort" class="admin-control text-xs py-1">
                            <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-[6px] bg-brand-green-500 px-4 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-brand-green-600 transition">
                        Filter Activities
                    </button>
                    <a href="{{ route('admin.activity.index') }}" class="rounded-[6px] border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Unified Activity Timeline Table -->
    <div class="admin-table-wrap">
        <div class="border-b bg-slate-50 px-4 py-3 sm:flex sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Unified Activity Timeline</h3>
                <p class="text-[11px] text-slate-400">Showing page {{ $activities->currentPage() }} of {{ $activities->lastPage() }} (Total matching: {{ number_format($activities->total()) }})</p>
            </div>
            <div class="mt-2 sm:mt-0 text-xs text-slate-500">
                Updated in real-time
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="admin-table min-w-full">
                <thead class="border-b text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Time</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Event & Description</th>
                        <th class="px-4 py-3">Module</th>
                        <th class="px-4 py-3">Target</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Source & IP</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y text-xs">
                    @forelse($activities as $act)
                        @php
                            $moduleBadgeCls = match($act->module) {
                                'orders' => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
                                'products' => 'bg-sky-50 text-sky-800 ring-1 ring-sky-200',
                                'customers' => 'bg-purple-50 text-purple-800 ring-1 ring-purple-200',
                                'team' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
                                'email' => 'bg-blue-50 text-blue-800 ring-1 ring-blue-200',
                                'security' => 'bg-rose-50 text-rose-800 ring-1 ring-rose-200',
                                default => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Time -->
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600 font-mono text-[11px]">
                                {{ optional($act->occurred_at)->format('Y-m-d H:i:s') }}
                            </td>

                            <!-- Actor -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-700">
                                        {{ strtoupper(substr($act->actor_name ?: 'S', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900 truncate">{{ $act->actor_name ?: 'System' }}</p>
                                        <p class="text-[10px] text-slate-400 capitalize">
                                            {{ $act->actor_role ?: $act->actor_type }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Event & Description -->
                            <td class="px-4 py-3">
                                <div class="max-w-md">
                                    <p class="font-semibold text-slate-800">{{ $act->human_action }}</p>
                                    @if($act->description)
                                        <p class="text-[11px] text-slate-500 truncate" title="{{ $act->description }}">{{ $act->description }}</p>
                                    @endif
                                </div>
                            </td>

                            <!-- Module -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $moduleBadgeCls }}">
                                    {{ $act->module }}
                                </span>
                            </td>

                            <!-- Target -->
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                @if($act->subject_type)
                                    <span class="font-medium text-slate-800">{{ $act->subject_type }}</span>
                                    <span class="font-mono text-slate-500">#{{ $act->subject_id }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($act->status === 'success')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Success
                                    </span>
                                @elseif($act->status === 'failure')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Failure
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        {{ ucfirst($act->status) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Source & IP -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="font-mono text-[11px] text-slate-600">{{ $act->ip_address ?: '—' }}</p>
                                <p class="text-[10px] text-slate-400 truncate max-w-[130px]" title="{{ $act->location }}">
                                    {{ $act->location ?: ucfirst($act->source) }}
                                </p>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" @click="fetchDetail('{{ $act->uuid }}')"
                                    class="inline-flex items-center gap-1 rounded px-2.5 py-1 text-xs font-semibold text-brand-green-700 hover:bg-brand-green-50 transition cursor-pointer">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                                No activity records match your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

    <!-- Activity Detail Modal (Alpine.js) -->
    <x-admin.modal name="detailModalOpen" title="Activity Event Audit Detail" subtitle="Complete cryptographic and operational audit record">
        <template x-if="loadingDetail">
            <div class="py-12 text-center text-xs text-slate-500">
                <svg class="mx-auto h-6 w-6 animate-spin text-brand-green-600 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Loading audit trail details...
            </div>
        </template>

        <template x-if="!loadingDetail && activeLog">
            <div class="space-y-4 text-xs">
                <!-- Header Card -->
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 text-sm" x-text="activeLog.human_action"></span>
                        <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase"
                              :class="activeLog.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                              x-text="activeLog.status"></span>
                    </div>
                    <p class="mt-1 text-slate-600 text-[11.5px]" x-text="activeLog.description || 'No extended description recorded.'"></p>
                </div>

                <!-- Attributes Grid -->
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded border border-slate-100 p-2.5 bg-white">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Event UUID</span>
                        <span class="font-mono text-slate-800 select-all" x-text="activeLog.uuid"></span>
                    </div>

                    <div class="rounded border border-slate-100 p-2.5 bg-white">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Occurred At</span>
                        <span class="font-mono text-slate-800" x-text="activeLog.occurred_at"></span>
                    </div>

                    <div class="rounded border border-slate-100 p-2.5 bg-white">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Actor</span>
                        <span class="font-medium text-slate-800" x-text="activeLog.actor_name + ' (' + (activeLog.actor_role || activeLog.actor_type) + ')'"></span>
                    </div>

                    <div class="rounded border border-slate-100 p-2.5 bg-white">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Target</span>
                        <span class="font-medium text-slate-800" x-text="(activeLog.subject_type || '—') + (activeLog.subject_id ? ' #' + activeLog.subject_id : '')"></span>
                    </div>

                    <div class="rounded border border-slate-100 p-2.5 bg-white">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Source & IP</span>
                        <span class="font-mono text-slate-800" x-text="activeLog.ip_address + ' [' + activeLog.source + ']'"></span>
                    </div>

                    <div class="rounded border border-slate-100 p-2.5 bg-white">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Approximate Location</span>
                        <span class="font-medium text-slate-800" x-text="activeLog.location"></span>
                    </div>
                </div>

                <!-- Changes / Diff Section -->
                <template x-if="activeLog.old_values || activeLog.new_values">
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <h4 class="font-bold text-slate-700 text-xs mb-2">Value Modifications (Before & After):</h4>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400">Previous Values</span>
                                <pre class="mt-1 rounded bg-slate-50 p-2 font-mono text-[11px] text-slate-700 overflow-x-auto max-h-40" x-text="JSON.stringify(activeLog.old_values, null, 2) || 'None'"></pre>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400">Updated Values</span>
                                <pre class="mt-1 rounded bg-slate-50 p-2 font-mono text-[11px] text-slate-700 overflow-x-auto max-h-40" x-text="JSON.stringify(activeLog.new_values, null, 2) || 'None'"></pre>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Metadata Section -->
                <template x-if="activeLog.metadata && Object.keys(activeLog.metadata).length > 0">
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <h4 class="font-bold text-slate-700 text-xs mb-2">Event Metadata:</h4>
                        <pre class="rounded bg-slate-50 p-2.5 font-mono text-[11px] text-slate-700 overflow-x-auto max-h-40" x-text="JSON.stringify(activeLog.metadata, null, 2)"></pre>
                    </div>
                </template>
            </div>
        </template>
    </x-admin.modal>

    <!-- Scheduled Reports Management Modal -->
    <x-admin.modal name="scheduledModalOpen" title="Automated Scheduled Activity Reports" subtitle="Configure automated recurring delivery of operational and audit reports">
        <div class="space-y-5 text-xs">
            <!-- Create Report Form -->
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h4 class="font-bold text-slate-800 text-xs mb-3">Create New Scheduled Report</h4>
                <form action="{{ route('admin.activity.scheduled.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-slate-600 font-semibold text-[11px]">Report Title *</label>
                            <input name="title" required placeholder="e.g. Weekly Security & Order Digest" class="admin-control w-full text-xs">
                        </div>
                        <div>
                            <label class="mb-1 block text-slate-600 font-semibold text-[11px]">Report Category *</label>
                            <select name="report_type" required class="admin-control w-full text-xs">
                                <option value="full">Full Platform Activity</option>
                                <option value="orders">Orders & Sales</option>
                                <option value="products">Products & Inventory</option>
                                <option value="customers">Customers</option>
                                <option value="security">Team Security & Logins</option>
                                <option value="email">Email Operations</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-slate-600 font-semibold text-[11px]">Frequency *</label>
                            <select name="frequency" required class="admin-control w-full text-xs">
                                <option value="daily">Daily (Every 24 hours)</option>
                                <option value="weekly" selected>Weekly (Every 7 days)</option>
                                <option value="monthly">Monthly (Every 30 days)</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-slate-600 font-semibold text-[11px]">Format *</label>
                            <select name="format" required class="admin-control w-full text-xs">
                                <option value="pdf">PDF Document</option>
                                <option value="csv">CSV Spreadsheet</option>
                                <option value="xlsx">Excel Workbook</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-slate-600 font-semibold text-[11px]">Recipient Email(s) * <span class="font-normal text-slate-400">(comma-separated)</span></label>
                            <input name="recipients" required placeholder="admin@mama-bazar.com, superadmin@mama-bazar.com" class="admin-control w-full text-xs">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="rounded-[6px] bg-brand-green-500 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-brand-green-600 transition">
                            Save Scheduled Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Reports Table -->
            <div>
                <h4 class="font-bold text-slate-800 text-xs mb-2">Existing Scheduled Reports ({{ $scheduledReports->count() }})</h4>
                <div class="rounded border border-slate-200 overflow-hidden">
                    <table class="admin-table min-w-full">
                        <thead class="bg-slate-50 text-[10.5px] uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Title</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Frequency</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y text-xs">
                            @forelse($scheduledReports as $sr)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-800">
                                        {{ $sr->title }}
                                        <span class="block text-[10px] text-slate-400 truncate max-w-xs">{{ $sr->recipients }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 uppercase text-[10px] font-bold">{{ $sr->report_type }}</td>
                                    <td class="px-3 py-2 text-slate-600 capitalize">{{ $sr->frequency }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $sr->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $sr->is_active ? 'Active' : 'Paused' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <form action="{{ route('admin.activity.scheduled.run', $sr->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-brand-green-700 hover:underline font-semibold text-[11px]" title="Execute now">Run Now</button>
                                            </form>
                                            <form action="{{ route('admin.activity.scheduled.toggle', $sr->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-slate-600 hover:underline font-medium text-[11px]">
                                                    {{ $sr->is_active ? 'Pause' : 'Resume' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.activity.scheduled.destroy', $sr->id) }}" method="POST" onsubmit="return confirm('Delete scheduled report?')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:underline font-medium text-[11px]">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-slate-400">
                                        No scheduled reports configured.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-admin.modal>
</div>
@endsection
