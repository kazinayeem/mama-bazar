@extends('layouts.admin', ['headerTitle' => 'Category Management'])

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Category Form -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-soft space-y-4 h-fit">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Add New Category</h3>

        <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Category Name *</label>
                <input type="text" name="name" required placeholder="e.g. Fresh Vegetables"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Parent Category (Optional)</label>
                <select name="parent_id" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none bg-white">
                    <option value="">None (Top-Level Category)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Category Image (Stored Locally)</label>
                <input type="file" name="image" accept="image/*"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2 focus:border-brand-green-500 focus:outline-none file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-brand-green-50 file:text-brand-green-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Custom Slug (Optional)</label>
                <input type="text" name="slug" placeholder="fresh-vegetables"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-sm transition">
                Create Category
            </button>
        </form>
    </div>

    <!-- Category Table -->
    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="p-4 border-b border-slate-100 font-bold text-xs uppercase tracking-wider text-slate-700">All Categories ({{ $categories->count() }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="p-4">Image</th>
                        <th class="p-4">Name</th>
                        <th class="p-4">Slug</th>
                        <th class="p-4">Parent</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $cat)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4">
                                @if($cat->image)
                                    <img src="{{ $cat->image }}" class="w-9 h-9 rounded-lg object-contain bg-slate-50 border border-slate-100">
                                @else
                                    <div class="w-9 h-9 rounded-lg bg-brand-green-50 text-brand-green-700 font-bold text-xs flex items-center justify-center">
                                        {{ substr($cat->name, 0, 1) }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 font-bold text-slate-800">{{ $cat->name }}</td>
                            <td class="p-4 text-slate-500 font-mono text-[11px]">{{ $cat->slug }}</td>
                            <td class="p-4 text-slate-600">{{ $cat->parent ? $cat->parent->name : '—' }}</td>
                            <td class="p-4 text-right">
                                <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this category?');">
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
                            <td colspan="5" class="p-8 text-center text-slate-400">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
