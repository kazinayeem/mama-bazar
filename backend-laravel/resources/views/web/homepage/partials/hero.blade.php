@php
    $slides = collect($slides ?? [])
        ->filter(fn ($s) => ($s['status'] ?? 'active') === 'active')
        ->values()
        ->all();
    $searches = array_slice($popularSearches ?? [], 0, 8);
    $first = $slides[0] ?? null;
    $heroCategories = \App\Models\Category::query()->limit(10)->get();
@endphp

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
    <section class="relative bg-[#f5f5f5] py-3 sm:py-4">
        <div class="store-container relative">
            <div class="flex gap-4 items-stretch">
                {{-- Left: Category Sidebar on Desktop --}}
                <div class="hidden lg:flex flex-col w-60 shrink-0 rounded-xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Categories</span>
                        <a href="{{ route('shop') }}" class="text-[11px] font-bold text-[#e2006a] hover:underline">View All</a>
                    </div>
                    <div class="flex-1 divide-y divide-slate-50 overflow-y-auto">
                        @foreach($heroCategories as $cat)
                            <a href="{{ route('shop', ['category' => $cat->slug]) }}" class="flex items-center justify-between px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-pink-50 hover:text-[#e2006a] transition group">
                                <span class="flex items-center gap-2.5 truncate">
                                    @if($cat->image)
                                        <img src="{{ $cat->image }}" alt="{{ $cat->name }}" class="w-5 h-5 object-contain rounded">
                                    @else
                                        <span class="w-2 h-2 rounded-full" style="background:#e2006a"></span>
                                    @endif
                                    <span class="truncate">{{ $cat->name }}</span>
                                </span>
                                <svg class="h-3 w-3 text-slate-300 group-hover:text-[#e2006a] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Center/Main: Hero Slider --}}
                <div class="flex-1 min-w-0 relative overflow-hidden rounded-xl shadow-md bg-white">
                    {{-- SSR Initial Slide (Immediately indexable for SEO & Fast LCP) --}}
                    @if($first)
                        <div x-show="index === 0" class="relative">
                            <div class="relative min-h-[280px] sm:min-h-[360px] lg:min-h-[420px]" style="background-color: {{ $first['backgroundColor'] ?? '#0f172a' }}">
                                <picture>
                                    @if(!empty($first['mobileImage']))
                                        <source media="(max-width: 640px)" srcset="{{ $first['mobileImage'] }}">
                                    @endif
                                    @if(!empty($first['tabletImage']))
                                        <source media="(max-width: 1024px)" srcset="{{ $first['tabletImage'] }}">
                                    @endif
                                    <img src="{{ $first['desktopImage'] ?? ($first['image'] ?? '/brandlogo.png') }}"
                                         alt="{{ $first['title'] ?? 'Mama Bazar Online Shopping' }}"
                                         class="absolute inset-0 h-full w-full object-cover"
                                         loading="eager"
                                         fetchpriority="high"
                                         width="1280"
                                         height="420">
                                </picture>
                                @if(($first['overlay'] ?? true) !== false)
                                    <div class="absolute inset-0 bg-black" style="opacity: {{ $first['overlayOpacity'] ?? 0.55 }}"></div>
                                @endif
                                <div class="relative z-10 flex min-h-[280px] w-full flex-col justify-center px-5 py-7 sm:min-h-[360px] sm:px-12 sm:py-10 lg:min-h-[420px] lg:px-14 {{ ($first['alignment'] ?? 'left') === 'center' ? 'items-center text-center' : (($first['alignment'] ?? 'left') === 'right' ? 'items-end text-right' : 'items-start text-left') }}" style="color: {{ $first['textColor'] ?? '#ffffff' }}">
                                    <div class="max-w-xl space-y-2.5 sm:space-y-3">
                                        @if(!empty($first['badge']))
                                            <span class="inline-block rounded-full px-3 py-1 text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-white" style="background:#e2006a">{{ $first['badge'] }}</span>
                                        @endif
                                        <h1 class="text-xl font-extrabold tracking-tight sm:text-3xl md:text-4xl lg:text-5xl break-words leading-snug sm:leading-tight">
                                            {{ $first['title'] ?? 'Mama Bazar — Online Grocery & Lifestyle Essentials in Bangladesh' }}
                                        </h1>
                                        @if(!empty($first['subtitle']))
                                            <p class="text-sm opacity-90 sm:text-base lg:text-lg break-words">{{ $first['subtitle'] }}</p>
                                        @endif
                                        @if(!empty($first['description']))
                                            <p class="text-xs sm:text-sm opacity-80 break-words line-clamp-2 sm:line-clamp-none">{{ $first['description'] }}</p>
                                        @endif
                                        @if(!empty($first['primaryButtonText']) && !empty($first['primaryButtonUrl']))
                                            <div class="flex flex-wrap gap-2.5 sm:gap-3 pt-2 {{ ($first['alignment'] ?? 'left') === 'center' ? 'justify-center' : (($first['alignment'] ?? 'left') === 'right' ? 'justify-end' : '') }}">
                                                <a href="{{ $first['primaryButtonUrl'] }}" class="inline-flex items-center justify-center rounded-full px-6 py-2.5 text-xs sm:text-sm font-extrabold text-white shadow-md transition hover:brightness-110" style="background:#e2006a">
                                                    {{ $first['primaryButtonText'] }}
                                                </a>
                                                @if(!empty($first['secondaryButtonText']) && !empty($first['secondaryButtonUrl']))
                                                    <a href="{{ $first['secondaryButtonUrl'] }}" class="inline-flex items-center rounded-full border border-white/50 bg-white/15 px-5 py-2 sm:px-6 sm:py-2.5 text-xs sm:text-sm font-bold text-white backdrop-blur transition hover:bg-white/25">
                                                        {{ $first['secondaryButtonText'] }}
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                {{-- Dynamic Subsequent Slides via Alpine.js --}}
                <template x-for="(slide, i) in slides" :key="slide.id || i">
                    <div
                        x-show="i > 0 && index === i"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="relative"
                    >
                        <div class="relative min-h-[280px] sm:min-h-[360px] lg:min-h-[420px]" :style="'background-color:' + (slide.backgroundColor || '#0f172a')">
                            <picture>
                                <source media="(max-width: 640px)" :srcset="slide.mobileImage || slide.tabletImage || slide.desktopImage">
                                <source media="(max-width: 1024px)" :srcset="slide.tabletImage || slide.desktopImage">
                                <img :src="slide.desktopImage" :alt="slide.title || 'Hero'" class="absolute inset-0 h-full w-full object-cover" loading="lazy" width="1280" height="420">
                            </picture>
                            <div
                                x-show="slide.overlay !== false"
                                class="absolute inset-0 bg-black"
                                :style="'opacity:' + (slide.overlayOpacity ?? 0.55)"
                            ></div>
                            <div class="relative z-10 flex min-h-[280px] w-full flex-col justify-center px-5 py-7 sm:min-h-[360px] sm:px-12 sm:py-10 lg:min-h-[420px] lg:px-16"
                                 :class="{
                                    'items-start text-left': (slide.alignment || 'left') === 'left',
                                    'items-center text-center': slide.alignment === 'center',
                                    'items-end text-right': slide.alignment === 'right',
                                 }"
                                 :style="'color:' + (slide.textColor || '#ffffff')">
                                <div class="max-w-xl space-y-2.5 sm:space-y-3">
                                    <span x-show="slide.badge" class="inline-block rounded-full bg-brand-orange-500 px-2.5 py-0.5 sm:px-3 sm:py-1 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-white" x-text="slide.badge"></span>
                                    <h2 x-show="slide.title" class="text-xl font-extrabold tracking-tight sm:text-3xl md:text-4xl lg:text-5xl break-words leading-snug sm:leading-tight" x-text="slide.title"></h2>
                                    <p x-show="slide.subtitle" class="text-sm opacity-90 sm:text-base lg:text-lg break-words" x-text="slide.subtitle"></p>
                                    <p x-show="slide.description" class="text-xs sm:text-sm opacity-80 break-words line-clamp-2 sm:line-clamp-none" x-text="slide.description"></p>
                                    <div class="flex flex-wrap gap-2.5 sm:gap-3 pt-2" :class="slide.alignment === 'center' ? 'justify-center' : (slide.alignment === 'right' ? 'justify-end' : '')">
                                        <template x-if="slide.primaryButtonText && slide.primaryButtonUrl">
                                            <a :href="slide.primaryButtonUrl" class="store-btn store-btn-primary !rounded-full !px-5 !py-2 sm:!px-6 sm:!py-2.5 !text-xs sm:!text-sm shadow-md" x-text="slide.primaryButtonText"></a>
                                        </template>
                                        <template x-if="slide.secondaryButtonText && slide.secondaryButtonUrl">
                                            <a :href="slide.secondaryButtonUrl" class="inline-flex items-center rounded-full border border-white/50 bg-white/15 px-5 py-2 sm:px-6 sm:py-2.5 text-xs sm:text-sm font-bold text-white backdrop-blur transition hover:bg-white/25" x-text="slide.secondaryButtonText"></a>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="slides.length > 1">
                    <div>
                        <button type="button" @click="prev()" class="hidden sm:flex absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/90 p-2 text-slate-800 shadow transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green-500" aria-label="Previous">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="next()" class="hidden sm:flex absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/90 p-2 text-slate-800 shadow hover:bg-white focus:outline-none focus:ring-2 focus:ring-brand-green-500" aria-label="Next">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <div class="absolute bottom-3 sm:bottom-4 left-0 right-0 z-20 flex justify-center gap-2">
                            <template x-for="(s, i) in slides" :key="'dot-'+i">
                                <button type="button" @click="index = i" class="h-2 w-2 rounded-full transition" :class="index === i ? 'bg-white' : 'bg-white/40'" :aria-label="'Go to slide ' + (i+1)"></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            </div>
        </div>
    </section>
