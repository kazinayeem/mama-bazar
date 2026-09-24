@props(['variant' => 'default'])

@php
    $variants = [
        'default' => 'bg-brand-green-50 text-brand-green-700 border-brand-green-100',
        'secondary' => 'bg-slate-100 text-slate-600 border-slate-200',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-100',
        'destructive' => 'bg-red-50 text-red-700 border-red-100',
        'orange' => 'bg-brand-orange-50 text-brand-orange-700 border-brand-orange-100',
        'muted' => 'bg-slate-50 text-slate-500 border-slate-100',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center rounded-[6px] border px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide '.($variants[$variant] ?? $variants['default'])
]) }}>{{ $slot }}</span>
