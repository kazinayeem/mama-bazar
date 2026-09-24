@extends('layouts.admin', ['headerTitle' => 'Customer Management'])

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="max-w-md w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer by name, phone, or email..." 
                class="w-full text-xs rounded-xl border border-slate-200 bg-white p-2.5 focus:border-brand-green-500 focus:outline-none">
        </form>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
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
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4 font-bold text-slate-800">{{ $c->name }}</td>
                            <td class="p-4 text-slate-700 font-medium">{{ $c->phone }}</td>
                            <td class="p-4 text-slate-500">{{ $c->email ?: '—' }}</td>
                            <td class="p-4 font-extrabold text-brand-green-700">{{ $c->orders_count }}</td>
                            <td class="p-4 text-slate-400">{{ $c->created_at->format('M d, Y') }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $c->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                    {{ $c->status }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <form action="{{ route('admin.customers.toggle', $c->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-[11px]">
                                        {{ $c->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-100 flex items-center justify-center">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
