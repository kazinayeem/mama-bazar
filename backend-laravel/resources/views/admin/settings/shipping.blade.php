@extends('layouts.admin', ['headerTitle' => 'Shipping Methods'])

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="admin-surface p-4 space-y-4 h-fit">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Add Shipping Method</h3>

        <form action="{{ route('admin.shipping.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Method Name *</label>
                <input type="text" name="name" required placeholder="e.g. Inside Dhaka Express" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Delivery Charge (৳) *</label>
                <input type="number" step="0.01" name="charge" required placeholder="60" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Estimated Delivery Time</label>
                <input type="text" name="estimated_delivery" placeholder="e.g. 24 - 48 Hours" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-[6px] bg-brand-green-500 text-sm font-medium text-white hover:bg-brand-green-600">
                Create Shipping Method
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 admin-table-wrap">
        <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Configured Shipping Methods ({{ $methods->count() }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="p-4">Name</th>
                        <th class="p-4">Delivery Charge</th>
                        <th class="p-4">Delivery Time</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($methods as $m)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4 font-bold text-slate-800">{{ $m->name }}</td>
                            <td class="p-4 font-extrabold text-brand-green-700">৳{{ number_format($m->charge, 0) }}</td>
                            <td class="p-4 text-slate-500">{{ $m->estimated_delivery ?: 'Standard' }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $m->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400">No shipping methods configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
