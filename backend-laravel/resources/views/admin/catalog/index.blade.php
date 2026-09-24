@extends('layouts.admin', ['headerTitle' => $headerTitle ?? $config['title']])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $config['title'] }}</h1>
        <p class="text-sm text-slate-500">{{ $config['description'] }} · {{ $items->total() }} items</p>
    </div>
    <x-admin.button type="button" onclick="document.getElementById('create-panel').classList.toggle('hidden')">
        Add {{ \Illuminate\Support\Str::singular($config['title']) }}
    </x-admin.button>
</div>

<div id="create-panel" class="mt-4 hidden rounded-xl border border-slate-200 bg-white p-5 shadow-soft">
    <h2 class="mb-4 text-sm font-bold text-slate-900">Create {{ \Illuminate\Support\Str::singular($config['title']) }}</h2>
    <form action="{{ route($config['route'].'.store') }}" method="POST" class="grid gap-3 sm:grid-cols-2">
        @csrf
        @foreach($config['fields'] as $field)
            <div class="{{ !empty($field['full']) ? 'sm:col-span-2' : '' }}">
                @include('admin.catalog._field', ['field' => $field, 'value' => old($field['name'])])
            </div>
        @endforeach
        <div class="flex justify-end gap-2 pt-2 sm:col-span-2">
            <x-admin.button type="button" variant="outline" size="sm" onclick="document.getElementById('create-panel').classList.add('hidden')">Cancel</x-admin.button>
            <x-admin.button type="submit" size="sm">Create</x-admin.button>
        </div>
    </form>
</div>

<form method="GET" class="mt-4 flex flex-col gap-3" x-data="{ filtersOpen: {{ request('status') ? 'true' : 'false' }} }">
    <div class="relative flex-1 sm:max-w-md">
        <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-8 pr-3 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
    </div>
    <button type="button" @click="filtersOpen = !filtersOpen"
            class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 md:hidden">
        <span>Status filter{{ request('status') ? ': '.ucfirst(request('status')) : '' }}</span>
        <svg class="h-4 w-4 text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center"
         :class="filtersOpen ? 'flex' : 'hidden md:flex'">
        <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm sm:w-44">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
        </select>
        <x-admin.button type="submit" variant="outline" size="sm" class="w-full sm:w-auto">Apply filters</x-admin.button>
    </div>
</form>

<div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
    @if($items->isEmpty())
        <x-admin.empty-state :title="$config['empty']" description="Try adjusting search or status filters." />
    @else
        {{-- Mobile cards --}}
        <div class="divide-y divide-slate-100 md:hidden">
            @foreach($items as $item)
                @php
                    $cardTitle = $item->name ?? $item->title ?? (\Illuminate\Support\Str::limit($item->text ?? '', 48) ?: null) ?? ('Item #'.$item->id);
                    $statusActive = ($item->status ?? '') === 'active';
                @endphp
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $cardTitle }}</p>
                            <dl class="mt-2 space-y-1">
                                @foreach($config['columns'] as $col)
                                    @if($col === 'status')
                                        @continue
                                    @endif
                                    <div class="flex gap-2 text-xs">
                                        <dt class="shrink-0 font-semibold uppercase tracking-wide text-slate-400">{{ str_replace('_', ' ', $col) }}</dt>
                                        <dd class="min-w-0 text-slate-700">
                                            @if($col === 'hex' && $item->hex)
                                                <span class="inline-flex items-center gap-1.5">
                                                    <span class="inline-block h-4 w-4 rounded border border-slate-200" style="background: {{ $item->hex }}"></span>
                                                    <code class="font-mono text-[10px]">{{ $item->hex }}</code>
                                                </span>
                                            @elseif($col === 'featured')
                                                {{ $item->featured ? 'Yes' : 'No' }}
                                            @elseif($col === 'text')
                                                <span class="line-clamp-2">{{ $item->text }}</span>
                                            @else
                                                {{ $item->{$col} ?? '—' }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
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
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        @foreach($config['columns'] as $col)
                            <th class="px-4 py-3">{{ str_replace('_', ' ', $col) }}</th>
                        @endforeach
                        <th class="w-[140px] px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($items as $item)
                        <tr class="hover:bg-slate-50/60">
                            @foreach($config['columns'] as $col)
                                <td class="px-4 py-3 text-slate-700">
                                    @if($col === 'hex' && $item->hex)
                                        <span class="inline-flex items-center gap-2">
                                            <span class="inline-block h-5 w-5 rounded border border-slate-200" style="background: {{ $item->hex }}"></span>
                                            <code class="font-mono text-xs">{{ $item->hex }}</code>
                                        </span>
                                    @elseif($col === 'featured')
                                        <x-admin.badge :variant="$item->featured ? 'warning' : 'muted'">{{ $item->featured ? 'Yes' : 'No' }}</x-admin.badge>
                                    @elseif($col === 'status')
                                        <x-admin.badge :variant="($item->status ?? '') === 'active' ? 'default' : 'muted'">{{ $item->status ?? '—' }}</x-admin.badge>
                                    @elseif($col === 'text')
                                        <span class="line-clamp-2 max-w-md">{{ $item->text }}</span>
                                    @else
                                        {{ $item->{$col} ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
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
            <details id="edit-{{ $item->id }}" class="border-t border-slate-100">
                <summary class="cursor-pointer px-4 py-2 text-xs font-semibold text-brand-green-700 hover:bg-brand-green-50">
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
@endsection
