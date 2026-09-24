@extends('layouts.admin', ['headerTitle' => 'Media Library'])

@section('content')
<div class="space-y-6">

    <!-- Upload Card -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-soft">
        <h3 class="text-sm font-bold text-slate-900 mb-3">Upload File to Local Storage</h3>
        <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            @csrf

            <input type="file" name="file" required accept="image/*" class="w-full text-xs rounded-xl border border-slate-200 p-2 focus:outline-none file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-brand-green-50 file:text-brand-green-700 sm:w-auto sm:flex-1 sm:min-w-[200px]">

            <select name="folder" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none bg-white sm:w-auto">
                <option value="general">Folder: General</option>
                <option value="products">Folder: Products</option>
                <option value="banners">Folder: Banners</option>
                <option value="categories">Folder: Categories</option>
            </select>

            <button type="submit" class="w-full px-5 py-2.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-sm transition sm:w-auto">
                Upload Local File
            </button>
        </form>
    </div>

    <!-- Media Grid -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-soft space-y-4">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Stored Media Assets ({{ $media->total() }})</h3>

        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
            @forelse($media as $asset)
                <div class="border border-slate-200 rounded-2xl p-2 bg-slate-50 flex flex-col justify-between group space-y-2">
                    <div class="aspect-square rounded-xl bg-white overflow-hidden flex items-center justify-center border border-slate-100">
                        <img src="{{ $asset->url }}" class="w-full h-full object-cover">
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-[10px] font-bold text-slate-700 truncate" title="{{ $asset->filename }}">{{ $asset->filename }}</p>
                        <p class="text-[9px] text-slate-400 font-mono">{{ number_format($asset->size / 1024, 1) }} KB</p>
                    </div>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $asset->url }}'); alert('Copied image URL to clipboard: {{ $asset->url }}');" class="w-full py-1 text-[10px] font-bold rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700">
                        Copy URL
                    </button>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 text-xs">
                    No media assets uploaded yet.
                </div>
            @endforelse
        </div>

        @if($media->hasPages())
            <div class="pt-4 border-t border-slate-100 flex items-center justify-center">
                {{ $media->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
