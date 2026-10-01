@extends('layouts.admin', ['headerTitle' => 'Shipping & Delivery'])

@section('content')
<div class="admin-page" x-data="{ editingId: null }">
    <x-admin.page-header title="Shipping & Delivery" :subtitle="$methods->count().' methods · charges controlled here, never hardcoded at checkout'">
        <x-slot:actions>
            <span class="hidden text-[11px] text-slate-400 sm:inline">Display order = Priority (low shows first)</span>
        </x-slot:actions>
    </x-admin.page-header>

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
                        <select name="status" class="admin-control w-full text-xs">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                    <input type="checkbox" name="cod_available" value="1" checked class="rounded border-slate-300 text-brand-green-600"> COD available for this method
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
                                <form action="{{ route('admin.shipping.toggle', $m->id) }}" method="POST" class="inline">@csrf
                                    <x-admin.button type="submit" variant="outline" size="sm">{{ $m->status === 'active' ? 'Disable' : 'Enable' }}</x-admin.button>
                                </form>
                                <x-admin.button type="button" variant="ghost" size="sm" @click="editingId = editingId === {{ $m->id }} ? null : {{ $m->id }}">Edit</x-admin.button>
                                <form action="{{ route('admin.shipping.destroy', $m->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete/deactivate this method? Orders keep history.')">@csrf @method('DELETE')
                                    <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">Delete</x-admin.button>
                                </form>
                            </div>
                        </div>
                        <div x-show="editingId === {{ $m->id }}" x-cloak class="mt-3 rounded-[8px] border border-[var(--admin-border)] bg-slate-50/60 p-3">
                            <form action="{{ route('admin.shipping.update', $m->id) }}" method="POST" class="grid gap-2 sm:grid-cols-2">
                                @csrf @method('PUT')
                                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-600">Name *</label><input type="text" name="name" required value="{{ $m->name }}" class="admin-control w-full text-xs"></div>
                                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Charge (৳) *</label><input type="number" step="0.01" min="0" name="charge" required value="{{ $m->charge }}" class="admin-control w-full text-xs"></div>
                                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Priority</label><input type="number" name="priority" value="{{ $m->priority }}" class="admin-control w-full text-xs"></div>
                                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Estimated time</label><input type="text" name="estimated_delivery" value="{{ $m->estimated_delivery }}" class="admin-control w-full text-xs"></div>
                                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Free above (৳)</label><input type="number" step="0.01" min="0" name="free_shipping_min_amount" value="{{ $m->free_shipping_min_amount }}" class="admin-control w-full text-xs"></div>
                                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-600">Description</label><textarea name="description" rows="2" class="admin-control w-full text-xs">{{ $m->description }}</textarea></div>
                                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-600">Applicable areas (blank = nationwide)</label><input type="text" name="applicable_areas" value="{{ $m->applicable_areas }}" class="admin-control w-full text-xs"></div>
                                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Status</label><select name="status" class="admin-control w-full text-xs"><option value="active" @selected($m->status==='active')>Active</option><option value="inactive" @selected($m->status==='inactive')>Inactive</option></select></div>
                                <div class="flex items-end"><label class="flex items-center gap-2 text-xs font-semibold text-slate-600"><input type="checkbox" name="cod_available" value="1" @checked($m->cod_available) class="rounded border-slate-300 text-brand-green-600"> COD available</label></div>
                                <div class="flex justify-end gap-2 sm:col-span-2">
                                    <x-admin.button type="button" variant="outline" size="sm" @click="editingId = null">Cancel</x-admin.button>
                                    <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script>
function submitShippingOrder() {
    const ids = [...document.querySelectorAll('#shippingList [data-id]')].map(el => el.dataset.id);
    const form = document.getElementById('reorderForm');
    const input = document.getElementById('reorderInput');
    const fd = new FormData(form);
    fd.set('order', JSON.stringify(ids));
    // Server expects array; submit via fetch with JSON-ish form
    const body = new FormData();
    ids.forEach(id => body.append('order[]', id));
    fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'text/html' }, body })
        .then(() => window.location.reload());
}
</script>
@endpush
@endsection
