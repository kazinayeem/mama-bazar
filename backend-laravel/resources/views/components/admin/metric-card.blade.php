@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'default',
])

@php
    $valueClass = match($tone) {
        'success' => 'text-brand-green-700',
        'warning' => 'text-amber-600',
        'danger' => 'text-red-600',
        'accent' => 'text-brand-orange-600',
        default => 'text-slate-900',
    };
@endphp

<div {{ $attributes->merge(['class' => 'admin-surface p-4']) }}>
    <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-xl font-bold tracking-tight {{ $valueClass }}">{{ $value }}</p>
    @if($hint)
        <p class="mt-1 text-[11px] text-slate-400">{{ $hint }}</p>
    @endif
    {{ $slot }}
</div>
