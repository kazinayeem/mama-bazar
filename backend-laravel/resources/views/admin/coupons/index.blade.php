@extends('layouts.admin', ['headerTitle' => 'Coupon Management'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Coupons" :subtitle="$coupons->count().' active coupons'" />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="admin-surface h-fit space-y-3 p-4">
            <h3 class="border-b border-[var(--admin-border)] pb-2.5 text-sm font-bold text-slate-900">Create Coupon</h3>
            <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-3">
                @csrf
                <x-admin.input label="Coupon Code *" name="code" required placeholder="e.g. EID50" class="font-mono uppercase" />
                <x-admin.select label="Discount Type *" name="discount_type" required>
                    <option value="fixed">Fixed Amount (৳)</option>
                    <option value="percentage">Percentage (%)</option>
                </x-admin.select>
                <x-admin.input label="Discount Value *" type="number" name="discount_value" step="0.01" required placeholder="e.g. 50" />
                <x-admin.input label="Minimum Order Amount (৳)" type="number" name="min_order_amount" step="0.01" value="0" />
                <x-admin.input label="Expiry Date (Optional)" type="date" name="expiry_date" />
                <x-admin.button type="submit" class="w-full" size="sm">Create Coupon</x-admin.button>
            </form>
        </div>

        <div class="admin-table-wrap lg:col-span-2">
            <div class="border-b border-[var(--admin-border)] px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">
                Active Coupons ({{ $coupons->count() }})
            </div>
            @if($coupons->isEmpty())
                <x-admin.empty-state title="No coupons created yet" description="Create your first coupon using the form." />
            @else
                <div class="md:hidden">
                    @foreach($coupons as $coup)
                        <div class="admin-mobile-card">
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
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="destructive" size="sm" class="w-full">Delete</x-admin.button>
                            </form>
                        </div>
                    @endforeach
                </div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Min Spend</th>
                                <th>Expires</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coupons as $coup)
                                <tr>
                                    <td class="font-mono font-bold text-brand-green-700">{{ $coup->code }}</td>
                                    <td class="font-semibold text-slate-800">
                                        {{ $coup->discount_type === 'percentage' ? $coup->discount_value . '%' : '৳' . number_format($coup->discount_value, 0) }}
                                    </td>
                                    <td class="text-slate-600">৳{{ number_format($coup->min_order_amount, 0) }}</td>
                                    <td class="text-slate-500">{{ $coup->expiry_date ?: 'No expiry' }}</td>
                                    <td class="text-right">
                                        <form action="{{ route('admin.coupons.destroy', $coup->id) }}" method="POST" onsubmit="return confirm('Delete this coupon?');">
                                            @csrf @method('DELETE')
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
</div>
@endsection
