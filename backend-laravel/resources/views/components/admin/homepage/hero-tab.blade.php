{{-- Hero Slides tab (Redesigned for modern SaaS e-commerce admin) --}}
<div class="space-y-4 pt-3">
    {{-- Header & Toolbar Card --}}
    <div class="rounded-[10px] border border-slate-200/90 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-900">Hero Carousel Slides</h2>
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                        <span x-text="config.heroSlides.length"></span> slides
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200/70">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span x-text="activeHeroSlidesCount()"></span> Live
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        <span x-text="inactiveHeroSlidesCount()"></span> Inactive
                    </span>
                </div>
                <p class="text-xs text-slate-500">
                    The top active slide displays first on the storefront carousel. Use arrows to reorder slides.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="openSlideCreate()"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-[6px] bg-brand-green-500 px-3.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-600 active:scale-[0.99] transition shrink-0"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Hero Slide</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Empty State when no slides --}}
    <div x-show="config.heroSlides.length === 0" x-cloak>
        <div class="flex flex-col items-center justify-center rounded-[10px] border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-green-50 text-brand-green-600 border border-brand-green-100">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-sm font-bold text-slate-900">No hero slides yet</h3>
            <p class="mt-1 max-w-sm text-xs text-slate-500">
                Add banner slides to showcase top promotions, seasonal collections, or featured deals at the top of your storefront homepage.
            </p>
            <button
                type="button"
                @click="openSlideCreate()"
                class="mt-4 inline-flex h-9 items-center gap-1.5 rounded-[6px] bg-brand-green-500 px-4 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-600 transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add First Hero Slide</span>
            </button>
        </div>
    </div>

    {{-- Slide Cards List --}}
    <div class="space-y-3" x-show="config.heroSlides.length > 0">
        <template x-for="(slide, index) in config.heroSlides" :key="slide.id">
            <div
                class="group relative rounded-[10px] border border-slate-200/90 bg-white p-3.5 sm:p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] hover:border-slate-300 hover:shadow-[0_4px_14px_rgba(0,0,0,0.06)] transition-all duration-200"
                :class="slide.status !== 'active' ? 'bg-slate-50/50 border-dashed border-slate-300' : 'bg-white'"
            >
                {{-- Desktop & Tablet Layout --}}
                <div class="hidden sm:flex sm:items-center sm:gap-4">
                    {{-- Reorder controls --}}
                    <div class="flex flex-col items-center gap-1 shrink-0">
                        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-[5px] bg-slate-100 text-[11px] font-bold text-slate-600 px-1" :title="'Order index ' + (index + 1)" x-text="'#' + (index + 1)"></span>
                        <button
                            type="button"
                            :disabled="index === 0"
                            @click="moveSlide(index, -1)"
                            class="rounded-[5px] border border-slate-200 bg-white p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition"
                            title="Move slide up"
                            aria-label="Move slide up"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button
                            type="button"
                            :disabled="index === config.heroSlides.length - 1"
                            @click="moveSlide(index, 1)"
                            class="rounded-[5px] border border-slate-200 bg-white p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition"
                            title="Move slide down"
                            aria-label="Move slide down"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>

                    {{-- Image Preview with Overlay simulation --}}
                    <div class="relative h-20 w-36 sm:h-24 sm:w-44 lg:h-28 lg:w-56 shrink-0 overflow-hidden rounded-[8px] border border-slate-200/90 bg-slate-900 shadow-inner group/img">
                        <img x-show="slide.desktopImage" :src="slide.desktopImage" alt="" class="h-full w-full object-cover transition-transform duration-300 group-hover/img:scale-105">
                        <div x-show="!slide.desktopImage" class="flex h-full flex-col items-center justify-center gap-1 bg-slate-100 text-slate-400">
                            <svg class="h-5 w-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-[10px] font-medium">No image</span>
                        </div>

                        {{-- Dark overlay preview simulation --}}
                        <div x-show="slide.desktopImage && slide.overlay !== false" class="absolute inset-0 pointer-events-none" :style="'background-color: rgba(0, 0, 0, ' + (slide.overlayOpacity ?? 0.55) + ')'"></div>

                        {{-- Responsive variant chips --}}
                        <div class="absolute bottom-1 left-1 flex items-center gap-1">
                            <span class="rounded bg-black/75 px-1 py-0.5 text-[9px] font-bold text-white uppercase tracking-wider">Desk</span>
                            <span x-show="slide.tabletImage" class="rounded bg-black/75 px-1 py-0.5 text-[9px] font-bold text-white uppercase tracking-wider">+Tab</span>
                            <span x-show="slide.mobileImage" class="rounded bg-black/75 px-1 py-0.5 text-[9px] font-bold text-white uppercase tracking-wider">+Mob</span>
                        </div>

                        {{-- Custom fallback background swatch --}}
                        <div x-show="slide.backgroundColor && slide.backgroundColor !== '#0f172a'" class="absolute top-1 right-1 h-3.5 w-3.5 rounded-full border border-white shadow-xs" :style="'background-color: ' + slide.backgroundColor" title="Fallback color"></div>
                    </div>

                    {{-- Content details column --}}
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span x-show="index === 0 && slide.status === 'active'" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-800 border border-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                Primary
                            </span>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                :class="slide.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/80' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="slide.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span x-text="slide.status === 'active' ? 'Live' : 'Draft'"></span>
                            </span>
                            <span x-show="slide.badge" class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800 border border-amber-200" x-text="slide.badge"></span>
                            <span class="inline-flex items-center gap-0.5 rounded-[4px] bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 capitalize">
                                <svg class="h-2.5 w-2.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"/></svg>
                                <span x-text="slide.alignment || 'left'"></span>
                            </span>
                        </div>

                        <h3 class="truncate text-sm sm:text-base font-bold text-slate-900 tracking-tight" x-text="(slide.title || '').trim() || (slide.badge || '').trim() || ('Slide ' + (index + 1))"></h3>

                        <p class="truncate text-xs text-slate-500" x-text="slide.subtitle || slide.description || 'No subtitle provided'"></p>

                        <div class="flex flex-wrap items-center gap-2 pt-0.5" x-show="slide.primaryButtonText || slide.secondaryButtonText">
                            <span x-show="slide.primaryButtonText" class="inline-flex items-center gap-1.5 rounded-[5px] bg-slate-100 border border-slate-200/80 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                <span class="font-semibold text-slate-900" x-text="slide.primaryButtonText"></span>
                                <span class="text-slate-400 font-mono text-[10px]" x-text="slide.primaryButtonUrl ? '→ ' + slide.primaryButtonUrl : ''"></span>
                            </span>
                            <span x-show="slide.secondaryButtonText" class="inline-flex items-center gap-1.5 rounded-[5px] bg-slate-100 border border-slate-200/80 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                <span class="font-semibold text-slate-900" x-text="slide.secondaryButtonText"></span>
                                <span class="text-slate-400 font-mono text-[10px]" x-text="slide.secondaryButtonUrl ? '→ ' + slide.secondaryButtonUrl : ''"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Actions and Visibility switch --}}
                    <div class="flex flex-col items-end gap-3 shrink-0">
                        {{-- Visibility switch --}}
                        <div class="flex items-center gap-2">
                            <label class="relative inline-flex cursor-pointer items-center" :title="slide.status === 'active' ? 'Click to make inactive' : 'Click to make live'">
                                <input
                                    type="checkbox"
                                    class="peer sr-only"
                                    :checked="slide.status === 'active'"
                                    @change="updateSlideField(slide.id, { status: $event.target.checked ? 'active' : 'inactive' })"
                                >
                                <span class="h-5 w-9 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-brand-green-500 peer-checked:after:translate-x-full"></span>
                            </label>
                            <span class="text-xs font-semibold select-none min-w-[36px]" :class="slide.status === 'active' ? 'text-emerald-700' : 'text-slate-400'" x-text="slide.status === 'active' ? 'Live' : 'Hidden'"></span>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="duplicateSlide(slide)"
                                class="inline-flex h-8 items-center gap-1 rounded-[6px] border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition"
                                title="Duplicate slide"
                            >
                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span>Copy</span>
                            </button>
                            <button
                                type="button"
                                @click="openSlideEdit(slide)"
                                class="inline-flex h-8 items-center gap-1 rounded-[6px] border border-brand-green-200 bg-brand-green-50/70 px-2.5 text-xs font-semibold text-brand-green-700 shadow-2xs hover:bg-brand-green-100 transition"
                                title="Edit slide"
                            >
                                <svg class="h-3.5 w-3.5 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Edit</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteSlideTarget = slide"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] border border-slate-200 bg-white text-slate-400 shadow-2xs hover:border-red-200 hover:bg-red-50 hover:text-red-600 transition"
                                title="Delete slide"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Mobile Stacked Layout (< sm: 390px - 640px) --}}
                <div class="sm:hidden space-y-3">
                    {{-- Mobile Top Row: Position, Status & Reorder --}}
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-[5px] bg-slate-100 text-[11px] font-bold text-slate-600 px-1" x-text="'#' + (index + 1)"></span>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                :class="slide.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/80' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="slide.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span x-text="slide.status === 'active' ? 'Live' : 'Draft'"></span>
                            </span>
                            <span x-show="slide.badge" class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800 border border-amber-200" x-text="slide.badge"></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    :disabled="index === 0"
                                    @click="moveSlide(index, -1)"
                                    class="rounded-[5px] border border-slate-200 bg-white p-1 text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed"
                                    aria-label="Move slide up"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button
                                    type="button"
                                    :disabled="index === config.heroSlides.length - 1"
                                    @click="moveSlide(index, 1)"
                                    class="rounded-[5px] border border-slate-200 bg-white p-1 text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed"
                                    aria-label="Move slide down"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input
                                    type="checkbox"
                                    class="peer sr-only"
                                    :checked="slide.status === 'active'"
                                    @change="updateSlideField(slide.id, { status: $event.target.checked ? 'active' : 'inactive' })"
                                >
                                <span class="h-5 w-9 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-brand-green-500 peer-checked:after:translate-x-full"></span>
                            </label>
                        </div>
                    </div>

                    {{-- Mobile Image Preview --}}
                    <div class="relative aspect-[16/7] w-full overflow-hidden rounded-[8px] border border-slate-200/90 bg-slate-900 shadow-inner">
                        <img x-show="slide.desktopImage" :src="slide.desktopImage" alt="" class="h-full w-full object-cover">
                        <div x-show="!slide.desktopImage" class="flex h-full flex-col items-center justify-center gap-1 bg-slate-100 text-slate-400">
                            <svg class="h-5 w-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-[10px] font-medium">No image</span>
                        </div>
                        <div x-show="slide.desktopImage && slide.overlay !== false" class="absolute inset-0 pointer-events-none" :style="'background-color: rgba(0, 0, 0, ' + (slide.overlayOpacity ?? 0.55) + ')'"></div>
                        <div class="absolute bottom-1 left-1 flex items-center gap-1">
                            <span class="rounded bg-black/75 px-1 py-0.5 text-[9px] font-bold text-white uppercase">Desk</span>
                            <span x-show="slide.tabletImage" class="rounded bg-black/75 px-1 py-0.5 text-[9px] font-bold text-white uppercase">+Tab</span>
                            <span x-show="slide.mobileImage" class="rounded bg-black/75 px-1 py-0.5 text-[9px] font-bold text-white uppercase">+Mob</span>
                        </div>
                    </div>

                    {{-- Mobile Content details --}}
                    <div>
                        <h3 class="text-sm font-bold text-slate-900" x-text="(slide.title || '').trim() || (slide.badge || '').trim() || ('Slide ' + (index + 1))"></h3>
                        <p class="mt-0.5 text-xs text-slate-500 line-clamp-2" x-text="slide.subtitle || slide.description || 'No subtitle provided'"></p>
                        <div class="mt-1.5 flex flex-wrap gap-1" x-show="slide.primaryButtonText || slide.secondaryButtonText">
                            <span x-show="slide.primaryButtonText" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-700" x-text="'CTA: ' + slide.primaryButtonText"></span>
                            <span x-show="slide.secondaryButtonText" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-700" x-text="'2nd: ' + slide.secondaryButtonText"></span>
                        </div>
                    </div>

                    {{-- Mobile Action Buttons --}}
                    <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-2.5">
                        <button
                            type="button"
                            @click="duplicateSlide(slide)"
                            class="inline-flex h-8 items-center justify-center gap-1 rounded-[6px] border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                        >
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span>Copy</span>
                        </button>
                        <button
                            type="button"
                            @click="openSlideEdit(slide)"
                            class="inline-flex h-8 items-center justify-center gap-1 rounded-[6px] border border-brand-green-200 bg-brand-green-50/70 px-2 text-xs font-semibold text-brand-green-700 hover:bg-brand-green-100 transition"
                        >
                            <svg class="h-3.5 w-3.5 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit</span>
                        </button>
                        <button
                            type="button"
                            @click="deleteSlideTarget = slide"
                            class="inline-flex h-8 items-center justify-center gap-1 rounded-[6px] border border-slate-200 bg-white px-2 text-xs font-semibold text-red-600 hover:border-red-200 hover:bg-red-50 transition"
                        >
                            <svg class="h-3.5 w-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- Slide Editor Modal / Drawer --}}
