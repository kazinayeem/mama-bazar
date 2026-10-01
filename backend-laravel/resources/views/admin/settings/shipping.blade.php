@extends('layouts.admin', ['headerTitle' => 'Shipping & Delivery'])

@section('content')
<div
    class="admin-page"
    x-data="{
        editOpen: false,
        editMethod: {},
        editUrl: '',
        failedEditId: {{ old('edit_id') ? (int) old('edit_id') : 'null' }},
        itemsMap: @js($methods->keyBy('id')),
        openEdit(m) {
            this.editMethod = { ...m };
            this.editUrl = '{{ route('admin.shipping.update', ':id') }}'.replace(':id', m.id);
            this.editOpen = true;
        }
    }"
    x-init="if (failedEditId && itemsMap[failedEditId]) openEdit(itemsMap[failedEditId])"
>
    <x-admin.page-header title="Shipping & Delivery" :subtitle="$methods->count().' methods · charges controlled here, never hardcoded at checkout'">
        <x-slot:actions>
            <span class="hidden text-[11px] text-slate-400 sm:inline">Display order = Priority (low shows first)</span>
        </x-slot:actions>
    </x-admin.page-header>

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Create form --}}
        <div class="admin-surface h-fit space-y-3 p-4">
            <h3 class="border-b border-[var(--admin-border)] pb-2.5 text-sm font-bold text-slate-900">Add Shipping Method</h3>
            <form action="{{ route('admin.shipping.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Method Name *</label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Standard Delivery (Inside Dhaka)" class="admin-control w-full text-xs">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Charge (৳) *</label>
                        <input type="number" step="0.01" min="0" name="charge" required value="{{ old('charge', 60) }}" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Priority</label>
                        <input type="number" name="priority" value="{{ old('priority', ($methods->max('priority') ?? 0) + 10) }}" class="admin-control w-full text-xs">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Estimated Delivery Time</label>
                    <input type="text" name="estimated_delivery" value="{{ old('estimated_delivery') }}" placeholder="e.g. 24–48 hours" class="admin-control w-full text-xs">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Description</label>
                    <textarea name="description" rows="2" placeholder="Short customer-facing description" class="admin-control w-full text-xs">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Applicable Areas <span class="font-normal text-slate-400">(blank = nationwide)</span></label>
                    <input type="text" name="applicable_areas" value="{{ old('applicable_areas') }}" placeholder="e.g. Dhaka, Gazipur — or: inside_dhaka" class="admin-control w-full text-xs">
                    <p class="mt-1 text-[11px] text-slate-400">Comma-separated districts. Use <code>inside_dhaka</code> / <code>outside_dhaka</code> for zone rules.</p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Free above (৳)</label>
                        <input type="number" step="0.01" min="0" name="free_shipping_min_amount" value="{{ old('free_shipping_min_amount') }}" placeholder="Optional" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Status</label>
                        <select name="status" class="admin-control w-full text-xs bg-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                    <input type="checkbox" name="cod_available" value="1" checked class="rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500"> COD available for this method
                </label>
                <x-admin.button type="submit" size="sm" class="w-full">Create Shipping Method</x-admin.button>
            </form>
        </div>

        {{-- List --}}
        <div class="admin-table-wrap lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--admin-border)] px-4 py-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-600">Configured Methods ({{ $methods->count() }})</p>
                @if($methods->count() > 1)
                <form action="{{ route('admin.shipping.reorder') }}" method="POST" class="flex items-center gap-1.5" id="reorderForm">
                    @csrf
                    <input type="hidden" name="order" id="reorderInput" value="">
                    <p class="text-[11px] text-slate-400">Drag with ↑ ↓ buttons, then</p>
                    <x-admin.button type="button" variant="outline" size="sm" onclick="submitShippingOrder()">Save order</x-admin.button>
                </form>
                @endif
            </div>
            @if($methods->isEmpty())
                <x-admin.empty-state title="No shipping methods configured" description="Add your first method — checkout will show a clear message until at least one is active." />
            @else
                <div class="divide-y divide-slate-100" id="shippingList">
                    @foreach($methods as $m)
                    <div class="p-4" data-id="{{ $m->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-slate-900">{{ $m->name }}</p>
                                    <x-admin.badge :variant="$m->status === 'active' ? 'default' : 'muted'">{{ $m->status }}</x-admin.badge>
                                    @if(!$m->cod_available)<x-admin.badge variant="warning">No COD</x-admin.badge>@endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    <span class="font-extrabold text-brand-green-700">৳{{ number_format($m->charge, 0) }}</span>
                                    · {{ $m->estimated_delivery ?: 'Standard time' }}
                                    · Priority {{ $m->priority }}
                                    @if($m->free_shipping_min_amount)<span class="text-brand-orange-600">· Free ≥ ৳{{ number_format($m->free_shipping_min_amount, 0) }}</span>@endif
                                </p>
                                @if($m->description)<p class="mt-0.5 text-xs text-slate-400">{{ $m->description }}</p>@endif
                                <p class="mt-0.5 text-[11px] text-slate-400">Areas: {{ $m->applicable_areas ?: 'Nationwide (all districts)' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <span class="inline-flex flex-col gap-0.5 mr-0.5">
                                    <button type="button" onclick="moveShippingRow(this, -1)" title="Move up" class="rounded border border-slate-200 px-1 text-[10px] leading-4 text-slate-500 hover:bg-slate-100">▲</button>
                                    <button type="button" onclick="moveShippingRow(this, 1)" title="Move down" class="rounded border border-slate-200 px-1 text-[10px] leading-4 text-slate-500 hover:bg-slate-100">▼</button>
                                </span>
                                <form action="{{ route('admin.shipping.toggle', $m->id) }}" method="POST" class="inline">@csrf
                                    <x-admin.button type="submit" variant="outline" size="sm">{{ $m->status === 'active' ? 'Disable' : 'Enable' }}</x-admin.button>
                                </form>
                                <button type="button" @click="openEdit(@js($m))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs text-slate-600 hover:bg-slate-100">Edit</button>
                                <form action="{{ route('admin.shipping.destroy', $m->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete/deactivate this method? Orders keep history.')">@csrf @method('DELETE')
                                    <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">Delete</x-admin.button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Edit Shipping Method Modal --}}
    <x-admin.modal name="editOpen" x-title="'Edit Shipping Method: ' + (editMethod ? editMethod.name : '')" maxWidth="lg">
        <form :action="editUrl" method="POST" class="grid gap-3.5 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_id" :value="editMethod && editMethod.id ? editMethod.id : ''">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Method Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required x-model="editMethod.name" class="admin-control w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Charge (৳) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="charge" required x-model="editMethod.charge" class="admin-control w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Priority</label>
                <input type="number" name="priority" x-model="editMethod.priority" class="admin-control w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Estimated Delivery Time</label>
                <input type="text" name="estimated_delivery" x-model="editMethod.estimated_delivery" placeholder="e.g. 24–48 hours" class="admin-control w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Free Above (৳)</label>
                <input type="number" step="0.01" min="0" name="free_shipping_min_amount" x-model="editMethod.free_shipping_min_amount" placeholder="Optional" class="admin-control w-full text-xs">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Description</label>
                <textarea name="description" rows="2" class="admin-control w-full text-xs" x-model="editMethod.description"></textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Applicable Areas (blank = nationwide)</label>
                <input type="text" name="applicable_areas" x-model="editMethod.applicable_areas" class="admin-control w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Status</label>
                <select name="status" class="admin-control w-full text-xs bg-white" x-model="editMethod.status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex items-center pt-5">
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="cod_available" value="1" x-model="editMethod.cod_available" class="rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                    <span>COD Available</span>
                </label>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 sm:col-span-2 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="editOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
@push('scripts')
<script>
function moveShippingRow(btn, dir) {
    const row = btn.closest('#shippingList [data-id]');
    if (!row) return;
    const sibling = dir < 0 ? row.previousElementSibling : row.nextElementSibling;
    if (!sibling) return;
    if (dir < 0) row.parentNode.insertBefore(row, sibling);
    else row.parentNode.insertBefore(sibling, row);
}
function submitShippingOrder() {
    const ids = [...document.querySelectorAll('#shippingList [data-id]')].map(el => el.dataset.id);
    const form = document.getElementById('reorderForm');
    const body = new FormData();
    ids.forEach(id => body.append('order[]', id));
    fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body })
        .then(async (res) => {
            if (!res.ok) {
                let msg = 'Could not save the new order.';
                try { const j = await res.json(); if (j.message) msg = j.message; } catch (e) {}
                alert(msg);
                return;
            }
            window.location.reload();
        })
        .catch(() => alert('Network error while saving the order.'));
}
</script>
@endpush
@endsection
