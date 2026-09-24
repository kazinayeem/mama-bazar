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

<div class="admin-page" x-data="{ createOpen: false, filtersOpen: {{ request('status') || request('q') ? 'true' : 'false' }} }">
    <x-admin.page-header :title="$config['title']" :subtitle="$config['description'].' · '.$items->total().' items'">
        <x-slot:actions>
            <x-admin.button type="button" size="sm" @click="createOpen = !createOpen">
                Add {{ $singular }}
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div x-show="createOpen" x-cloak class="admin-surface p-4">
        <h2 class="mb-3 text-sm font-bold text-slate-900">Create {{ $singular }}</h2>
        <form action="{{ route($config['route'].'.store') }}" method="POST" class="grid gap-3 sm:grid-cols-2">
            @csrf
            @foreach($config['fields'] as $field)
                <div class="{{ !empty($field['full']) ? 'sm:col-span-2' : '' }}">
                    @include('admin.catalog._field', ['field' => $field, 'value' => old($field['name'])])
                </div>
            @endforeach
            <div class="flex justify-end gap-2 pt-1 sm:col-span-2">
                <x-admin.button type="button" variant="outline" size="sm" @click="createOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Create</x-admin.button>
            </div>
        </form>
    </div>

    <form method="GET" class="admin-filter-bar">
        <x-admin.search-input name="q" placeholder="Search..." class="sm:max-w-md" />
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="flex w-full items-center justify-between rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] px-3 py-2.5 text-xs font-semibold text-slate-700 md:hidden">
            <span>Status{{ request('status') ? ': '.ucfirst(request('status')) : '' }}</span>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
             :class="filtersOpen ? 'flex' : 'hidden md:flex'">
            <select name="status" class="admin-control w-full sm:w-40">
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
                                        <img src="{{ $item->logo }}" alt="" class="h-10 w-10 rounded-[6px] border border-[var(--admin-border)] object-cover bg-slate-50" loading="lazy">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] text-[10px] font-bold text-slate-400">—</div>
                                    @endif
                                @endif
                                @if(in_array('hex', $config['columns'], true) && $item->hex)
                                    <span class="admin-swatch mt-1 shrink-0" style="background: {{ $item->hex }}"></span>
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
                            <x-admin.button href="#edit-{{ $item->id }}" variant="outline" size="sm" class="flex-1">Edit</x-admin.button>
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
                                        <x-admin.button href="#edit-{{ $item->id }}" variant="ghost" size="sm">Edit</x-admin.button>
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

            @foreach($items as $item)
                <details id="edit-{{ $item->id }}" class="border-t border-[var(--admin-border)]">
                    <summary class="cursor-pointer px-4 py-2.5 text-xs font-semibold text-brand-green-700 hover:bg-brand-green-50">
                        Edit #{{ $item->id }} — {{ \Illuminate\Support\Str::limit($item->name ?? $item->title ?? $item->text ?? 'Item', 60) }}
                    </summary>
                    <form action="{{ route($config['route'].'.update', $item->id) }}" method="POST" class="grid gap-3 p-4 sm:grid-cols-2">
                        @csrf
                        @method('PUT')
                        @foreach($config['fields'] as $field)
                            <div class="{{ !empty($field['full']) ? 'sm:col-span-2' : '' }}">
                                @include('admin.catalog._field', ['field' => $field, 'value' => old($field['name'], $item->{$field['name']} ?? null)])
                            </div>
                        @endforeach
                        <div class="flex justify-end sm:col-span-2">
                            <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
                        </div>
                    </form>
                </details>
            @endforeach

            <x-admin.pagination :paginator="$items" />
        @endif
    </div>
</div>
@endsection
