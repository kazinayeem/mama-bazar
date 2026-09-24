@extends('layouts.admin', ['headerTitle' => 'Customer Management'])

@section('content')
<div class="space-y-6">

    <form method="GET" action="{{ route('admin.customers.index') }}" class="w-full max-w-md">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer by name, phone, or email..."
            class="admin-control w-full focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
    </form>

    <div class="admin-table-wrap">
        @if($customers->isEmpty())
            <x-admin.empty-state title="No customers found" description="Try a different search term." />
        @else
            {{-- Mobile cards --}}
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach($customers as $c)
                    @php $initial = strtoupper(substr($c->name ?? '?', 0, 1)); @endphp
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-green-50 text-sm font-bold text-brand-green-700">{{ $initial }}</div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $c->name }}</p>
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

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="p-4">Customer</th>
                            <th class="p-4">Phone</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Orders</th>
                            <th class="p-4">Joined</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($customers as $c)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="p-4 font-bold text-slate-800">{{ $c->name }}</td>
                                <td class="p-4 font-medium text-slate-700">{{ $c->phone }}</td>
                                <td class="p-4 text-slate-500">{{ $c->email ?: '—' }}</td>
                                <td class="p-4 font-extrabold text-brand-green-700">{{ $c->orders_count }}</td>
                                <td class="p-4 text-slate-400">{{ $c->created_at->format('M d, Y') }}</td>
                                <td class="p-4">
                                    <x-admin.badge :variant="$c->status === 'active' ? 'success' : 'destructive'">{{ $c->status }}</x-admin.badge>
                                </td>
                                <td class="p-4 text-right">
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

            <x-admin.pagination :paginator="$customers" class="border-t border-slate-100" />
        @endif
    </div>

</div>
@endsection
