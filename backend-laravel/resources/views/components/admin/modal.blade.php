@props([
    'name' => 'modal',
    'title' => null,
    'maxWidth' => 'lg',
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
@endphp

<div
    x-show="{{ $name }}"
    x-cloak
    class="fixed inset-0 z-[250] flex items-end justify-center sm:items-center"
    {{ $attributes }}
>
    <div class="absolute inset-0 bg-black/50" @click="{{ $name }} = false" x-transition.opacity></div>
    <div
        class="relative z-10 w-full {{ $widths[$maxWidth] ?? $widths['lg'] }} max-h-[92vh] overflow-y-auto rounded-t-2xl border border-slate-200 bg-white shadow-panel sm:rounded-2xl"
        x-transition
        @click.stop
    >
        @if($title)
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
                <h2 class="text-base font-bold text-slate-900">{{ $title }}</h2>
                <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="{{ $name }} = false" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
        <div class="p-5">
            {{ $slot }}
        </div>
    </div>
</div>
