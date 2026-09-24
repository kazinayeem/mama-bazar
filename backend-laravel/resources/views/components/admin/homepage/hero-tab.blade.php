{{-- Hero Slides tab (matches React HeroSlidesManager) --}}
<div class="space-y-3 pt-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500"><span x-text="config.heroSlides.length"></span> slides · reorder via arrows · first slide shows first</p>
        <button type="button" @click="openSlideCreate()" class="inline-flex h-10 items-center rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white hover:bg-brand-green-600">+ Add Slide</button>
    </div>

    <div x-show="config.heroSlides.length === 0">
        <x-admin.empty-state
            title="No slides yet"
            description="Add your first hero slide — or leave empty to hide the carousel on the storefront."
        />
    </div>

    <div class="space-y-2">
        <template x-for="(slide, index) in config.heroSlides" :key="slide.id">
            <div class="admin-surface p-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-1">
                        <button type="button" :disabled="index === 0" @click="moveSlide(index, -1)" class="rounded-lg p-2 hover:bg-slate-50 disabled:opacity-30" aria-label="Move up">↑</button>
                        <button type="button" :disabled="index === config.heroSlides.length - 1" @click="moveSlide(index, 1)" class="rounded-lg p-2 hover:bg-slate-50 disabled:opacity-30" aria-label="Move down">↓</button>
                    </div>
                    <div class="relative h-16 w-28 shrink-0 overflow-hidden rounded-md border bg-slate-100 sm:h-14 sm:w-40">
                        <img x-show="slide.desktopImage" :src="slide.desktopImage" alt="" class="h-full w-full object-cover">
                        <div x-show="!slide.desktopImage" class="flex h-full items-center justify-center text-xs text-slate-400">No image</div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-semibold text-slate-900" x-text="(slide.title || '').trim() || (slide.badge || '').trim() || ('Slide ' + (index + 1))"></p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="slide.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" x-text="slide.status"></span>
                        </div>
                        <p class="truncate text-xs text-slate-500" x-text="slide.subtitle || slide.description || (slide.primaryButtonText ? ('CTA: ' + slide.primaryButtonText) : 'No caption')"></p>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <input type="checkbox" class="rounded text-brand-green-600" :checked="slide.status === 'active'" @change="updateSlideField(slide.id, { status: $event.target.checked ? 'active' : 'inactive' })">
                        Live
                    </label>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="duplicateSlide(slide)" class="rounded-lg px-2 py-1.5 text-xs font-semibold hover:bg-slate-50" title="Duplicate">Copy</button>
                        <button type="button" @click="openSlideEdit(slide)" class="rounded-lg px-2 py-1.5 text-xs font-semibold hover:bg-slate-50" title="Edit">Edit</button>
                        <button type="button" @click="deleteSlideTarget = slide" class="rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50" title="Delete">Delete</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- Slide editor dialog --}}
