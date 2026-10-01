@extends('layouts.admin', ['headerTitle' => 'Coupon Management'])

@section('content')
<div
    class="admin-page"
    x-data="{
        editOpen: false,
        editCoupon: null,
        editUrl: '',
        failedEditId: {{ old('edit_id') ? (int) old('edit_id') : 'null' }},
        itemsMap: @js($coupons->keyBy('id')),
        openEdit(c) {
            this.editCoupon = c;
            this.editUrl = '{{ route('admin.coupons.update', ':id') }}'.replace(':id', c.id);
            this.editOpen = true;
        }
    }"
    x-init="if (failedEditId && itemsMap[failedEditId]) openEdit(itemsMap[failedEditId])"
>
    <x-admin.page-header title="Coupons" :subtitle="$coupons->count().' active coupons'" />

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

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
                            <div class="mt-3 flex gap-2">
                                <button type="button" @click="openEdit(@js($coup))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs border border-[var(--admin-border)] bg-white text-slate-700 hover:bg-[var(--admin-muted)] flex-1">Edit</button>
                                <form action="{{ route('admin.coupons.destroy', $coup->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Delete this coupon?');">
                                    @csrf @method('DELETE')
                                    <x-admin.button type="submit" variant="destructive" size="sm" class="w-full">Delete</x-admin.button>
                                </form>
                            </div>
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
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" @click="openEdit(@js($coup))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs text-slate-600 hover:bg-slate-100">Edit</button>
                                            <form action="{{ route('admin.coupons.destroy', $coup->id) }}" method="POST" onsubmit="return confirm('Delete this coupon?');">
                                                @csrf @method('DELETE')
                                                <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">Delete</x-admin.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-admin.pagination :paginator="$coupons" />
            @endif
        </div>
    </div>

    {{-- Edit Coupon Modal --}}
    <x-admin.modal name="editOpen" x-title="'Edit Coupon: ' + (editCoupon ? editCoupon.code : '')" maxWidth="md">
        <form :action="editUrl" method="POST" class="space-y-3.5">
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_id" :value="editCoupon ? editCoupon.id : ''">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Coupon Code <span class="text-red-500">*</span></label>
                <input type="text" name="code" :value="editCoupon?.code" required class="admin-control w-full text-xs font-mono uppercase">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Type <span class="text-red-500">*</span></label>
                    <select name="discount_type" class="admin-control w-full text-xs bg-white" :value="editCoupon?.discount_type || 'fixed'">
                        <option value="fixed">Fixed (৳)</option>
                        <option value="percentage">Percentage (%)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Discount Value <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="discount_value" :value="editCoupon?.discount_value" required class="admin-control w-full text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Minimum Order Amount (৳)</label>
                <input type="number" step="0.01" min="0" name="min_order_amount" :value="editCoupon?.min_order_amount || 0" class="admin-control w-full text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Expiry Date</label>
                <input type="date" name="expiry_date" :value="editCoupon?.expiry_date ? editCoupon.expiry_date.substring(0, 10) : ''" class="admin-control w-full text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                <select name="status" class="admin-control w-full text-xs bg-white" :value="editCoupon?.status || 'active'">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="editOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
@endsection
