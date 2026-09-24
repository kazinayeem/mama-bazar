@extends('layouts.admin', ['headerTitle' => 'Payment Methods'])

@php
    $methodsPayload = $methods->map(function ($m) {
        return [
            'id' => $m->id,
            'code' => $m->code,
            'name' => $m->name,
            'type' => $m->type,
            'enabled' => (bool) $m->enabled,
            'sortOrder' => (int) $m->sort_order,
            'maintenanceMode' => (bool) $m->maintenance_mode,
            'config' => $m->config_array,
            'icon' => $m->icon,
            'typeLabel' => $m->type_label,
            'updateUrl' => route('admin.payment-methods.update', $m->id),
            'toggleUrl' => route('admin.payment-methods.toggle', $m->id),
            'deleteUrl' => route('admin.payment-methods.destroy', $m->id),
        ];
    })->values();
@endphp

@section('content')
<div
    class="admin-page"
    x-data="paymentMethodsAdmin(@js($methodsPayload))"
    @keydown.escape.window="closeDialog(); pickerOpen = false; deleteTarget = null"
>
    <x-admin.page-header
        title="Payment Methods"
        subtitle="Payment options shown at checkout. Online gateways can be marked as coming soon."
    >
        <x-slot:actions>
            <template x-if="selected.length > 0">
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('admin.payment-methods.bulk-status') }}" class="inline">
                        @csrf
                        <template x-for="id in selected" :key="'en-'+id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <input type="hidden" name="enabled" value="1">
                        <x-admin.button type="submit" variant="outline" size="sm">
                            Enable (<span x-text="selected.length"></span>)
                        </x-admin.button>
                    </form>
                    <form method="POST" action="{{ route('admin.payment-methods.bulk-status') }}" class="inline">
                        @csrf
                        <template x-for="id in selected" :key="'dis-'+id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <input type="hidden" name="enabled" value="0">
                        <x-admin.button type="submit" variant="outline" size="sm">
                            Disable (<span x-text="selected.length"></span>)
                        </x-admin.button>
                    </form>
                </div>
            </template>
            <x-admin.button type="button" size="sm" @click="openCreate()">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Method
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-table-wrap">
        <template x-if="methods.length === 0">
            <div class="p-6">
                <x-admin.empty-state title="No payment methods yet" description="Add COD, bKash, Nagad, bank transfer, or an online gateway." />
            </div>
        </template>

        <template x-if="methods.length > 0">
            <div>
                {{-- Mobile cards --}}
                <div class="md:hidden">
                    <template x-for="m in methods" :key="'m-'+m.id">
                        <div class="admin-mobile-card space-y-3">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" class="mt-1" :checked="selected.includes(m.id)" @change="toggleRow(m.id)">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm" x-text="m.icon"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900" x-text="m.name"></p>
                                    <p class="font-mono text-[11px] text-slate-400" x-text="m.code"></p>
                                    <p class="mt-1 text-xs text-slate-500" x-text="m.typeLabel"></p>
                                </div>
                                <template x-if="m.maintenanceMode">
                                    <x-admin.badge variant="warning">Maintenance</x-admin.badge>
                                </template>
                                <template x-if="!m.maintenanceMode">
                                    <x-admin.badge variant="secondary">Live</x-admin.badge>
                                </template>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <form method="POST" :action="m.toggleUrl">
                                    @csrf
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600">
                                        <input type="checkbox" class="rounded border-slate-300 text-brand-green-600" :checked="m.enabled" onchange="this.form.submit()">
                                        Enabled
                                    </label>
                                </form>
                                <div class="flex gap-1">
                                    <x-admin.button type="button" variant="ghost" size="sm" @click="openEdit(m)">Edit</x-admin.button>
                                    <x-admin.button type="button" variant="ghost" size="sm" class="text-red-600" @click="deleteTarget = m">Delete</x-admin.button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="w-10">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll()">
                                </th>
                                <th>Method</th>
                                <th>Type</th>
                                <th>Sort</th>
                                <th>Enabled</th>
                                <th>Maintenance</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="m in methods" :key="'t-'+m.id">
                                <tr>
                                    <td>
                                        <input type="checkbox" :checked="selected.includes(m.id)" @change="toggleRow(m.id)">
                                    </td>
                                    <td>
                                        <span class="flex items-center gap-2 font-semibold text-slate-900">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm" x-text="m.icon"></span>
                                            <span x-text="m.name"></span>
                                            <span class="font-mono text-[11px] font-normal text-slate-400" x-text="'(' + m.code + ')'"></span>
                                        </span>
                                    </td>
                                    <td class="text-slate-500" x-text="m.typeLabel"></td>
                                    <td class="text-slate-500" x-text="m.sortOrder"></td>
                                    <td>
                                        <form method="POST" :action="m.toggleUrl">
                                            @csrf
                                            <label class="relative inline-flex cursor-pointer items-center">
                                                <input type="checkbox" class="peer sr-only" :checked="m.enabled" onchange="this.form.submit()">
                                                <span class="h-5 w-9 rounded-full bg-slate-200 transition peer-checked:bg-brand-green-500 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-4"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        <template x-if="m.maintenanceMode">
                                            <x-admin.badge variant="warning">Maintenance</x-admin.badge>
                                        </template>
                                        <template x-if="!m.maintenanceMode">
                                            <x-admin.badge variant="secondary">Live</x-admin.badge>
                                        </template>
                                    </td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <x-admin.button type="button" variant="ghost" size="sm" @click="openEdit(m)">Edit</x-admin.button>
                                            <x-admin.button type="button" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50" @click="deleteTarget = m">Delete</x-admin.button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>

    {{-- Create / Edit dialog --}}
    <div x-show="dialogOpen" x-cloak class="fixed inset-0 z-[250] flex items-end justify-center sm:items-center">
        <div class="absolute inset-0 bg-black/45" @click="closeDialog()" x-transition.opacity></div>
        <div class="relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-[10px] border border-[var(--admin-border)] bg-white shadow-panel sm:rounded-[10px]" @click.stop>
            <div class="flex h-12 shrink-0 items-center justify-between border-b border-[var(--admin-border)] px-4">
                <h2 class="text-sm font-bold text-slate-900" x-text="editing ? ('Edit ' + editing.name) : 'Add Payment Method'"></h2>
                <button type="button" class="rounded-[6px] p-1.5 text-slate-400 hover:bg-slate-100" @click="closeDialog()" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" :action="editing ? editing.updateUrl : storeUrl" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <template x-if="editing">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Code</label>
                            <input
                                type="text"
                                name="code"
                                x-model="form.code"
                                :disabled="!!editing"
                                :required="!editing"
                                placeholder="bkash, nagad, cod…"
                                class="admin-control w-full disabled:bg-slate-50 disabled:text-slate-400"
                            >
                            <template x-if="editing">
                                <input type="hidden" name="code" :value="form.code">
                            </template>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Name</label>
                            <input type="text" name="name" x-model="form.name" required placeholder="bKash" class="admin-control w-full">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Type</label>
                            <select name="type" x-model="form.type" class="admin-control w-full">
                                <option value="cod">Cash on Delivery</option>
                                <option value="mobile_banking">Mobile Banking</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="online">Online Gateway</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Sort Order</label>
                            <input type="number" name="sort_order" x-model.number="form.sortOrder" class="admin-control w-full">
                        </div>

                        <div class="flex items-center justify-between rounded-[8px] border border-[var(--admin-border)] p-3">
                            <div>
                                <p class="text-sm font-medium text-slate-900">Enabled</p>
                                <p class="text-[11px] text-slate-400">Show at checkout</p>
                            </div>
                            <input type="hidden" name="enabled" :value="form.enabled ? 1 : 0">
                            <button type="button" role="switch" :aria-checked="form.enabled" @click="form.enabled = !form.enabled"
                                class="relative h-5 w-9 rounded-full transition"
                                :class="form.enabled ? 'bg-brand-green-500' : 'bg-slate-200'">
                                <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition" :class="form.enabled && 'translate-x-4'"></span>
                            </button>
                        </div>

                        <div class="flex items-center justify-between rounded-[8px] border border-[var(--admin-border)] p-3">
                            <div>
                                <p class="text-sm font-medium text-slate-900">Maintenance Mode</p>
                                <p class="text-[11px] text-slate-400">Temporarily unavailable (“coming soon”)</p>
                            </div>
                            <input type="hidden" name="maintenance_mode" :value="form.maintenanceMode ? 1 : 0">
                            <button type="button" role="switch" :aria-checked="form.maintenanceMode" @click="form.maintenanceMode = !form.maintenanceMode"
                                class="relative h-5 w-9 rounded-full transition"
                                :class="form.maintenanceMode ? 'bg-amber-500' : 'bg-slate-200'">
                                <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition" :class="form.maintenanceMode && 'translate-x-4'"></span>
                            </button>
                        </div>

                        {{-- Mobile banking config --}}
                        <template x-if="form.type === 'mobile_banking'">
                            <div class="contents">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Merchant Number</label>
                                    <input type="text" name="config[merchantNumber]" x-model="form.config.merchantNumber" placeholder="01711111111" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Merchant Name</label>
                                    <input type="text" name="config[merchantName]" x-model="form.config.merchantName" placeholder="Mama Bazar" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5 sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700">QR Code Image</label>
                                    <input type="hidden" name="config[qrCode]" :value="form.config.qrCode || ''">
                                    <template x-if="form.config.qrCode">
                                        <div class="flex items-center gap-3">
                                            <img :src="form.config.qrCode" alt="" class="h-16 w-16 rounded-[6px] border border-[var(--admin-border)] object-cover">
                                            <x-admin.button type="button" variant="outline" size="sm" @click="form.config.qrCode = ''">Remove</x-admin.button>
                                        </div>
                                    </template>
                                    <template x-if="!form.config.qrCode">
                                        <x-admin.button type="button" variant="outline" size="sm" @click="openPicker()">Pick QR image</x-admin.button>
                                    </template>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Min Amount (Tk)</label>
                                    <input type="number" name="config[minAmount]" x-model="form.config.minAmount" placeholder="50" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Max Amount (Tk)</label>
                                    <input type="number" name="config[maxAmount]" x-model="form.config.maxAmount" placeholder="200000" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Extra Fee (Tk)</label>
                                    <input type="number" name="config[extraFee]" x-model="form.config.extraFee" placeholder="0" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Extra Fee (%)</label>
                                    <input type="number" name="config[extraFeePercent]" x-model="form.config.extraFeePercent" placeholder="e.g. 2" class="admin-control w-full">
                                </div>
                            </div>
                        </template>

                        {{-- Bank config --}}
                        <template x-if="form.type === 'bank'">
                            <div class="contents">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Bank Name</label>
                                    <input type="text" name="config[bankName]" x-model="form.config.bankName" placeholder="DBBL" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Account Name</label>
                                    <input type="text" name="config[accountName]" x-model="form.config.accountName" placeholder="Mama Bazar" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Account Number</label>
                                    <input type="text" name="config[accountNumber]" x-model="form.config.accountNumber" placeholder="1234567890" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700">Routing Number</label>
                                    <input type="text" name="config[routingNumber]" x-model="form.config.routingNumber" placeholder="123456789" class="admin-control w-full">
                                </div>
                                <div class="space-y-1.5 sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700">Branch</label>
                                    <input type="text" name="config[branch]" x-model="form.config.branch" placeholder="Gulshan" class="admin-control w-full">
                                </div>
                            </div>
                        </template>

                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700">Instructions (shown to customer)</label>
                            <textarea name="config[instructions]" rows="3" x-model="form.config.instructions" placeholder="How the customer should pay…" class="admin-control w-full"></textarea>
                        </div>
                    </div>
                </div>

                <div class="sticky bottom-0 flex shrink-0 items-center justify-end gap-2 border-t border-[var(--admin-border)] bg-white px-4 py-3">
                    <x-admin.button type="button" variant="outline" size="sm" @click="closeDialog()">Cancel</x-admin.button>
                    <x-admin.button type="submit" size="sm" x-text="editing ? 'Save Changes' : 'Create'"></x-admin.button>
                </div>
            </form>
        </div>
    </div>

    {{-- Media picker (local storage) --}}
    <div x-show="pickerOpen" x-cloak class="fixed inset-0 z-[320] flex items-end justify-center sm:items-center">
        <div class="absolute inset-0 bg-black/45" @click="pickerOpen = false"></div>
        <div class="relative z-10 flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-[10px] border border-[var(--admin-border)] bg-white shadow-panel sm:rounded-[10px]" @click.stop>
            <div class="flex h-12 shrink-0 items-center justify-between border-b border-[var(--admin-border)] px-4">
                <h2 class="text-sm font-bold text-slate-900">Select QR image</h2>
                <button type="button" class="rounded-[6px] p-1.5 text-slate-400 hover:bg-slate-100" @click="pickerOpen = false" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-b border-[var(--admin-border)] px-4 py-3">
                <input type="search" x-model.debounce.300ms="pickerSearch" placeholder="Search files…" class="admin-control h-9 min-w-[140px] flex-1 px-2.5 text-xs">
                <select x-model="pickerFolder" class="admin-control h-9 w-auto px-2 text-xs">
                    <option value="all">All folders</option>
                    <template x-for="f in pickerFolders" :key="f">
                        <option :value="f" x-text="f"></option>
                    </template>
                </select>
                <label class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-[6px] bg-brand-green-500 px-3 text-xs font-semibold text-white hover:bg-brand-green-600">
                    <span x-text="pickerUploading ? 'Uploading…' : 'Upload'"></span>
                    <input type="file" accept="image/*" class="sr-only" @change="uploadPickerFile($event)" :disabled="pickerUploading">
                </label>
            </div>
            <div class="min-h-[240px] flex-1 overflow-y-auto p-4">
                <div x-show="pickerLoading" class="py-16 text-center text-sm text-slate-500">Loading media…</div>
                <div x-show="!pickerLoading && pickerAssets.length === 0" class="py-16 text-center text-sm text-slate-500">No media yet. Upload an image (stored locally).</div>
                <div x-show="!pickerLoading && pickerAssets.length > 0" class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                    <template x-for="asset in pickerAssets" :key="asset.id">
                        <button type="button" @click="pickerSelected = asset"
                            class="group relative aspect-square overflow-hidden rounded-[8px] border-2 transition"
                            :class="pickerSelected?.id === asset.id ? 'border-brand-green-500 ring-2 ring-brand-green-200' : 'border-[var(--admin-border)] hover:border-brand-green-300'">
                            <img :src="asset.url" :alt="asset.filename || ''" class="h-full w-full object-cover" loading="lazy">
                        </button>
                    </template>
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-between gap-2 border-t border-[var(--admin-border)] px-4 py-3">
                <p class="text-[11px] text-slate-500">Local storage · no Cloudinary</p>
                <div class="flex gap-2">
                    <x-admin.button type="button" variant="outline" size="sm" @click="pickerOpen = false">Cancel</x-admin.button>
                    <x-admin.button type="button" size="sm" :disabled="!pickerSelected" @click="confirmPicker()">Use selected</x-admin.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div x-show="!!deleteTarget" x-cloak class="fixed inset-0 z-[260] flex items-end justify-center sm:items-center">
        <div class="absolute inset-0 bg-black/45" @click="deleteTarget = null"></div>
        <div class="relative z-10 w-full max-w-md rounded-t-[10px] border border-[var(--admin-border)] bg-white p-5 shadow-panel sm:rounded-[10px]" @click.stop>
            <h3 class="text-sm font-bold text-slate-900">Delete payment method?</h3>
            <p class="mt-1 text-xs text-slate-500">This action cannot be undone. Method: <span class="font-semibold text-slate-700" x-text="deleteTarget?.name"></span></p>
            <div class="mt-4 flex justify-end gap-2">
                <x-admin.button type="button" variant="outline" size="sm" @click="deleteTarget = null">Cancel</x-admin.button>
                <form method="POST" :action="deleteTarget?.deleteUrl">
                    @csrf
                    @method('DELETE')
                    <x-admin.button type="submit" variant="destructive" size="sm">Delete</x-admin.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function paymentMethodsAdmin(initialMethods) {
    const mediaListUrl = @json(route('admin.media.picker'));
    const mediaUploadUrl = @json(route('admin.media.picker.upload'));
    const csrf = @json(csrf_token());

    return {
        methods: initialMethods,
        selected: [],
        dialogOpen: false,
        editing: null,
        deleteTarget: null,
        storeUrl: @json(route('admin.payment-methods.store')),
        form: {
            code: '',
            name: '',
            type: 'cod',
            enabled: true,
            maintenanceMode: false,
            sortOrder: 0,
            config: {},
        },
        pickerOpen: false,
        pickerSearch: '',
        pickerFolder: 'all',
        pickerFolders: ['general', 'products', 'banners', 'payments'],
        pickerAssets: [],
        pickerLoading: false,
        pickerSelected: null,
        pickerUploading: false,

        get allSelected() {
            return this.methods.length > 0 && this.selected.length === this.methods.length;
        },

        init() {
            this.$watch('pickerFolder', () => { if (this.pickerOpen) this.loadPickerAssets(); });
            this.$watch('pickerSearch', () => { if (this.pickerOpen) this.loadPickerAssets(); });
        },

        emptyForm() {
            return {
                code: '',
                name: '',
                type: 'cod',
                enabled: true,
                maintenanceMode: false,
                sortOrder: this.methods.length ? Math.max(...this.methods.map(m => m.sortOrder)) + 1 : 1,
                config: {},
            };
        },

        openCreate() {
            this.form = this.emptyForm();
            this.editing = null;
            this.dialogOpen = true;
        },

        openEdit(m) {
            this.editing = m;
            this.form = {
                code: m.code,
                name: m.name,
                type: m.type,
                enabled: !!m.enabled,
                maintenanceMode: !!m.maintenanceMode,
                sortOrder: m.sortOrder ?? 0,
                config: { ...(m.config || {}) },
            };
            this.dialogOpen = true;
        },

        closeDialog() {
            this.dialogOpen = false;
            this.editing = null;
        },

        toggleRow(id) {
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter((x) => x !== id);
            } else {
                this.selected = [...this.selected, id];
            }
        },

        toggleAll() {
            if (this.allSelected) this.selected = [];
            else this.selected = this.methods.map((m) => m.id);
        },

        openPicker() {
            this.pickerOpen = true;
            this.pickerSelected = null;
            this.loadPickerAssets();
        },

        async loadPickerAssets() {
            this.pickerLoading = true;
            try {
                const params = new URLSearchParams({ limit: '40' });
                if (this.pickerFolder && this.pickerFolder !== 'all') params.set('folder', this.pickerFolder);
                if (this.pickerSearch) params.set('search', this.pickerSearch);
                const res = await fetch(`${mediaListUrl}?${params}`, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.pickerAssets = json.data || [];
                if (Array.isArray(json.folders) && json.folders.length) {
                    this.pickerFolders = json.folders;
                }
            } catch (e) {
                this.pickerAssets = [];
            } finally {
                this.pickerLoading = false;
            }
        },

        async uploadPickerFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.pickerUploading = true;
            try {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('folder', 'payments');
                const res = await fetch(mediaUploadUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: fd,
                });
                const json = await res.json();
                if (json.data?.url) {
                    this.form.config.qrCode = json.data.url;
                    this.pickerOpen = false;
                } else {
                    await this.loadPickerAssets();
                }
            } finally {
                this.pickerUploading = false;
                event.target.value = '';
            }
        },

        confirmPicker() {
            if (this.pickerSelected?.url) {
                this.form.config.qrCode = this.pickerSelected.url;
            }
            this.pickerOpen = false;
        },
    };
}
</script>
@endpush
