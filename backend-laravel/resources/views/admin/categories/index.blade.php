@extends('layouts.admin', ['headerTitle' => 'Category Management'])

@section('content')
<div
    class="admin-page"
    x-data="{
        editOpen: false,
        editCat: null,
        editUrl: '',
        failedEditId: {{ old('edit_id') ? (int) old('edit_id') : 'null' }},
        itemsMap: @js($categories->keyBy('id')),
        openEdit(cat) {
            this.editCat = cat;
            this.editUrl = '{{ route('admin.categories.update', ':id') }}'.replace(':id', cat.id);
            this.editOpen = true;
        }
    }"
    x-init="if (failedEditId && itemsMap[failedEditId]) openEdit(itemsMap[failedEditId])"
>
    <x-admin.page-header title="Categories" :subtitle="'Organize your catalog · '.$categories->total().' categories'" />

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.categories.index') }}" class="admin-filter-bar">
        <x-admin.search-input name="q" placeholder="Search categories..." />
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            <select name="status" class="admin-control w-full sm:w-40">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <x-admin.button type="submit" size="sm" class="w-full sm:w-auto">Filter</x-admin.button>
        </div>
    </form>

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
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Sort Order</label>
                        <input type="number" name="sort_order" value="0" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Status</label>
                        <select name="status" class="admin-control w-full text-xs bg-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 pt-1 cursor-pointer">
                    <input type="checkbox" name="featured" value="1" class="rounded text-brand-green-600 focus:ring-brand-green-500">
                    <span>Feature this category</span>
                </label>
                <x-admin.button type="submit" class="w-full" size="sm">Create Category</x-admin.button>
            </form>
        </div>

        <div class="admin-table-wrap lg:col-span-2">
            <div class="border-b border-[var(--admin-border)] px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600">
                All Categories ({{ $categories->total() }})
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
                            <div class="mt-3 flex gap-2">
                                <button type="button" @click="openEdit(@js($cat))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs border border-[var(--admin-border)] bg-white text-slate-700 hover:bg-[var(--admin-muted)] flex-1">Edit</button>
                                <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Delete this category?');">
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
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" @click="openEdit(@js($cat))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs text-slate-600 hover:bg-slate-100">Edit</button>
                                            <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this category?');">
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
                <div class="p-3 border-t border-[var(--admin-border)]">
                    <x-admin.pagination :paginator="$categories" />
                </div>
            @endif
        </div>
    </div>

    {{-- Edit Category Modal --}}
    <x-admin.modal name="editOpen" x-title="'Edit Category: ' + (editCat ? editCat.name : '')" maxWidth="lg">
        <form :action="editUrl" method="POST" enctype="multipart/form-data" class="space-y-3.5">
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_id" :value="editCat ? editCat.id : ''">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Category Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" :value="editCat?.name" required class="admin-control w-full text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Slug</label>
                <input type="text" name="slug" :value="editCat?.slug" class="admin-control w-full text-xs font-mono">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Parent Category</label>
                <select name="parent_id" class="admin-control w-full text-xs bg-white" :value="editCat?.parent_id || ''">
                    <option value="">None (Top-Level Category)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="admin-control w-full text-xs" :value="editCat?.description || ''"></textarea>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-700">Category Image</label>
                <div class="flex items-center gap-3">
                    <template x-if="editCat?.image">
                        <img :src="editCat.image" class="h-10 w-10 shrink-0 rounded-lg border border-slate-200 object-contain bg-slate-50 p-0.5">
                    </template>
                    <input type="file" name="image" accept="image/*"
                        class="admin-control flex-1 text-xs file:mr-2 file:rounded-[6px] file:border-0 file:bg-brand-green-50 file:px-2.5 file:py-1 file:text-[11px] file:font-semibold file:text-brand-green-700">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" :value="editCat?.sort_order ?? 0" class="admin-control w-full text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                    <select name="status" class="admin-control w-full text-xs bg-white" :value="editCat?.status || 'active'">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <label class="flex items-center gap-2 text-xs font-medium text-slate-700 pt-1 cursor-pointer">
                <input type="checkbox" name="featured" value="1" :checked="Boolean(editCat?.featured)" class="rounded text-brand-green-600 focus:ring-brand-green-500">
                <span>Featured Category</span>
            </label>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="editOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
@endsection
