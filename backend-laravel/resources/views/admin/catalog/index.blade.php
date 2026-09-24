@extends('layouts.admin', ['headerTitle' => $headerTitle ?? $config['title']])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $config['title'] }}</h1>
        <p class="text-sm text-slate-500">{{ $config['description'] }} · {{ $items->total() }} items</p>
    </div>
    <button type="button" onclick="document.getElementById('create-panel').classList.toggle('hidden')"
            class="inline-flex items-center justify-center rounded-full bg-brand-green-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-brand-green-600">
        Add {{ \Illuminate\Support\Str::singular($config['title']) }}
    </button>
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
        <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
            <button type="button" onclick="document.getElementById('create-panel').classList.add('hidden')"
                    class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="submit" class="rounded-full bg-brand-green-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-green-600">Create</button>
        </div>
    </form>
</div>

<form method="GET" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
    <div class="relative flex-1 sm:max-w-xs">
        <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..." class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-8 pr-3 text-sm focus:border-brand-green-500 focus:outline-none">
    </div>
    <select name="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm sm:w-44" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <option value="active" @selected(request('status')==='active')>Active</option>
        <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
    </select>
    <button type="submit" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">Filter</button>
</form>

<div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
    <div class="overflow-x-auto">
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
                @forelse($items as $item)
                    <tr class="hover:bg-slate-50/60">
                        @foreach($config['columns'] as $col)
                            <td class="px-4 py-3 text-slate-700">
                                @if($col === 'hex' && $item->hex)
                                    <span class="inline-flex items-center gap-2">
                                        <span class="inline-block h-5 w-5 rounded border border-slate-200" style="background: {{ $item->hex }}"></span>
                                        <code class="font-mono text-xs">{{ $item->hex }}</code>
                                    </span>
                                @elseif($col === 'featured')
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item->featured ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $item->featured ? 'Yes' : 'No' }}
                                    </span>
                                @elseif($col === 'status')
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ ($item->status ?? '') === 'active' ? 'bg-brand-green-50 text-brand-green-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $item->status ?? '—' }}
                                    </span>
                                @elseif($col === 'text')
                                    <span class="line-clamp-2 max-w-md">{{ $item->text }}</span>
                                @else
                                    {{ $item->{$col} ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="#edit-{{ $item->id }}" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Edit</a>
                                <form action="{{ route($config['route'].'.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this item?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($config['columns']) + 1 }}" class="px-4 py-12 text-center text-sm text-slate-500">
                            {{ $config['empty'] }}
                        </td>
                    </tr>
                @endforelse
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
                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="rounded-full bg-brand-green-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-green-600">Save Changes</button>
                </div>
            </form>
        </details>
    @endforeach

    @if($items->hasPages())
        <div class="flex items-center justify-between border-t border-slate-100 p-3 text-xs text-slate-500">
            <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }} · {{ $items->total() }} items</span>
            <div>{{ $items->links() }}</div>
        </div>
    @endif
</div>
@endsection
