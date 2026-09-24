{{-- Alpine media picker modal. Parent must expose pickerOpen, pickerTarget, onMediaPicked(url). --}}
@props([
    'listUrl' => null,
    'uploadUrl' => null,
])

@php
    $listUrl = $listUrl ?: route('admin.media.picker');
    $uploadUrl = $uploadUrl ?: route('admin.media.picker.upload');
@endphp

<div
    x-show="pickerOpen"
    x-cloak
    class="fixed inset-0 z-[320] flex items-end justify-center sm:items-center"
    @keydown.escape.window="pickerOpen = false"
>
    <div class="absolute inset-0 bg-black/45" @click="pickerOpen = false"></div>
    <div class="relative z-10 flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-[10px] border border-[var(--admin-border)] bg-white shadow-panel sm:rounded-[10px]" @click.stop>
        <div class="flex h-12 shrink-0 items-center justify-between border-b border-[var(--admin-border)] px-4">
            <h2 class="text-sm font-bold text-slate-900">Select image</h2>
            <button type="button" class="rounded-[6px] p-1.5 text-slate-400 hover:bg-slate-100" @click="pickerOpen = false" aria-label="Close">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-b border-[var(--admin-border)] px-4 py-3">
            <input type="search" x-model.debounce.300ms="pickerSearch" placeholder="Search files…" class="admin-control h-9 flex-1 min-w-[140px] px-2.5 text-xs">
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
            <div x-show="!pickerLoading && pickerAssets.length === 0" class="py-16 text-center">
                <x-admin.empty-state title="No media yet" description="Upload an image to get started. Files are stored locally." />
            </div>
            <div x-show="!pickerLoading && pickerAssets.length > 0" class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                <template x-for="asset in pickerAssets" :key="asset.id">
                    <button
                        type="button"
                        @click="selectPickerAsset(asset)"
                        class="group relative aspect-square overflow-hidden rounded-[8px] border-2 transition"
                        :class="pickerSelected?.id === asset.id ? 'border-brand-green-500 ring-2 ring-brand-green-200' : 'border-[var(--admin-border)] hover:border-brand-green-300'"
                    >
                        <img :src="asset.url" :alt="asset.filename || ''" class="h-full w-full object-cover" loading="lazy">
                        <span class="absolute inset-x-0 bottom-0 truncate bg-black/55 px-1 py-0.5 text-[9px] text-white opacity-0 transition group-hover:opacity-100" x-text="asset.filename"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-between gap-2 border-t border-[var(--admin-border)] px-4 py-3">
            <p class="text-[11px] text-slate-500" x-text="pickerAssets.length + ' files · local storage'"></p>
            <div class="flex gap-2">
                <button type="button" @click="pickerOpen = false" class="rounded-[6px] border border-[var(--admin-border)] px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="confirmPicker()" :disabled="!pickerSelected" class="rounded-[6px] bg-brand-green-500 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-green-600 disabled:opacity-40">Use selected</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
window.__homepageMediaPicker = {
    listUrl: @json($listUrl),
    uploadUrl: @json($uploadUrl),
    csrf: @json(csrf_token()),
};
</script>
@endpush
@endonce
