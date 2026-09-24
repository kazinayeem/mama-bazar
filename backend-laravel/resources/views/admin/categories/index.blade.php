@extends('layouts.admin', ['headerTitle' => 'Category Management'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Categories" :subtitle="'Organize your catalog · '.$categories->count().' categories'" />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="admin-surface h-fit space-y-3 p-4">
            <h3 class="border-b border-[var(--admin-border)] pb-2.5 text-sm font-bold text-slate-900">Add Category</h3>
            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <x-admin.input label="Category Name *" name="name" required placeholder="e.g. Fresh Vegetables" />
                <x-admin.select label="Parent Category (Optional)" name="parent_id">
                    <option value="">None (Top-Level Category)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </x-admin.select>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">Category Image</label>
                    <input type="file" name="image" accept="image/*"
                        class="admin-control w-full file:mr-2 file:rounded-[6px] file:border-0 file:bg-brand-green-50 file:px-2.5 file:py-1 file:text-[11px] file:font-semibold file:text-brand-green-700">
                </div>
                <x-admin.input label="Custom Slug (Optional)" name="slug" placeholder="fresh-vegetables" />
                <x-admin.button type="submit" class="w-full" size="sm">Create Category</x-admin.button>
            </form>
        </div>

        <div class="admin-table-wrap lg:col-span-2">
            <div class="border-b border-[var(--admin-border)] px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">
                All Categories ({{ $categories->count() }})
            </div>
            @if($categories->isEmpty())
                <x-admin.empty-state title="No categories yet" description="Create your first category using the form." />
            @else
                <div class="md:hidden">
                    @foreach($categories as $cat)
                        <div class="admin-mobile-card">
                            <div class="flex items-start gap-3">
                                @if($cat->image)
                                    <img src="{{ $cat->image }}" class="h-10 w-10 rounded-[6px] border border-[var(--admin-border)] object-contain bg-slate-50" alt="">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-[6px] bg-brand-green-50 text-xs font-bold text-brand-green-700">{{ substr($cat->name, 0, 1) }}</div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $cat->name }}</p>
                                    <p class="font-mono text-[11px] text-slate-400">{{ $cat->slug }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $cat->parent ? $cat->parent->name : 'Top-level' }}</p>
                                </div>
                            </div>
                            <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" class="mt-3" onsubmit="return confirm('Delete this category?');">
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
                                <th>Image</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Parent</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $cat)
                                <tr>
                                    <td>
                                        @if($cat->image)
                                            <img src="{{ $cat->image }}" class="h-9 w-9 rounded-[6px] border border-[var(--admin-border)] object-contain bg-slate-50" alt="">
                                        @else
                                            <div class="flex h-9 w-9 items-center justify-center rounded-[6px] bg-brand-green-50 text-xs font-bold text-brand-green-700">{{ substr($cat->name, 0, 1) }}</div>
                                        @endif
                                    </td>
                                    <td class="font-semibold text-slate-900">{{ $cat->name }}</td>
                                    <td><code class="rounded bg-slate-50 px-1.5 py-0.5 font-mono text-[11px] text-slate-600">{{ $cat->slug }}</code></td>
                                    <td class="text-slate-600">{{ $cat->parent ? $cat->parent->name : '—' }}</td>
                                    <td class="text-right">
                                        <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this category?');">
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
