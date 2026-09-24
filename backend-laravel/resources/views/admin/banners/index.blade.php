@extends('layouts.admin', ['headerTitle' => 'Banner Management'])

@section('content')
<div class="admin-page">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Banner Form -->
    <div class="admin-surface p-4 space-y-4 h-fit">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Upload Banner</h3>

        <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Banner Title *</label>
                <input type="text" name="title" required placeholder="e.g. Summer Grocery Festival" class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Position *</label>
                <select name="position" required class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none bg-white">
                    <option value="hero">Hero Main Banner</option>
                    <option value="promo">Promotional Strip</option>
                    <option value="sidebar">Sidebar Ad</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Banner Image (Stored Locally) *</label>
                <input type="file" name="image" required accept="image/*" class="w-full text-xs admin-control p-2 focus:border-brand-green-500 focus:outline-none file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-brand-green-50 file:text-brand-green-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Target Click URL (Optional)</label>
                <input type="text" name="link" placeholder="/shop?sale=true" class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none">
            </div>

            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-[6px] bg-brand-green-500 text-sm font-medium text-white hover:bg-brand-green-600">
                Save & Publish Banner
            </button>
        </form>
    </div>

    <!-- Banners Table -->
    <div class="lg:col-span-2 admin-table-wrap">
        <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">Configured Banners ({{ $banners->count() }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="p-4">Preview</th>
                        <th class="p-4">Title</th>
                        <th class="p-4">Position</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($banners as $b)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4">
                                <img src="{{ $b->image }}" class="w-20 h-10 rounded-lg object-cover border border-slate-200">
                            </td>
                            <td class="p-4 font-bold text-slate-800">{{ $b->title }}</td>
                            <td class="p-4 uppercase text-[10px] font-bold text-slate-500">{{ $b->position }}</td>
                            <td class="p-4 text-right">
                                <form action="{{ route('admin.banners.destroy', $b->id) }}" method="POST" onsubmit="return confirm('Delete this banner?');">
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
                            <td colspan="4" class="p-8 text-center text-slate-400">No banners created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
@endsection
