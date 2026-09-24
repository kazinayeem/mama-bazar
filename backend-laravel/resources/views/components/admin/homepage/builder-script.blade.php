<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
/**
 * Global factory (same pattern as adminShell) — do NOT use Alpine.data + alpine:init here.
 * @stack('scripts') can race Alpine.start(); a window function is always available for x-data.
 */
window.homepageBuilder = function homepageBuilder(opts) {
    opts = opts || {};
    return {
        config: structuredClone(opts.initialConfig || { sections: [], heroSlides: [] }),
        categories: opts.categories || [],
        subscribers: opts.subscribers || [],
        sectionMeta: opts.sectionMeta || {},
        sectionOrder: opts.sectionOrder || [],
        iconOptions: opts.iconOptions || [],
        saveUrl: opts.saveUrl,
        resetUrl: opts.resetUrl,
        mediaListUrl: opts.mediaListUrl || (window.__homepageMediaPicker && window.__homepageMediaPicker.listUrl),
        mediaUploadUrl: opts.mediaUploadUrl || (window.__homepageMediaPicker && window.__homepageMediaPicker.uploadUrl),
        csrf: opts.csrf,
        tab: 'layout',
        dirty: false,
        saving: false,
        publishedBaseline: '',
        resetOpen: false,
        openSections: {},
        sortable: null,
        slideEditing: null,
        slideIsNew: false,
        deleteSlideTarget: null,
        // Media picker
        pickerOpen: false,
        pickerTarget: null,
        pickerAssets: [],
        pickerFolders: [],
        pickerFolder: 'all',
        pickerSearch: '',
        pickerSelected: null,
        pickerLoading: false,
        pickerUploading: false,

        init() {
            this.publishedBaseline = JSON.stringify(this.config);
            this.$watch('config', () => this.checkDirty(), { deep: true });
            this.$watch('tab', (v) => {
                if (v === 'layout') this.$nextTick(() => this.initSortable());
            });
            this.$watch('pickerFolder', () => { if (this.pickerOpen) this.loadPickerAssets(); });
            this.$watch('pickerSearch', () => { if (this.pickerOpen) this.loadPickerAssets(); });
            this.$nextTick(() => this.initSortable());
        },

        checkDirty() {
            this.dirty = JSON.stringify(this.config) !== this.publishedBaseline;
        },

        setTab(name) {
            this.tab = name;
        },

        isKnownType(type) {
            return this.sectionOrder.includes(type);
        },

        knownSections() {
            return this.config.sections.filter((s) => this.isKnownType(s.type));
        },

        unknownSections() {
            return this.config.sections.filter((s) => !this.isKnownType(s.type));
        },

        sectionCanLimit(type) {
            return !['hero', 'trust_strip', 'promo_banner', 'why_choose_us', 'newsletter'].includes(type);
        },

        updateSection(id, patch) {
            this.config.sections = this.config.sections.map((s) => (s.id === id ? { ...s, ...patch } : s));
        },

        toggleSectionOpen(id) {
            this.openSections[id] = !this.openSections[id];
        },

        onCategoryPick(sectionId, value) {
            if (value === 'none') {
                this.updateSection(sectionId, { categoryId: null, categorySlug: null });
                return;
            }
            const cat = this.categories.find((c) => String(c.id) === value);
            if (!cat) return;
            const section = this.config.sections.find((s) => s.id === sectionId);
            const patch = { categoryId: cat.id, categorySlug: cat.slug };
            if (section && !(section.title || '').trim()) patch.title = cat.name;
            this.updateSection(sectionId, patch);
        },

        clampLimit(v) {
            const n = Number(v) || 12;
            return Math.max(1, Math.min(24, n));
        },

        addCategoryProducts() {
            const id = 'category_products_' + Date.now();
            this.config.sections = [...this.config.sections, { id, type: 'category_products', enabled: true, title: '', limit: 6 }];
        },

        removeSection(id) {
            this.config.sections = this.config.sections.filter((s) => s.id !== id);
        },

        initSortable() {
            const el = this.$refs.sectionList;
            if (!el || typeof Sortable === 'undefined') return;
            if (this.sortable) {
                this.sortable.destroy();
                this.sortable = null;
            }
            this.sortable = Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                draggable: '[data-section-id]',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;
                    const known = this.knownSections();
                    const [moved] = known.splice(evt.oldIndex, 1);
                    known.splice(evt.newIndex, 0, moved);
                    const unknown = this.unknownSections();
                    this.config.sections = [...known, ...unknown];
                },
            });
        },

        publish() {
            if (!this.dirty || this.saving) return;
            this.saving = true;
            this.$refs.publishForm.submit();
        },

        async confirmReset() {
            this.resetOpen = false;
            try {
                const res = await fetch(this.resetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                });
                if (!res.ok) throw new Error('Reset failed');
                const cfg = await res.json();
                this.config = cfg;
                this.dirty = true;
                window.dispatchEvent(new CustomEvent('admin-toast', { detail: { message: 'Default layout restored — press Publish Changes to apply' } }));
            } catch (e) {
                alert(e.message || 'Reset failed');
            }
        },

        slideUid() {
            return 'slide-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
        },

        blankSlide() {
            return {
                id: this.slideUid(),
                desktopImage: '',
                status: 'active',
                priority: 0,
                alignment: 'left',
                overlay: true,
                overlayOpacity: 0.55,
                backgroundColor: '#0f172a',
            };
        },

        openSlideCreate() {
            this.slideIsNew = true;
            this.slideEditing = this.blankSlide();
        },

        openSlideEdit(slide) {
            this.slideIsNew = false;
            this.slideEditing = structuredClone(slide);
        },

        isValidButtonUrl(url) {
            if (!url || !String(url).trim()) return true;
            const value = String(url).trim();
            if (value.startsWith('/') || value.startsWith('#')) return true;
            try {
                const parsed = new URL(value);
                return ['http:', 'https:', 'tel:', 'mailto:'].includes(parsed.protocol);
            } catch {
                return false;
            }
        },

        saveSlide() {
            if (!this.slideEditing) return;
            if (!this.slideEditing.desktopImage) {
                alert('A desktop image is required');
                return;
            }
            if ((this.slideEditing.title || '').length > 120) {
                alert('Title must be 120 characters or fewer');
                return;
            }
            if (!this.isValidButtonUrl(this.slideEditing.primaryButtonUrl)) {
                alert('Primary button link must be a valid URL or internal path');
                return;
            }
            if (!this.isValidButtonUrl(this.slideEditing.secondaryButtonUrl)) {
                alert('Secondary button link must be a valid URL or internal path');
                return;
            }
            if (this.slideIsNew) {
                const slide = { ...this.slideEditing, priority: this.config.heroSlides.length + 1 };
                this.config.heroSlides = [...this.config.heroSlides, slide];
            } else {
                this.config.heroSlides = this.config.heroSlides.map((s) => (s.id === this.slideEditing.id ? { ...this.slideEditing } : s));
            }
            this.slideEditing = null;
        },

        updateSlideField(id, patch) {
            this.config.heroSlides = this.config.heroSlides.map((s) => (s.id === id ? { ...s, ...patch } : s));
        },

        moveSlide(index, dir) {
            const target = index + dir;
            if (target < 0 || target >= this.config.heroSlides.length) return;
            const next = [...this.config.heroSlides];
            const [item] = next.splice(index, 1);
            next.splice(target, 0, item);
            this.config.heroSlides = next.map((s, i) => ({ ...s, priority: next.length - i }));
        },

        duplicateSlide(slide) {
            const copy = {
                ...structuredClone(slide),
                id: this.slideUid(),
                title: slide.title ? slide.title + ' (Copy)' : undefined,
                priority: this.config.heroSlides.length + 1,
            };
            this.config.heroSlides = [...this.config.heroSlides, copy];
        },

        confirmDeleteSlide() {
            if (!this.deleteSlideTarget) return;
            this.config.heroSlides = this.config.heroSlides.filter((s) => s.id !== this.deleteSlideTarget.id);
            this.deleteSlideTarget = null;
        },

        // —— Media picker ——
        openMediaPicker(field) {
            this.pickerTarget = field;
            this.pickerSelected = null;
            this.pickerFolder = 'all';
            this.pickerSearch = '';
            this.pickerOpen = true;
            this.loadPickerAssets();
        },

        async loadPickerAssets() {
            const listUrl = this.mediaListUrl || (window.__homepageMediaPicker && window.__homepageMediaPicker.listUrl);
            if (!listUrl) return;
            this.pickerLoading = true;
            try {
                const params = new URLSearchParams({ limit: '30' });
                if (this.pickerFolder && this.pickerFolder !== 'all') params.set('folder', this.pickerFolder);
                if (this.pickerSearch) params.set('search', this.pickerSearch);
                const res = await fetch(listUrl + '?' + params.toString(), {
                    headers: { Accept: 'application/json' },
                });
                const json = await res.json();
                this.pickerAssets = json.data || [];
                if (Array.isArray(json.folders)) this.pickerFolders = json.folders;
            } catch (e) {
                this.pickerAssets = [];
            } finally {
                this.pickerLoading = false;
            }
        },

        selectPickerAsset(asset) {
            this.pickerSelected = asset;
        },

        confirmPicker() {
            if (!this.pickerSelected || !this.pickerTarget || !this.slideEditing) return;
            this.slideEditing[this.pickerTarget] = this.pickerSelected.url;
            this.pickerOpen = false;
            this.pickerTarget = null;
            this.pickerSelected = null;
        },

        async uploadPickerFile(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) return;
            const uploadUrl = this.mediaUploadUrl || (window.__homepageMediaPicker && window.__homepageMediaPicker.uploadUrl);
            if (!uploadUrl) return;
            this.pickerUploading = true;
            try {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('folder', 'banners');
                const res = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: fd,
                });
                if (!res.ok) throw new Error('Upload failed');
                const json = await res.json();
                const asset = json.data;
                if (asset) {
                    this.pickerAssets = [asset, ...this.pickerAssets];
                    this.pickerSelected = asset;
                }
            } catch (e) {
                alert(e.message || 'Upload failed');
            } finally {
                this.pickerUploading = false;
            }
        },

        setContentItem(key, idx, patch) {
            this.config[key] = this.config[key].map((it, i) => (i === idx ? { ...it, ...patch } : it));
        },

        addContentItem(key) {
            const icon = this.iconOptions[0] || undefined;
            this.config[key] = [...(this.config[key] || []), { icon, title: '', text: '' }];
        },

        removeContentItem(key, idx) {
            this.config[key] = this.config[key].filter((_, i) => i !== idx);
        },

        flashStartLocal() {
            const s = this.config.flashSaleWindow?.start;
            return s ? String(s).slice(0, 16) : '';
        },

        flashEndLocal() {
            const s = this.config.flashSaleWindow?.end;
            return s ? String(s).slice(0, 16) : '';
        },

        addPopularSearch() {
            const term = window.prompt('Enter a popular search term');
            if (!term || !term.trim()) return;
            const t = term.trim();
            if (this.config.popularSearches.includes(t)) return;
            this.config.popularSearches = [...this.config.popularSearches, t].slice(0, 12);
        },

        removePopularSearch(term) {
            this.config.popularSearches = this.config.popularSearches.filter((t) => t !== term);
        },

        formatDate(iso) {
            if (!iso) return '—';
            try {
                return new Date(iso).toLocaleDateString();
            } catch {
                return iso;
            }
        },
    };
};
</script>
