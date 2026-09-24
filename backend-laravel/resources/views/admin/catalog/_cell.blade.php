@if($col === 'logo')
    @if($item->logo)
        <img src="{{ $item->logo }}" alt="" class="h-9 w-9 rounded-[6px] border border-[var(--admin-border)] object-cover bg-slate-50" loading="lazy">
    @else
        <div class="flex h-9 w-9 items-center justify-center rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] text-[10px] font-bold text-slate-400">—</div>
    @endif
@elseif($col === 'hex' && $item->hex)
    <span class="inline-flex items-center gap-2">
        <span class="admin-swatch" style="background: {{ $item->hex }}"></span>
        <code class="font-mono text-xs text-slate-600">{{ $item->hex }}</code>
    </span>
@elseif($col === 'featured')
    <x-admin.badge :variant="$item->featured ? 'warning' : 'muted'">{{ $item->featured ? 'Yes' : 'No' }}</x-admin.badge>
@elseif($col === 'status')
    <x-admin.badge :variant="($item->status ?? '') === 'active' ? 'default' : 'muted'">{{ $item->status ?? '—' }}</x-admin.badge>
@elseif($col === 'products_count')
    <span class="font-semibold text-slate-800">{{ number_format($item->products_count ?? 0) }}</span>
@elseif($col === 'created_at')
    <span class="text-slate-500">{{ optional($item->created_at)->format('M d, Y') ?? '—' }}</span>
@elseif($col === 'text')
    <span class="line-clamp-2 max-w-md">{{ $item->text }}</span>
@elseif($col === 'name')
    <span class="font-semibold text-slate-900">{{ $item->name }}</span>
@elseif($col === 'slug')
    <code class="rounded bg-slate-50 px-1.5 py-0.5 font-mono text-[11px] text-slate-600">{{ $item->slug ?? '—' }}</code>
@else
    {{ $item->{$col} ?? '—' }}
@endif
