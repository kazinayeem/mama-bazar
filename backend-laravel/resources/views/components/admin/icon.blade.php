@props([
    'name' => null,
    'size' => 18,
])

@php
    $paths = \App\Support\AdminNav::lucide();
    $inner = $name ? ($paths[$name] ?? '') : trim((string) $slot);
    $px = (int) $size;
@endphp

<svg
    {{ $attributes->class('admin-sidebar-icon') }}
    xmlns="http://www.w3.org/2000/svg"
    width="{{ $px }}"
    height="{{ $px }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>
    {!! $inner !!}
</svg>