<div
    x-show="slideEditing"
    x-cloak
    class="fixed inset-0 z-[300] flex items-end justify-center bg-black/50 backdrop-blur-xs p-0 sm:items-center sm:p-4"
    @keydown.escape.window="slideEditing = null"
>
    <div
        class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-t-[12px] bg-white shadow-2xl sm:rounded-[12px] border border-slate-200/90"
        @click.outside="slideEditing = null"
    >
        {{-- Modal Header --}}
        <div class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-200/80 bg-white px-5 py-4 sm:px-6">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-[8px] bg-brand-green-50 text-brand-green-700 border border-brand-green-100">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900" x-text="slideIsNew ? 'Add Hero Slide' : 'Edit Hero Slide'"></h3>
                    <p class="text-xs text-slate-500">Configure responsive banner media, headline copywriting, and visual presentation.</p>
                </div>
            </div>
            <button
                type="button"
                class="rounded-[6px] p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition"
                @click="slideEditing = null"
                aria-label="Close modal"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Validation Error Banner --}}
        <div x-show="slideValidationError" x-cloak class="mx-5 mt-4 sm:mx-6 flex items-center gap-2 rounded-[8px] border border-red-200 bg-red-50 p-3 text-xs font-semibold text-red-800">
            <svg class="h-4 w-4 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="slideValidationError"></span>
        </div>

        {{-- Form Body (template x-if: inner bindings only evaluate when a slide is loaded) --}}
        <template x-if="slideEditing">
        <div class="p-5 sm:p-6 space-y-5">
            {{-- Section 1: Responsive Visual Media --}}
            <div class="rounded-[8px] border border-slate-200/90 bg-slate-50/50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Responsive Banner Images</h4>
                        <p class="text-[11px] text-slate-500">Desktop image is required. Add optional tablet & mobile versions for optimized rendering.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <template x-for="field in ['desktopImage','tabletImage','mobileImage']" :key="field">
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-700" x-text="field === 'desktopImage' ? 'Desktop *' : (field === 'tabletImage' ? 'Tablet' : 'Mobile')"></span>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="field === 'desktopImage' ? '16:6' : (field === 'tabletImage' ? '16:8' : '4:5')"></span>
                            </div>

                            <button
                                type="button"
                                @click="openMediaPicker(field)"
                                class="group relative flex aspect-[16/9] w-full flex-col items-center justify-center gap-1 overflow-hidden rounded-[8px] border-2 border-dashed transition"
                                :class="slideEditing[field] ? 'border-slate-300 bg-slate-900' : 'border-slate-200 bg-white hover:border-brand-green-500 hover:bg-brand-green-50/30'"
                            >
                                <template x-if="slideEditing[field]">
                                    <div class="absolute inset-0">
                                        <img :src="slideEditing[field]" alt="" class="h-full w-full object-cover">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                            <span class="rounded bg-white/90 px-2 py-1 text-[10px] font-bold text-slate-800 shadow-sm">Replace</span>
                                        </div>
                                        <span
                                            role="button"
                                            tabindex="0"
                                            class="absolute right-1.5 top-1.5 rounded-full bg-black/70 p-1 text-white hover:bg-red-600 transition cursor-pointer"
                                            title="Remove image"
                                            aria-label="Remove image"
                                            @click.stop="slideEditing[field] = field === 'desktopImage' ? '' : undefined"
                                            @keydown.enter.prevent="slideEditing[field] = field === 'desktopImage' ? '' : undefined"
                                            @keydown.space.prevent="slideEditing[field] = field === 'desktopImage' ? '' : undefined"
                                        >
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </span>
                                    </div>
                                </template>
                                <template x-if="!slideEditing[field]">
                                    <div class="px-2 text-center text-slate-400 group-hover:text-brand-green-600">
                                        <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="mt-1 block text-[10px] font-semibold">+ Choose Image</span>
                                    </div>
                                </template>
                            </button>
                        </div>
                    </template>
                </div>
                <p x-show="!slideEditing?.desktopImage" class="mt-2 text-[11px] font-medium text-red-600 flex items-center gap-1">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Desktop banner image is required</span>
                </p>
            </div>

            {{-- Section 2: Headline Copy & Content --}}
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Content & Copywriting</h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold text-slate-700">Badge Label</label>
                        <input
                            type="text"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs"
                            x-model="slideEditing.badge"
                            placeholder="e.g. New Collection, 50% Off, Eid Special"
                        >
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700">Slide Headline Title</label>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="((slideEditing.title || '').length) + '/120'"></span>
                        </div>
                        <input
                            type="text"
                            maxlength="120"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs"
                            x-model="slideEditing.title"
                            placeholder="Main promotional headline"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-700">Subtitle</label>
                        <input
                            type="text"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs"
                            x-model="slideEditing.subtitle"
                            placeholder="Secondary supporting headline"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-700">Description</label>
                        <input
                            type="text"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs"
                            x-model="slideEditing.description"
                            placeholder="Short promotional paragraph"
                        >
                    </div>
                </div>
            </div>

            {{-- Section 3: Call-to-Action Buttons --}}
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Call-to-Action Buttons</h4>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold text-slate-700">Primary Button Text</label>
                        <input
                            type="text"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs"
                            x-model="slideEditing.primaryButtonText"
                            placeholder="e.g. Shop Now, Explore Deals"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-700">Primary Button Destination</label>
                        <input
                            type="text"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs font-mono"
                            x-model="slideEditing.primaryButtonUrl"
                            placeholder="/shop or https://..."
                        >
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-700">Secondary Button Text</label>
                        <input
                            type="text"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs"
                            x-model="slideEditing.secondaryButtonText"
                            placeholder="e.g. View Catalog, Learn More"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-700">Secondary Button Destination</label>
                        <input
                            type="text"
                            class="mt-1 w-full admin-control px-3 py-2 text-xs font-mono"
                            x-model="slideEditing.secondaryButtonUrl"
                            placeholder="/collections or https://..."
                        >
                    </div>
                </div>
            </div>

            {{-- Section 4: Visual Appearance & Settings --}}
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Visual Styling & Visibility</h4>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold text-slate-700">Text Alignment</label>
                        <div class="mt-1 grid grid-cols-3 gap-1 rounded-[6px] border border-slate-200 bg-slate-50 p-1">
                            <button
                                type="button"
                                @click="slideEditing.alignment = 'left'"
                                class="flex items-center justify-center gap-1 rounded-[4px] py-1.5 text-xs font-semibold transition"
                                :class="(slideEditing.alignment || 'left') === 'left' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"/></svg>
                                <span>Left</span>
                            </button>
                            <button
                                type="button"
                                @click="slideEditing.alignment = 'center'"
                                class="flex items-center justify-center gap-1 rounded-[4px] py-1.5 text-xs font-semibold transition"
                                :class="slideEditing.alignment === 'center' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M5 18h14"/></svg>
                                <span>Center</span>
                            </button>
                            <button
                                type="button"
                                @click="slideEditing.alignment = 'right'"
                                class="flex items-center justify-center gap-1 rounded-[4px] py-1.5 text-xs font-semibold transition"
                                :class="slideEditing.alignment === 'right' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14"/></svg>
                                <span>Right</span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-700">Fallback Background Color</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input
                                type="color"
                                class="h-9 w-12 cursor-pointer rounded-[6px] border border-slate-200 p-0.5"
                                x-model="slideEditing.backgroundColor"
                            >
                            <input
                                type="text"
                                class="h-9 flex-1 admin-control px-3 text-xs font-mono"
                                x-model="slideEditing.backgroundColor"
                                placeholder="#0f172a"
                            >
                        </div>
                    </div>
                </div>

                <div class="rounded-[8px] border border-slate-200/90 bg-slate-50/50 p-3.5 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                class="rounded text-brand-green-600"
                                :checked="slideEditing.overlay !== false"
                                @change="slideEditing.overlay = $event.target.checked"
                            >
                            <span>Enable dark text contrast overlay</span>
                        </label>

                        <div class="flex items-center gap-2" x-show="slideEditing.overlay !== false">
                            <span class="text-[11px] font-bold text-slate-600">Strength: <span class="font-mono text-brand-green-700" x-text="Math.round((slideEditing.overlayOpacity ?? 0.55) * 100) + '%'"></span></span>
                            <input
                                type="range"
                                min="0"
                                max="1"
                                step="0.05"
                                class="h-2 w-32 cursor-pointer accent-brand-green-600"
                                x-model.number="slideEditing.overlayOpacity"
                            >
                        </div>
                    </div>

                    <div class="border-t border-slate-200/70 pt-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-800">Slide Visibility Status</p>
                            <p class="text-[11px] text-slate-500">Live slides will display on your storefront carousel.</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                class="peer sr-only"
                                :checked="slideEditing.status === 'active'"
                                @change="slideEditing.status = $event.target.checked ? 'active' : 'inactive'"
                            >
                            <span class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-brand-green-500 peer-checked:after:translate-x-full"></span>
                            <span class="ml-2.5 text-xs font-semibold" :class="slideEditing.status === 'active' ? 'text-emerald-700' : 'text-slate-500'" x-text="slideEditing.status === 'active' ? 'Active' : 'Inactive'"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        </template>

        {{-- Modal Footer --}}
        <div class="sticky bottom-0 z-20 flex items-center justify-end gap-2 border-t border-slate-200/80 bg-white px-5 py-3 sm:px-6">
            <button
                type="button"
                @click="cancelSlideEdit()"
                :disabled="slideSaving"
                class="rounded-[6px] border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition disabled:opacity-50"
            >
                Cancel
            </button>
            <button
                type="button"
                @click="saveSlide()"
                :disabled="slideSaving"
                class="inline-flex items-center gap-1.5 rounded-[6px] bg-brand-green-500 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-600 active:scale-[0.99] transition disabled:opacity-60"
            >
                <span x-show="slideSaving" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <span x-text="slideSaving ? 'Saving…' : (slideIsNew ? 'Create Slide' : 'Save Changes')"></span>
            </button>
        </div>
    </div>