</div>
@else
    <section class="relative bg-white py-4 sm:py-6">
        <div class="store-container">
            <div class="rounded-xl bg-gradient-to-r from-brand-green-900 to-slate-900 px-5 py-8 text-center text-white sm:px-12 sm:py-16 shadow-lg">
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl break-words">
                    Mama Bazar — Online Grocery & Lifestyle Essentials in Bangladesh
                </h1>
                <p class="mx-auto mt-3 max-w-2xl text-sm sm:text-base text-brand-green-100 sm:text-lg break-words">
                    Quality groceries, fresh produce, and lifestyle essentials delivered fast and reliably across Bangladesh.
                </p>
                <div class="mt-5 sm:mt-6 flex justify-center gap-3">
                    <a href="{{ route('shop') }}" class="store-btn store-btn-primary !rounded-full !px-6 !py-2.5 !text-sm shadow-md">
                        Shop Now
                    </a>
                </div>
            </div>
        </div>
    </section>
@endif

@if(count($searches) > 0)
    <section class="store-container pt-3 sm:pt-4">
        <div class="flex flex-col items-start gap-3 rounded-[10px] border border-slate-200 bg-white px-3.5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">Popular searches</p>
                <p class="mt-0.5 text-xs sm:text-sm text-slate-600">Jump into what shoppers are looking for.</p>
            </div>
            <div class="flex flex-wrap gap-1.5 sm:gap-2">
                @foreach($searches as $term)
                    <a href="{{ route('shop', ['q' => $term]) }}" class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] sm:px-3 sm:py-1.5 sm:text-xs font-semibold text-slate-700 transition hover:border-brand-green-500 hover:bg-brand-green-500 hover:text-white">{{ $term }}</a>
                @endforeach
            </div>
        </div>
    </section>
@endif
