@extends('layouts.admin', ['headerTitle' => 'Customer Management'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Customers" :subtitle="$customers->total().' registered customers'" />

    <form method="GET" action="{{ route('admin.customers.index') }}" class="admin-filter-bar">
        <x-admin.search-input name="search" placeholder="Search by name, phone, or email..." />
    </form>

    <div class="admin-table-wrap">
        @if($customers->isEmpty())
            <x-admin.empty-state title="No customers found" description="Try a different search term." />
        @else
            <div class="md:hidden">
                @foreach($customers as $c)
                    @php $initial = strtoupper(substr($c->name ?? '?', 0, 1)); @endphp
                    <div class="admin-mobile-card">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-green-50 text-sm font-bold text-brand-green-700">{{ $initial }}</div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $c->name }}</p>
                                    <x-admin.badge :variant="$c->status === 'active' ? 'success' : 'destructive'">{{ $c->status }}</x-admin.badge>
                                </div>
                                <p class="mt-0.5 text-xs font-medium text-slate-700">{{ $c->phone }}</p>
                                @if($c->email)
                                    <p class="truncate text-xs text-slate-500">{{ $c->email }}</p>
                                @endif
                                <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">
                                    <div><span class="font-semibold text-slate-400">Orders</span> {{ $c->orders_count }}</div>
                                    <div><span class="font-semibold text-slate-400">Joined</span> {{ $c->created_at->format('M d, Y') }}</div>
                                </dl>
                            </div>
                        </div>
                        <form action="{{ route('admin.customers.toggle', $c->id) }}" method="POST" class="mt-3">
                            @csrf
                            <x-admin.button type="submit" variant="outline" size="sm" class="w-full">
                                {{ $c->status === 'active' ? 'Deactivate' : 'Activate' }}
                            </x-admin.button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th class="admin-hide-sm">Email</th>
                            <th>Orders</th>
                            <th class="admin-hide-md">Joined</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $c)
                            <tr>
                                <td class="font-semibold text-slate-900">{{ $c->name }}</td>
                                <td class="font-medium text-slate-700">{{ $c->phone }}</td>
                                <td class="admin-hide-sm text-slate-500">{{ $c->email ?: '—' }}</td>
                                <td class="font-bold text-brand-green-700">{{ $c->orders_count }}</td>
                                <td class="admin-hide-md text-slate-400">{{ $c->created_at->format('M d, Y') }}</td>
                                <td><x-admin.badge :variant="$c->status === 'active' ? 'success' : 'destructive'">{{ $c->status }}</x-admin.badge></td>
                                <td class="text-right">
                                    <form action="{{ route('admin.customers.toggle', $c->id) }}" method="POST">
                                        @csrf
                                        <x-admin.button type="submit" variant="outline" size="sm">
                                            {{ $c->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </x-admin.button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination :paginator="$customers" />
        @endif
    </div>
</div>
@endsection