<div x-show="slideEditing" x-cloak class="fixed inset-0 z-[300] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" @keydown.escape.window="slideEditing = null">
    <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-t-[10px] bg-white p-5 shadow-xl sm:rounded-[10px] sm:p-6" @click.outside="slideEditing = null">
        <h3 class="text-lg font-bold text-slate-900" x-text="slideIsNew ? 'Add Hero Slide' : 'Edit Hero Slide'"></h3>
        <div class="mt-4 space-y-4">
            <div class="rounded-md border border-slate-200 p-3">
                <p class="mb-3 text-sm font-semibold">Responsive Images</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <template x-for="field in ['desktopImage','tabletImage','mobileImage']" :key="field">
                        <div>
                            <button
                                type="button"
                                @click="openMediaPicker(field)"
                                class="relative flex aspect-video w-full flex-col items-center justify-center gap-1 overflow-hidden rounded-md border-2 border-dashed text-slate-400 hover:border-brand-green-500 hover:text-brand-green-600"
                            >
                                <template x-if="slideEditing[field]">
                                    <div class="absolute inset-0">
                                        <img :src="slideEditing[field]" alt="" class="h-full w-full object-cover">
                                        <button type="button" class="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white" @click.stop="slideEditing[field] = field === 'desktopImage' ? '' : undefined">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!slideEditing[field]">
                                    <div class="px-2 text-center">
                                        <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span class="mt-1 block text-[10px]" x-text="field === 'desktopImage' ? 'Desktop (16:6)' : (field === 'tabletImage' ? 'Tablet' : 'Mobile (4:5)')"></span>
                                    </div>
                                </template>
                            </button>
                            <p class="mt-1 text-center text-[10px] font-semibold uppercase text-slate-500" x-text="field === 'desktopImage' ? 'Desktop' : (field === 'tabletImage' ? 'Tablet' : 'Mobile')"></p>
                        </div>
                    </template>
                </div>
                <p x-show="!slideEditing?.desktopImage" class="mt-2 text-xs text-red-600">Desktop image is required</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="text-xs font-bold">Badge</label><input type="text" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.badge" placeholder="e.g. New Season"></div>
                <div><label class="text-xs font-bold">Title</label><input type="text" maxlength="120" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.title" placeholder="Slide headline"></div>
                <div><label class="text-xs font-bold">Subtitle</label><input type="text" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.subtitle"></div>
                <div><label class="text-xs font-bold">Description</label><input type="text" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.description"></div>
                <div><label class="text-xs font-bold">Primary button text</label><input type="text" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.primaryButtonText" placeholder="Shop Now"></div>
                <div><label class="text-xs font-bold">Primary button link</label><input type="text" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.primaryButtonUrl" placeholder="/shop or https://…"></div>
                <div><label class="text-xs font-bold">Secondary button text</label><input type="text" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.secondaryButtonText"></div>
                <div><label class="text-xs font-bold">Secondary button link</label><input type="text" class="mt-1 w-full admin-control px-2.5 py-2 text-xs" x-model="slideEditing.secondaryButtonUrl"></div>
                <div>
                    <label class="text-xs font-bold">Text alignment</label>
                    <select class="mt-1 w-full admin-control px-2 py-2 text-xs" x-model="slideEditing.alignment">
                        <option value="left">Left</option>
                        <option value="center">Center</option>
                        <option value="right">Right</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold">Fallback background color</label>
                    <input type="color" class="mt-1 h-9 w-full rounded-[8px] border" x-model="slideEditing.backgroundColor">
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-6">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="rounded text-brand-green-600" :checked="slideEditing.overlay !== false" @change="slideEditing.overlay = $event.target.checked">
                    Dark overlay
                </label>
                <div x-show="slideEditing.overlay !== false">
                    <label class="text-xs font-bold">Overlay strength · <span x-text="Math.round((slideEditing.overlayOpacity ?? 0.55) * 100)"></span>%</label>
                    <input type="range" min="0" max="1" step="0.05" class="mt-1 block w-56" x-model.number="slideEditing.overlayOpacity">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="rounded text-brand-green-600" :checked="slideEditing.status === 'active'" @change="slideEditing.status = $event.target.checked ? 'active' : 'inactive'">
                    Active
                </label>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-4">
            <button type="button" @click="slideEditing = null" class="rounded-[6px] border border-slate-200 px-4 py-2 text-sm font-medium">Cancel</button>
            <button type="button" @click="saveSlide()" class="rounded-[6px] bg-brand-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-green-600" x-text="slideIsNew ? 'Create Slide' : 'Save Changes'"></button>
        </div>
    </div>
</div>

{{-- Delete confirm --}}
<div x-show="deleteSlideTarget" x-cloak class="fixed inset-0 z-[301] flex items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-[10px] bg-white p-6 shadow-xl">
        <h3 class="font-bold text-slate-900">Delete slide?</h3>
        <p class="mt-2 text-sm text-slate-500">This slide will be removed from the homepage carousel.</p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="deleteSlideTarget = null" class="rounded-[6px] border px-4 py-2 text-sm">Cancel</button>
            <button type="button" @click="confirmDeleteSlide()" class="rounded-[6px] bg-red-600 px-4 py-2 text-sm font-medium text-white">Delete</button>
        </div>
    </div>
</div>
