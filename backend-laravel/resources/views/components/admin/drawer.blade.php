@props([
    'name' => 'drawerOpen',
    'title' => null,
    'side' => 'right',
    'width' => 'md',
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ];
    $sideClass = $side === 'left' ? 'left-0' : 'right-0';
    $enterFrom = $side === 'left' ? '-translate-x-full' : 'translate-x-full';
@endphp

<div
    x-show="{{ $name }}"
    x-cloak
    class="fixed inset-0 z-[250]"
    {{ $attributes }}
>
    <div class="absolute inset-0 bg-black/45" @click="{{ $name }} = false" x-transition.opacity></div>
    <div
        class="absolute inset-y-0 {{ $sideClass }} flex w-full {{ $widths[$width] ?? $widths['md'] }} flex-col border-[var(--admin-border)] bg-white shadow-panel {{ $side === 'left' ? 'border-r' : 'border-l' }}"
        x-transition:enter="transition transform duration-200 ease-out"
        x-transition:enter-start="{{ $enterFrom }}"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform duration-150 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="{{ $enterFrom }}"
        @click.stop
    >
        @if($title)
            <div class="flex h-14 shrink-0 items-center justify-between border-b border-[var(--admin-border)] px-4">
                <h2 class="text-sm font-bold text-slate-900">{{ $title }}</h2>
                <button type="button" class="rounded-[6px] p-1.5 text-slate-400 hover:bg-slate-100" @click="{{ $name }} = false" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        <div class="flex-1 overflow-y-auto p-4">
            {{ $slot }}
        </div>
        @isset($footer)
            <div class="shrink-0 border-t border-[var(--admin-border)] px-4 py-3">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
