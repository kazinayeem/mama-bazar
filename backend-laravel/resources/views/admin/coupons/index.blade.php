@extends('layouts.admin', ['headerTitle' => 'Coupon Management'])

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Coupon Form -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-soft space-y-4 h-fit">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Create Coupon</h3>

        <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Coupon Code *</label>
                <input type="text" name="code" required placeholder="e.g. EID50" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none uppercase font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Discount Type *</label>
                <select name="discount_type" required class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none bg-white">
                    <option value="fixed">Fixed Amount (৳)</option>
                    <option value="percentage">Percentage (%)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Discount Value *</label>
                <input type="number" step="0.01" name="discount_value" required placeholder="e.g. 50" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Minimum Order Amount (৳)</label>
                <input type="number" step="0.01" name="min_order_amount" value="0" placeholder="0" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Expiry Date (Optional)</label>
                <input type="date" name="expiry_date" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-sm transition">
                Create Coupon
            </button>
        </form>
    </div>

    <!-- Coupons Table -->
    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Active Coupons ({{ $coupons->count() }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="p-4">Code</th>
                        <th class="p-4">Discount</th>
                        <th class="p-4">Min Spend</th>
                        <th class="p-4">Expires</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($coupons as $coup)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4 font-mono font-bold text-brand-green-700">{{ $coup->code }}</td>
                            <td class="p-4 font-semibold text-slate-800">
                                {{ $coup->discount_type === 'percentage' ? $coup->discount_value . '%' : '৳' . number_format($coup->discount_value, 0) }}
                            </td>
                            <td class="p-4 text-slate-600">৳{{ number_format($coup->min_order_amount, 0) }}</td>
                            <td class="p-4 text-slate-500">{{ $coup->expiry_date ?: 'No expiry' }}</td>
                            <td class="p-4 text-right">
                                <form action="{{ route('admin.coupons.destroy', $coup->id) }}" method="POST" onsubmit="return confirm('Delete this coupon?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 rounded-lg border border-red-200 hover:bg-red-50 text-red-600 font-semibold text-[11px]">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">No coupons created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
