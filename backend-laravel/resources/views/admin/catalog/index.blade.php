@extends('layouts.admin', ['headerTitle' => $headerTitle ?? $config['title']])

@section('content')
@php
    $singular = \Illuminate\Support\Str::singular($config['title']);
    $columnLabels = [
        'logo' => 'Logo',
        'name' => $resource === 'brands' ? 'Brand' : 'Name',
        'slug' => 'Slug',
        'products_count' => 'Products',
        'featured' => 'Featured',
        'status' => 'Status',
        'created_at' => 'Created',
        'hex' => 'Color',
        'text' => 'Notice',
        'priority' => 'Priority',
        'sort_order' => 'Order',
        'phone' => 'Phone',
        'email' => 'Email',
        'type' => 'Type',
    ];
@endphp

<div
    class="admin-page"
    x-data="{
        createOpen: false,
        editOpen: false,
        filtersOpen: {{ request('status') || request('q') ? 'true' : 'false' }},
        editItem: null,
        editUrl: '',
        editForm: {},
        createForm: {},
        failedEditId: {{ old('edit_id') ? (int) old('edit_id') : 'null' }},
        itemsMap: @js($items->keyBy('id')),
        openCreate() {
            this.createForm = {
                @foreach($config['fields'] as $field)
                    @if(($field['type'] ?? '') === 'checkbox')
                        '{{ $field['name'] }}': false,
                    @elseif(($field['type'] ?? '') === 'select')
                        '{{ $field['name'] }}': '{{ array_key_first($field['options'] ?? ['active' => 'Active']) }}',
                    @elseif(($field['type'] ?? '') === 'hex')
                        '{{ $field['name'] }}': '#176B3A',
                    @else
                        '{{ $field['name'] }}': '',
                    @endif
                @endforeach
            };
            this.createOpen = true;
        },
        openEdit(item) {
            this.editItem = item;
            this.editUrl = '{{ route($config['route'].'.update', ':id') }}'.replace(':id', item.id);
            this.editForm = {};
            @foreach($config['fields'] as $field)
                @if(($field['type'] ?? '') === 'checkbox')
                    this.editForm['{{ $field['name'] }}'] = Boolean(item['{{ $field['name'] }}']);
                @else
                    this.editForm['{{ $field['name'] }}'] = item['{{ $field['name'] }}'] ?? '';
                @endif
            @endforeach
            this.editOpen = true;
        }
    }"
    x-init="if (failedEditId && itemsMap[failedEditId]) openEdit(itemsMap[failedEditId])"
