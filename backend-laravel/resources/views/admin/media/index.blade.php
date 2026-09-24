@extends('layouts.admin', ['headerTitle' => 'Media Library'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Media Library" :subtitle="'Local storage assets · '.$media->total().' files'" />

    <div class="admin-surface p-4">
        <h3 class="mb-3 text-sm font-bold text-slate-900">Upload File</h3>
        <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            @csrf
            <input type="file" name="file" required accept="image/*"
                class="admin-control w-full file:mr-2 file:rounded-[6px] file:border-0 file:bg-brand-green-50 file:px-2.5 file:py-1 file:text-[11px] file:font-semibold file:text-brand-green-700 sm:min-w-[200px] sm:flex-1">
            <select name="folder" class="admin-control w-full sm:w-auto">
                <option value="general">Folder: General</option>
                <option value="products">Folder: Products</option>
                <option value="banners">Folder: Banners</option>
                <option value="categories">Folder: Categories</option>
            </select>
            <x-admin.button type="submit" size="sm" class="w-full sm:w-auto">Upload</x-admin.button>
        </form>
    </div>

    <div class="admin-surface p-4">
        <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-600">Stored Assets ({{ $media->total() }})</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6">
            @forelse($media as $asset)
                <div class="flex flex-col space-y-2 rounded-[8px] border border-[var(--admin-border)] bg-[var(--admin-muted)] p-2">
                    <div class="flex aspect-square items-center justify-center overflow-hidden rounded-[6px] border border-[var(--admin-border)] bg-white">
                        <img src="{{ $asset->url }}" class="h-full w-full object-cover" alt="" loading="lazy">
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <p class="truncate text-[10px] font-bold text-slate-700" title="{{ $asset->filename }}">{{ $asset->filename }}</p>
                        <p class="font-mono text-[9px] text-slate-400">{{ number_format($asset->size / 1024, 1) }} KB</p>
                    </div>
                    <button type="button"
                            onclick="navigator.clipboard.writeText(@js($asset->url)); window.dispatchEvent(new CustomEvent('admin-toast', { detail: { message: 'URL copied' } }));"
                            class="w-full rounded-[6px] border border-[var(--admin-border)] bg-white py-1.5 text-[10px] font-bold text-slate-700 hover:bg-slate-50">
                        Copy URL
                    </button>
                </div>
            @empty
                <div class="col-span-full">
                    <x-admin.empty-state title="No media yet" description="Upload your first image above." />
                </div>
            @endforelse
        </div>
        <x-admin.pagination :paginator="$media" class="mt-4 border-t-0 px-0" />
    </div>
</div>
@endsection
