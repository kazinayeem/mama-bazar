@props([
    'name' => 'q',
    'placeholder' => 'Search...',
    'value' => null,
])

@php
    $inputValue = $value ?? request($name);
@endphp

<div class="relative min-w-0 flex-1">
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input
        type="search"
        name="{{ $name }}"
        value="{{ $inputValue }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'admin-control w-full pl-9']) }}
    >
</div>
