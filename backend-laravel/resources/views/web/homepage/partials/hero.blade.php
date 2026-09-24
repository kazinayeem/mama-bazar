@php
    $slides = collect($slides ?? [])
        ->filter(fn ($s) => ($s['status'] ?? 'active') === 'active')
        ->values()
        ->all();
    $searches = array_slice($popularSearches ?? [], 0, 8);
@endphp

<div class="space-y-3">
@if(count($slides) > 0)
<div
    x-data="{
        index: 0,
        slides: {{ Js::from($slides) }},
        timer: null,
        next() { this.index = (this.index + 1) % this.slides.length },
        prev() { this.index = (this.index - 1 + this.slides.length) % this.slides.length },
        start() {
            if (this.slides.length < 2) return;
            this.timer = setInterval(() => this.next(), 4500);
        },
        stop() { if (this.timer) clearInterval(this.timer) },
    }"
    x-init="start()"
    @mouseenter="stop()"
    @mouseleave="start()"
>
    <section class="relative overflow-hidden bg-slate-900">
        <template x-for="(slide, i) in slides" :key="slide.id || i">
            <div
                x-show="index === i"
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="relative"
            >
                <div class="relative min-h-[280px] sm:min-h-[360px] lg:min-h-[420px]" :style="'background-color:' + (slide.backgroundColor || '#0f172a')">
                    <picture>
                        <source media="(max-width: 640px)" :srcset="slide.mobileImage || slide.tabletImage || slide.desktopImage">
                        <source media="(max-width: 1024px)" :srcset="slide.tabletImage || slide.desktopImage">
                        <img :src="slide.desktopImage" :alt="slide.title || 'Hero'" class="absolute inset-0 h-full w-full object-cover" loading="eager">
                    </picture>
                    <div
                        x-show="slide.overlay !== false"
                        class="absolute inset-0 bg-black"
                        :style="'opacity:' + (slide.overlayOpacity ?? 0.55)"
                    ></div>
                    <div class="relative z-10 mx-auto flex min-h-[280px] max-w-7xl flex-col justify-center px-4 py-12 sm:min-h-[360px] sm:px-6 lg:min-h-[420px] lg:px-8"
                         :class="{
                            'items-start text-left': (slide.alignment || 'left') === 'left',
                            'items-center text-center': slide.alignment === 'center',
                            'items-end text-right': slide.alignment === 'right',
                         }"
                         :style="'color:' + (slide.textColor || '#ffffff')">
                        <div class="max-w-xl space-y-3">
                            <span x-show="slide.badge" class="inline-block rounded-full bg-brand-orange-500 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white" x-text="slide.badge"></span>
                            <h1 x-show="slide.title" class="text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl" x-text="slide.title"></h1>
                            <p x-show="slide.subtitle" class="text-base opacity-90 sm:text-lg" x-text="slide.subtitle"></p>
                            <p x-show="slide.description" class="text-sm opacity-80" x-text="slide.description"></p>
                            <div class="flex flex-wrap gap-3 pt-2" :class="slide.alignment === 'center' ? 'justify-center' : (slide.alignment === 'right' ? 'justify-end' : '')">
                                <template x-if="slide.primaryButtonText && slide.primaryButtonUrl">
                                    <a :href="slide.primaryButtonUrl" class="store-btn store-btn-primary !rounded-full !px-6 !py-2.5 !text-sm shadow-md" x-text="slide.primaryButtonText"></a>
                                </template>
                                <template x-if="slide.secondaryButtonText && slide.secondaryButtonUrl">
                                    <a :href="slide.secondaryButtonUrl" class="inline-flex items-center rounded-full border border-white/50 bg-white/15 px-6 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/25" x-text="slide.secondaryButtonText"></a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="slides.length > 1">
            <div>
                <button type="button" @click="prev()" class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/90 p-2 text-slate-800 shadow hover:bg-white" aria-label="Previous">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="next()" class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/90 p-2 text-slate-800 shadow hover:bg-white" aria-label="Next">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div class="absolute bottom-4 left-0 right-0 z-20 flex justify-center gap-2">
                    <template x-for="(s, i) in slides" :key="'dot-'+i">
                        <button type="button" @click="index = i" class="h-2 w-2 rounded-full transition" :class="index === i ? 'bg-white' : 'bg-white/40'" :aria-label="'Go to slide ' + (i+1)"></button>
                    </template>
                </div>
            </div>
        </template>
    </section>
</div>
@endif

@if(count($searches) > 0)
    <section class="store-container pt-4">
        <div class="flex flex-col items-start gap-3 rounded-[10px] border border-slate-200 bg-white px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Popular searches</p>
                <p class="mt-0.5 text-sm text-slate-600">Jump into what shoppers are looking for.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($searches as $term)
                    <a href="{{ route('shop', ['q' => $term]) }}" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-brand-green-500 hover:bg-brand-green-500 hover:text-white">{{ $term }}</a>
                @endforeach
            </div>
        </div>
    </section>
@endif
</div>
