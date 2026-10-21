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
            <div class="mb-5 flex items-center justify-between gap-3 border-b border-slate-200/60 pb-3">
                <div class="min-w-0">
                    @if($eyebrow)
                        <span class="mb-1 inline-flex items-center rounded-full border border-pink-200 bg-pink-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em]" style="color:#e2006a">{{ $eyebrow }}</span>
                    @endif
                    @if($title)
                        <h2 class="text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl lg:text-2xl">{{ $title }}</h2>
                    @endif
                    @if($subtitle)
                        <p class="mt-0.5 max-w-2xl text-xs text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($ctaText && $ctaUrl)
                    <a href="{{ $ctaUrl }}" class="group inline-flex items-center gap-1 text-xs font-bold text-slate-600 transition hover:text-[#e2006a]">
                        <span>{{ $ctaText }}</span>
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @elseif($title && !in_array($section['type'] ?? '', ['hero', 'trust_strip', 'why_choose_us', 'newsletter']))
                    <a href="{{ route('shop') }}" class="group inline-flex items-center gap-1 text-xs font-bold text-slate-600 transition hover:text-[#e2006a]">
                        <span>View All</span>
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
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