</div>

{{-- Delete Confirmation Dialog --}}
<div
    x-show="deleteSlideTarget"
    x-cloak
    class="fixed inset-0 z-[301] flex items-center justify-center bg-black/50 backdrop-blur-xs p-4"
    @keydown.escape.window="deleteSlideTarget = null"
>
    <div
        class="w-full max-w-sm rounded-[10px] bg-white p-5 shadow-2xl border border-slate-200"
        @click.outside="deleteSlideTarget = null"
    >
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 border border-red-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Delete Hero Slide?</h3>
                <p class="text-xs text-slate-500">This action will remove the slide from the carousel.</p>
            </div>
        </div>

        <div class="mt-4 rounded-[6px] bg-slate-50 p-3 border border-slate-100" x-show="deleteSlideTarget">
            <p class="text-xs font-semibold text-slate-800 truncate" x-text="deleteSlideTarget?.title || deleteSlideTarget?.badge || 'Untitled Slide'"></p>
            <p class="text-[11px] text-slate-500 truncate" x-text="deleteSlideTarget?.subtitle || 'No subtitle'"></p>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button
                type="button"
                @click="deleteSlideTarget = null"
                class="rounded-[6px] border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
            >
                Cancel
            </button>
            <button
                type="button"
                @click="confirmDeleteSlide()"
                class="rounded-[6px] bg-red-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition"
            >
                Delete Slide
            </button>
        </div>
    </div>
</div>
