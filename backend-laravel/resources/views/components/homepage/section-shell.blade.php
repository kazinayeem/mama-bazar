@props(['section'])

@php
    $bg = match ($section['background'] ?? 'default') {
        'muted' => 'bg-brand-green-50/70',
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

<section {{ $attributes->merge(['class' => "store-section {$bg}"]) }}>
    <div class="store-container">
        @if($showHeader)
            <div class="store-section-header mb-5 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    @if($eyebrow)
                        <span class="mb-2 inline-flex items-center rounded-full border border-brand-orange-200 bg-brand-orange-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em] text-brand-orange-600">{{ $eyebrow }}</span>
                    @endif
                    @if($title)
                        <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl lg:text-[28px] lg:leading-tight">{{ $title }}</h2>
                    @endif
                    @if($subtitle)
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($ctaText && $ctaUrl)
                    <a href="{{ $ctaUrl }}" class="store-btn store-btn-ghost hidden shrink-0 sm:inline-flex">
                        {{ $ctaText }}
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        @endif

        {{ $slot }}

        @if($ctaText && $ctaUrl)
            <div class="mt-5 text-center sm:hidden">
                <a href="{{ $ctaUrl }}" class="store-btn store-btn-primary inline-flex">
                    {{ $ctaText }}
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        @endif
    </div>
</section>
