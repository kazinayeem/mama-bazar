@props([
    'align' => 'right',
])

@php
    $panelAlign = $align === 'left' ? 'left-0' : 'right-0';
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} x-data="{ open: false }" @keydown.escape.window="open = false">
    <div @click="open = !open">
        {{ $trigger }}
    </div>
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition
        class="absolute {{ $panelAlign }} z-30 mt-1.5 min-w-[11rem] overflow-hidden rounded-[8px] border border-[var(--admin-border)] bg-white py-1 shadow-panel"
    >
        {{ $slot }}
    </div>
</div>
