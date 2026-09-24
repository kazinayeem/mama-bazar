@extends('layouts.admin', ['headerTitle' => 'Coupon Management'])

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    <div class="h-fit space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-soft">
        <h3 class="border-b border-slate-100 pb-3 text-sm font-bold text-slate-900">Create Coupon</h3>

        <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Coupon Code *</label>
                <input type="text" name="code" required placeholder="e.g. EID50" class="w-full rounded-xl border border-slate-200 p-2.5 font-mono text-xs uppercase focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Discount Type *</label>
                <select name="discount_type" required class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs focus:border-brand-green-500 focus:outline-none">
                    <option value="fixed">Fixed Amount (৳)</option>
                    <option value="percentage">Percentage (%)</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Discount Value *</label>
                <input type="number" step="0.01" name="discount_value" required placeholder="e.g. 50" class="w-full rounded-xl border border-slate-200 p-2.5 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Minimum Order Amount (৳)</label>
                <input type="number" step="0.01" name="min_order_amount" value="0" placeholder="0" class="w-full rounded-xl border border-slate-200 p-2.5 text-xs focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Expiry Date (Optional)</label>
                <input type="date" name="expiry_date" class="w-full rounded-xl border border-slate-200 p-2.5 text-xs focus:border-brand-green-500 focus:outline-none">
            </div>

            <x-admin.button type="submit" class="w-full">Create Coupon</x-admin.button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft lg:col-span-2">
        <div class="border-b border-slate-100 p-4 text-xs font-bold uppercase tracking-wider text-slate-700">
            Active Coupons ({{ $coupons->count() }})
        </div>

        @if($coupons->isEmpty())
            <x-admin.empty-state title="No coupons created yet" description="Create your first coupon using the form." class="border-0 shadow-none" />
        @else
            {{-- Mobile cards --}}
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach($coupons as $coup)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-mono text-sm font-bold text-brand-green-700">{{ $coup->code }}</p>
                            <x-admin.badge variant="default">
                                {{ $coup->discount_type === 'percentage' ? $coup->discount_value . '% off' : '৳' . number_format($coup->discount_value, 0) . ' off' }}
                            </x-admin.badge>
                        </div>
                        <dl class="mt-2 space-y-1 text-xs text-slate-600">
                            <div class="flex justify-between gap-2">
                                <dt class="font-semibold text-slate-400">Min spend</dt>
                                <dd>৳{{ number_format($coup->min_order_amount, 0) }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="font-semibold text-slate-400">Expires</dt>
                                <dd>{{ $coup->expiry_date ?: 'No expiry' }}</dd>
                            </div>
                        </dl>
                        <form action="{{ route('admin.coupons.destroy', $coup->id) }}" method="POST" class="mt-3" onsubmit="return confirm('Delete this coupon?');">
                            @csrf
                            @method('DELETE')
                            <x-admin.button type="submit" variant="destructive" size="sm" class="w-full">Delete</x-admin.button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="p-4">Code</th>
                            <th class="p-4">Discount</th>
                            <th class="p-4">Min Spend</th>
                            <th class="p-4">Expires</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($coupons as $coup)
                            <tr class="transition hover:bg-slate-50/60">
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
                                        <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">Delete</x-admin.button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