>
    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <x-admin.page-header :title="$config['title']" :subtitle="$config['description'].' · '.$items->total().' items'">
        <x-slot:actions>
            <x-admin.button type="button" size="sm" @click="openCreate()">
                <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add {{ $singular }}
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Create Modal --}}
    <x-admin.modal name="createOpen" :title="'Create ' . $singular" maxWidth="2xl">
        <form action="{{ route($config['route'].'.store') }}" method="POST" class="grid gap-3.5 sm:grid-cols-2">
            @csrf
            @foreach($config['fields'] as $field)
                <div class="{{ !empty($field['full']) ? 'sm:col-span-2' : '' }}">
                    @include('admin.catalog._field', ['field' => $field, 'modelPrefix' => 'createForm', 'value' => old($field['name'])])
                </div>
            @endforeach
            <div class="flex items-center justify-end gap-2 pt-3 sm:col-span-2 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="createOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Create {{ $singular }}</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Edit Modal --}}
    <x-admin.modal name="editOpen" x-title="'Edit ' + (editItem ? (editItem.name || editItem.title || editItem.text || ('# ' + editItem.id)) : 'Item')" maxWidth="2xl">
        <form :action="editUrl" method="POST" class="grid gap-3.5 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_id" :value="editItem ? editItem.id : ''">
            @foreach($config['fields'] as $field)
                <div class="{{ !empty($field['full']) ? 'sm:col-span-2' : '' }}">
                    @include('admin.catalog._field', ['field' => $field, 'modelPrefix' => 'editForm'])
                </div>
            @endforeach
            <div class="flex items-center justify-end gap-2 pt-3 sm:col-span-2 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="editOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <form method="GET" class="admin-filter-bar">
        <x-admin.search-input name="q" placeholder="Search..." class="sm:max-w-md" />
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="flex w-full items-center justify-between rounded-lg border border-[var(--admin-border)] bg-[var(--admin-muted)] px-3 py-2 text-xs font-semibold text-slate-700 md:hidden">
            <span>Status{{ request('status') ? ': '.ucfirst(request('status')) : '' }}</span>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
             :class="filtersOpen ? 'flex' : 'hidden md:flex'">
            <select name="status" class="admin-control w-full sm:w-40 text-xs">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            </select>
            <x-admin.button type="submit" variant="outline" size="sm" class="w-full sm:w-auto">Apply</x-admin.button>
        </div>
    </form>

    <div class="admin-table-wrap">
        @if($items->isEmpty())
            <x-admin.empty-state :title="$config['empty']" description="Try adjusting search or status filters." />
        @else
            {{-- Mobile cards --}}
            <div class="md:hidden">
                @foreach($items as $item)
                    @php
                        $cardTitle = $item->name ?? $item->title ?? (\Illuminate\Support\Str::limit($item->text ?? '', 48) ?: null) ?? ('Item #'.$item->id);
                        $statusActive = ($item->status ?? '') === 'active';
                    @endphp
                    <div class="admin-mobile-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                @if(in_array('logo', $config['columns'], true))
                                    @if($item->logo)
                                        <img src="{{ $item->logo }}" alt="" class="h-10 w-10 rounded-lg border border-[var(--admin-border)] object-contain bg-slate-50 p-0.5" loading="lazy">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-[var(--admin-border)] bg-[var(--admin-muted)] text-[10px] font-bold text-slate-400">—</div>
                                    @endif
                                @endif
                                @if(in_array('hex', $config['columns'], true) && $item->hex)
                                    <span class="admin-swatch mt-1 shrink-0 shadow-xs" style="background: {{ $item->hex }}"></span>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $cardTitle }}</p>
                                    <dl class="mt-2 space-y-1">
                                        @foreach($config['columns'] as $col)
                                            @if(in_array($col, ['name', 'logo', 'status', 'hex'], true)) @continue @endif
                                            <div class="flex gap-2 text-xs">
                                                <dt class="shrink-0 font-semibold uppercase tracking-wide text-slate-400">{{ $columnLabels[$col] ?? str_replace('_', ' ', $col) }}</dt>
                                                <dd class="min-w-0 text-slate-700">
                                                    @include('admin.catalog._cell', ['col' => $col, 'item' => $item])
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            </div>
                            @if(in_array('status', $config['columns'], true))
                                <x-admin.badge :variant="$statusActive ? 'default' : 'muted'" class="shrink-0">{{ $item->status ?? '—' }}</x-admin.badge>
                            @endif
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="button" @click="openEdit(@js($item))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs border border-[var(--admin-border)] bg-white text-slate-700 hover:bg-[var(--admin-muted)] flex-1">Edit</button>
                            <form action="{{ route($config['route'].'.destroy', $item->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Delete this item?')">
                                @csrf
                                @method('DELETE')
                                <x-admin.button type="submit" variant="destructive" size="sm" class="w-full">Delete</x-admin.button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            @foreach($config['columns'] as $col)
                                <th>{{ $columnLabels[$col] ?? str_replace('_', ' ', $col) }}</th>
                            @endforeach
                            <th class="w-[120px] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                @foreach($config['columns'] as $col)
                                    <td>
                                        @include('admin.catalog._cell', ['col' => $col, 'item' => $item])
                                    </td>
                                @endforeach
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-0.5">
                                        <button type="button" @click="openEdit(@js($item))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs text-slate-600 hover:bg-slate-100">Edit</button>
                                        <form action="{{ route($config['route'].'.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this item?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50 hover:text-red-700">Delete</x-admin.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-admin.pagination :paginator="$items" />
        @endif
    </div>
</div>
@endsection
