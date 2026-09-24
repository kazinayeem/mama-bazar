{{-- Layout tab: drag-reorder sections (matches React HomepageLayoutBuilder) --}}
<div class="space-y-3 pt-4">
    <p class="text-sm text-slate-500">Drag to reorder, toggle to show or hide. Empty sections are automatically hidden on the storefront.</p>

    <div x-show="knownSections().length === 0" class="pt-2">
        <x-admin.empty-state
            title="No sections configured"
            description="Reset to defaults to restore the full homepage layout, then publish."
        />
    </div>

    <div x-ref="sectionList" class="space-y-2">
        <template x-for="(section, index) in knownSections()" :key="section.id">
            <div class="admin-surface" :data-section-id="section.id">
                <div class="flex flex-wrap items-center gap-3 p-3">
                    <button type="button" class="drag-handle cursor-grab touch-none text-slate-400 hover:text-slate-600 active:cursor-grabbing" aria-label="Drag to reorder">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 9h8M8 15h8"/></svg>
                    </button>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[8px] bg-brand-green-50 text-[10px] font-bold uppercase text-brand-green-700" x-text="(sectionMeta[section.type]?.label || section.type).slice(0,2)"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-semibold text-slate-900" x-text="(section.title || '').trim() || sectionMeta[section.type]?.label || section.type"></p>
                            <span class="hidden rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-500 sm:inline" x-text="'#' + (index + 1)"></span>
                        </div>
                        <p class="truncate text-xs text-slate-500" x-text="sectionMeta[section.type]?.description || ''"></p>
                    </div>
                    <span x-show="!section.enabled" class="hidden text-xs text-slate-400 sm:inline">Hidden</span>
                    <label class="relative inline-flex cursor-pointer items-center" title="Enable section">
                        <input type="checkbox" class="peer sr-only" :checked="section.enabled" @change="updateSection(section.id, { enabled: $event.target.checked })">
                        <span class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-green-500 peer-checked:after:translate-x-full"></span>
                    </label>
                    <button x-show="section.type === 'category_products'" type="button" @click="removeSection(section.id)" class="rounded-lg p-2 text-red-600 hover:bg-red-50" title="Remove section">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button type="button" @click="toggleSectionOpen(section.id)" class="rounded-lg p-2 hover:bg-slate-50" :aria-expanded="!!openSections[section.id]">
                        <svg class="h-4 w-4 transition" :class="openSections[section.id] && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                <div x-show="openSections[section.id]" class="border-t border-slate-100 px-4 pb-4 pt-3">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div x-show="section.type === 'category_products'">
                            <label class="text-xs font-bold text-slate-600">Category</label>
                            <select class="mt-1 h-9 w-full admin-control px-2 text-xs" :value="section.categoryId ? String(section.categoryId) : 'none'" @change="onCategoryPick(section.id, $event.target.value)">
                                <option value="none">Select a category…</option>
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="String(cat.id)" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Title</label>
                            <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" :placeholder="sectionMeta[section.type]?.label || ''" :value="section.title || ''" @input="updateSection(section.id, { title: $event.target.value })">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Subtitle</label>
                            <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" placeholder="Optional" :value="section.subtitle || ''" @input="updateSection(section.id, { subtitle: $event.target.value })">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Eyebrow label</label>
                            <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" placeholder="Optional" :value="section.eyebrow || ''" @input="updateSection(section.id, { eyebrow: $event.target.value })">
                        </div>
                        <div x-show="sectionCanLimit(section.type)">
                            <label class="text-xs font-bold text-slate-600">Max items</label>
                            <input type="number" min="1" max="24" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" :value="section.limit || 12" @input="updateSection(section.id, { limit: clampLimit($event.target.value) })">
                        </div>
                        <div x-show="section.type === 'categories'">
                            <label class="text-xs font-bold text-slate-600">Columns</label>
                            <select class="mt-1 h-9 w-full admin-control px-2 text-xs" :value="String(section.columns || 4)" @change="updateSection(section.id, { columns: Number($event.target.value) })">
                                <option value="4">4 columns</option>
                                <option value="5">5 columns</option>
                                <option value="6">6 columns</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Background</label>
                            <select class="mt-1 h-9 w-full admin-control px-2 text-xs" :value="section.background || 'default'" @change="updateSection(section.id, { background: $event.target.value })">
                                <option value="default">White</option>
                                <option value="muted">Soft grey</option>
                                <option value="dark">Dark</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">CTA text</label>
                            <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" placeholder="View all" :value="section.ctaText || ''" @input="updateSection(section.id, { ctaText: $event.target.value })">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">CTA link</label>
                            <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" placeholder="/shop" :value="section.ctaUrl || ''" @input="updateSection(section.id, { ctaUrl: $event.target.value })">
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <template x-for="section in unknownSections()" :key="section.id">
        <div class="rounded-[8px] border border-dashed border-amber-300 bg-amber-50/50 p-3 text-xs text-amber-900">
            Unknown section type: <span class="font-bold" x-text="section.type"></span> (<span x-text="section.id"></span>)
        </div>
    </template>

    <button type="button" @click="addCategoryProducts()" class="flex w-full items-center justify-center gap-2 rounded-[8px] border border-dashed border-slate-300 bg-white py-3 text-sm font-medium text-slate-700 hover:border-brand-green-400 hover:text-brand-green-700">
        + Add Category Products section
    </button>
</div>
