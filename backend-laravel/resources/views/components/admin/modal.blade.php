@props([
    'name' => 'modal',
    'title' => null,
    'xTitle' => null,
    'subtitle' => null,
    'xSubtitle' => null,
    'maxWidth' => 'lg',
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
    ];
@endphp

<div
    x-show="{{ $name }}"
    x-cloak
    class="fixed inset-0 z-[250] flex items-end justify-center sm:items-center p-0 sm:p-4"
    @keydown.escape.window="{{ $name }} = false"
    {{ $attributes }}
>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
        @click="{{ $name }} = false"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- Modal Dialog Panel -->
    <div
        class="relative z-10 flex max-h-[92vh] w-full {{ $widths[$maxWidth] ?? $widths['lg'] }} flex-col overflow-hidden rounded-t-2xl border border-[var(--admin-border)] bg-white shadow-2xl sm:rounded-2xl"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        @click.stop
    >
        @if(isset($header))
            <div class="flex shrink-0 items-center justify-between border-b border-[var(--admin-border)] px-5 py-3.5 bg-white">
                <div class="min-w-0 flex-1">
                    {{ $header }}
                </div>
                <button type="button" class="ml-3 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition" @click="{{ $name }} = false" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @elseif($title || $xTitle)
            <div class="flex shrink-0 items-center justify-between border-b border-[var(--admin-border)] px-5 py-3.5 bg-white">
                <div class="min-w-0 flex-1">
                    @if($xTitle)
                        <h2 class="truncate text-sm font-bold text-slate-900" x-text="{{ $xTitle }}"></h2>
                    @else
                        <h2 class="truncate text-sm font-bold text-slate-900">{{ $title }}</h2>
                    @endif

                    @if($xSubtitle)
                        <p class="truncate text-xs text-slate-500 mt-0.5" x-text="{{ $xSubtitle }}" x-show="{{ $xSubtitle }}"></p>
                    @elseif($subtitle)
                        <p class="truncate text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" class="ml-3 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition" @click="{{ $name }} = false" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- Scrollable Form Body -->
        <div class="flex-1 overflow-y-auto p-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-[var(--admin-border)] bg-slate-50/70 px-5 py-3.5">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
