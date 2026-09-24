@props(['section'])

@php
    $bg = match ($section['background'] ?? 'default') {
        'muted' => 'bg-brand-green-50',
        'dark' => 'bg-slate-100',
        default => 'bg-white',
    };
    $title = trim($section['title'] ?? '');
    $subtitle = trim($section['subtitle'] ?? '');
    $eyebrow = trim($section['eyebrow'] ?? '');
    $ctaText = trim($section['ctaText'] ?? '');
    $ctaUrl = trim($section['ctaUrl'] ?? '');
    $showHeader = $title || $subtitle || $eyebrow;
@endphp

<section {{ $attributes->merge(['class' => "py-6 lg:py-8 {$bg}"]) }}>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if($showHeader)
            <div class="mb-4 flex items-end justify-between gap-4 lg:mb-6">
                <div class="min-w-0">
                    @if($eyebrow)
                        <span class="mb-2.5 inline-flex items-center rounded-full border border-brand-orange-200 bg-brand-orange-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-orange-600">{{ $eyebrow }}</span>
                    @endif
                    @if($title)
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl lg:text-[32px] lg:leading-tight">{{ $title }}</h2>
                    @endif
                    @if($subtitle)
                        <p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-500 sm:text-[15px]">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($ctaText && $ctaUrl)
                    <a href="{{ $ctaUrl }}" class="hidden shrink-0 items-center gap-1.5 rounded-full bg-brand-orange-500 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-brand-orange-600 sm:inline-flex">
                        {{ $ctaText }}
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        @endif

        {{ $slot }}

        @if($ctaText && $ctaUrl)
            <div class="mt-6 text-center sm:hidden">
                <a href="{{ $ctaUrl }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-orange-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-brand-orange-600">
                    {{ $ctaText }}
                </a>
            </div>
        @endif
    </div>
</section>
