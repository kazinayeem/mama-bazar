@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap';
    $sizes = [
        'sm' => 'h-8 rounded-[6px] px-2.5 text-xs',
        'md' => 'h-10 rounded-[6px] px-3.5 text-sm',
        'lg' => 'h-11 rounded-[6px] px-4 text-sm',
        'icon' => 'h-9 w-9 rounded-[6px] p-0',
    ];
    $variants = [
        'primary' => 'bg-brand-green-500 text-white hover:bg-brand-green-600',
        'secondary' => 'bg-slate-100 text-slate-800 hover:bg-slate-200',
        'outline' => 'border border-[var(--admin-border)] bg-white text-slate-700 hover:bg-[var(--admin-muted)]',
        'ghost' => 'text-slate-600 hover:bg-slate-100',
        'destructive' => 'bg-red-600 text-white hover:bg-red-700',
        'accent' => 'bg-brand-orange-500 text-white hover:bg-brand-orange-600',
    ];
    $class = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
