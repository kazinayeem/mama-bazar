{{-- Content tab (matches React HomepageContentSettings) --}}
<div class="grid gap-4 pt-4">
    <div class="admin-surface p-4">
        <h3 class="text-base font-bold text-slate-900">Announcement Bar</h3>
        <div class="mt-3 space-y-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" class="rounded text-brand-green-600" :checked="config.announcement.enabled" @change="config.announcement.enabled = $event.target.checked">
                Show announcement bar on top of the site
            </label>
            <input type="text" class="w-full admin-control" placeholder="e.g. Free delivery on orders over ৳2,000" x-model="config.announcement.text">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-slate-600">Background color</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="color" class="h-8 w-10 cursor-pointer rounded border" x-model="config.announcement.backgroundColor">
                        <input type="text" class="h-8 flex-1 admin-control px-2 text-xs" x-model="config.announcement.backgroundColor">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600">Text color</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="color" class="h-8 w-10 cursor-pointer rounded border" x-model="config.announcement.textColor">
                        <input type="text" class="h-8 flex-1 admin-control px-2 text-xs" x-model="config.announcement.textColor">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-surface p-4">
        <h3 class="text-base font-bold text-slate-900">Trust Strip</h3>
        <p class="text-xs text-slate-500">Icon perks shown right below the hero.</p>
        <div class="mt-3 space-y-2">
            <template x-for="(item, idx) in config.trustStrip" :key="'trust-' + idx">
                <div class="flex flex-col gap-2 rounded-md border border-slate-200 p-3 sm:flex-row">
                    <div class="w-full sm:w-36">
                        <label class="text-xs font-bold text-slate-600">Icon</label>
                        <select class="mt-1 h-9 w-full admin-control px-2 text-xs" :value="item.icon || 'none'" @change="setContentItem('trustStrip', idx, { icon: $event.target.value === 'none' ? undefined : $event.target.value })">
                            <option value="none">No icon</option>
                            <template x-for="name in iconOptions" :key="name"><option :value="name" x-text="name"></option></template>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Title</label>
                        <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" placeholder="e.g. Fast delivery" :value="item.title" @input="setContentItem('trustStrip', idx, { title: $event.target.value })">
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Text</label>
                        <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" placeholder="Supporting text" :value="item.text || ''" @input="setContentItem('trustStrip', idx, { text: $event.target.value })">
                    </div>
                    <button type="button" @click="removeContentItem('trustStrip', idx)" class="self-end rounded-lg p-2 text-red-600 hover:bg-red-50">×</button>
                </div>
            </template>
        </div>
        <button type="button" @click="addContentItem('trustStrip')" class="mt-2 rounded-[6px] border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">+ Add item</button>
    </div>

    <div class="admin-surface p-4">
        <h3 class="text-base font-bold text-slate-900">Why Choose Us</h3>
        <p class="text-xs text-slate-500">Value proposition cards near the bottom of the homepage.</p>
        <div class="mt-3 space-y-2">
            <template x-for="(item, idx) in config.whyChooseUs" :key="'why-' + idx">
                <div class="flex flex-col gap-2 rounded-md border border-slate-200 p-3 sm:flex-row">
                    <div class="w-full sm:w-36">
                        <label class="text-xs font-bold text-slate-600">Icon</label>
                        <select class="mt-1 h-9 w-full admin-control px-2 text-xs" :value="item.icon || 'none'" @change="setContentItem('whyChooseUs', idx, { icon: $event.target.value === 'none' ? undefined : $event.target.value })">
                            <option value="none">No icon</option>
                            <template x-for="name in iconOptions" :key="name"><option :value="name" x-text="name"></option></template>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Title</label>
                        <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" placeholder="e.g. Authentic products" :value="item.title" @input="setContentItem('whyChooseUs', idx, { title: $event.target.value })">
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Text</label>
                        <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" :value="item.text || ''" @input="setContentItem('whyChooseUs', idx, { text: $event.target.value })">
                    </div>
                    <button type="button" @click="removeContentItem('whyChooseUs', idx)" class="self-end rounded-lg p-2 text-red-600 hover:bg-red-50">×</button>
                </div>
            </template>
        </div>
        <button type="button" @click="addContentItem('whyChooseUs')" class="mt-2 rounded-[6px] border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">+ Add item</button>
    </div>

    <div class="admin-surface p-4">
        <h3 class="text-base font-bold text-slate-900">Flash Sale Window</h3>
        <p class="text-xs text-slate-500">Optional date range that powers the Flash Deals countdown. Leave empty for a daily sale ending at midnight.</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            <label class="flex items-center gap-2 text-sm sm:col-span-3">
                <input type="checkbox" class="rounded text-brand-green-600" :checked="config.flashSaleWindow.enabled" @change="config.flashSaleWindow.enabled = $event.target.checked">
                Enable scheduled flash sale window
            </label>
            <div>
                <label class="text-xs font-bold text-slate-600">Starts</label>
                <input type="datetime-local" class="mt-1 h-9 w-full admin-control px-2 text-xs" :value="flashStartLocal()" @input="config.flashSaleWindow.start = $event.target.value ? $event.target.value : null">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-600">Ends</label>
                <input type="datetime-local" class="mt-1 h-9 w-full admin-control px-2 text-xs" :value="flashEndLocal()" @input="config.flashSaleWindow.end = $event.target.value ? $event.target.value : null">
            </div>
        </div>
    </div>

    <div class="admin-surface p-4">
        <h3 class="text-base font-bold text-slate-900">Newsletter Section</h3>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="checkbox" class="rounded text-brand-green-600" :checked="config.newsletter.enabled" @change="config.newsletter.enabled = $event.target.checked">
                Show newsletter signup block
            </label>
            <div>
                <label class="text-xs font-bold text-slate-600">Title</label>
                <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" x-model="config.newsletter.title">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-600">Button text</label>
                <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" x-model="config.newsletter.buttonText">
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-bold text-slate-600">Subtitle</label>
                <input type="text" class="mt-1 h-9 w-full admin-control px-2.5 text-xs" x-model="config.newsletter.subtitle">
            </div>
        </div>
    </div>

    <div class="admin-surface p-4">
        <h3 class="text-base font-bold text-slate-900">Popular Searches</h3>
        <p class="text-xs text-slate-500">Suggested chips shown under the hero and in search.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <template x-for="term in config.popularSearches" :key="term">
                <span class="inline-flex items-center gap-1.5 rounded-full border bg-slate-100 px-3 py-1 text-xs font-semibold">
                    <span x-text="term"></span>
                    <button type="button" @click="removePopularSearch(term)" class="text-slate-500 hover:text-slate-800">×</button>
                </span>
            </template>
        </div>
        <button type="button" @click="addPopularSearch()" class="mt-3 rounded-[6px] border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">+ Add search term</button>
    </div>
</div>
