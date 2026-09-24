import Alpine from 'alpinejs';
import { registerRichEditor } from './product-rich-editor';

window.Alpine = Alpine;
registerRichEditor(Alpine);

// Cart Store + PDP variant engine (registered before Alpine.start)
document.addEventListener('alpine:init', () => {
    Alpine.store('cart', {
        items: JSON.parse(localStorage.getItem('mamabazar_cart') || '[]'),
        drawerOpen: false,

        init() {
            this.save();
        },

        resolvePrice(product, variant = null) {
            if (variant) {
                const disc = parseFloat(variant.discountPrice ?? variant.salePrice ?? 0);
                if (disc > 0) return disc;
                const vp = parseFloat(variant.price ?? 0);
                if (vp > 0) return vp;
            }
            if (product && product.effectivePrice != null && product.effectivePrice !== '') {
                return parseFloat(product.effectivePrice);
            }
            const sale = parseFloat(product?.salePrice ?? product?.sale_price ?? 0);
            if (sale > 0) return sale;
            const base = parseFloat(product?.price ?? 0);
            const discount = parseFloat(product?.discount ?? 0);
            if (discount > 0 && discount <= 100) {
                return Math.round(base - (base * discount) / 100);
            }
            return base;
        },

        addItem(product, variant = null, quantity = 1) {
            const qty = Math.max(1, parseInt(quantity, 10) || 1);
            const price = this.resolvePrice(product, variant);
            const image = (variant && (variant.image || variant.thumbnail || (variant.images && variant.images[0])))
                || (product.images && product.images[0])
                || product.image
                || '';
            const key = variant && variant.id ? `${product.id}-${variant.id}` : `${product.id}`;

            const existingIndex = this.items.findIndex(i => i.key === key);
            if (existingIndex > -1) {
                this.items[existingIndex].quantity += qty;
                this.items[existingIndex].price = price;
                if (image) this.items[existingIndex].image = image;
            } else {
                this.items.push({
                    key,
                    id: product.id,
                    variantId: variant && variant.id ? variant.id : null,
                    title: product.title,
                    price,
                    image,
                    slug: product.slug,
                    quantity: qty,
                    color: variant?.color || product.color || null,
                    size: variant?.size || product.size || null,
                    options: variant?.options || null,
                    sku: variant?.sku || product.sku || null,
                });
            }
            this.save();
            this.drawerOpen = true;
        },

        updateQuantity(key, quantity) {
            if (quantity <= 0) {
                this.removeItem(key);
            } else {
                const item = this.items.find(i => i.key === key);
                if (item) {
                    item.quantity = quantity;
                    this.save();
                }
            }
        },

        removeItem(key) {
            this.items = this.items.filter(i => i.key !== key);
            this.save();
        },

        clear() {
            this.items = [];
            this.save();
        },

        get count() {
            return this.items.reduce((total, item) => total + item.quantity, 0);
        },

        get subtotal() {
            return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
        },

        save() {
            localStorage.setItem('mamabazar_cart', JSON.stringify(this.items));
        },

        wishlist: JSON.parse(localStorage.getItem('mamabazar_wishlist') || '[]'),
        hasWishlist(id) {
            return this.wishlist.includes(id);
        },
        toggleWishlist(id) {
            if (this.hasWishlist(id)) {
                this.wishlist = this.wishlist.filter(itemId => itemId !== id);
            } else {
                this.wishlist.push(id);
            }
            localStorage.setItem('mamabazar_wishlist', JSON.stringify(this.wishlist));
        },

        compare: JSON.parse(localStorage.getItem('mamabazar_compare') || '[]'),
        hasCompare(id) {
            return this.compare.includes(id);
        },
        toggleCompare(id) {
            if (this.hasCompare(id)) {
                this.compare = this.compare.filter(itemId => itemId !== id);
            } else {
                this.compare.push(id);
            }
            localStorage.setItem('mamabazar_compare', JSON.stringify(this.compare));
        },
    });

    // Generic PDP variant engine — axes from variant.options (Color/Size/Storage/RAM/Strap/…)
    Alpine.data('productDetail', (payload) => ({
        product: payload,
        optionGroups: payload.optionGroups || [],
        variants: (payload.variants || []).filter(v => v.status !== 'inactive'),
        selected: {},
        qty: Math.max(1, payload.minOrder || 1),
        activeImageIndex: 0,
        galleryOpen: false,
        variantImage: null,
        checkoutUrl: payload.checkoutUrl || '/checkout',

        init() {
            const initial = {};
            for (const group of this.optionGroups) {
                this.selected = { ...initial };
                const first = (group.values || []).find(v => this.isOptionAvailable(group.key, v.name));
                if (first) initial[group.key] = first.name;
            }
            this.selected = initial;
            this.syncVariantImage();
            this.$watch('selected', () => {
                this.syncVariantImage();
                if (this.qty > this.maxQty) this.qty = this.maxQty;
                if (this.qty < this.minQty) this.qty = this.minQty;
            });
        },

        get galleryImages() {
            return (this.product.images && this.product.images.length)
                ? this.product.images
                : ['/brandlogo.png'];
        },

        get currentImage() {
            return this.variantImage
                || this.galleryImages[this.activeImageIndex]
                || this.galleryImages[0]
                || '/brandlogo.png';
        },

        get needsOptions() {
            return this.optionGroups.length > 0;
        },

        get allOptionsSelected() {
            if (!this.needsOptions) return true;
            return this.optionGroups.every(g => !!this.selected[g.key]);
        },

        get activeVariant() {
            if (!this.variants.length) return null;
            if (this.needsOptions && !this.allOptionsSelected) return null;
            return this.variants.find(v => {
                if (v.availability === false) return false;
                const opts = v.options || {};
                return this.optionGroups.every(g => {
                    const wanted = this.selected[g.key];
                    if (!wanted) return true;
                    return this.optionValueMatches(opts, g.key, wanted);
                });
            }) || null;
        },

        get finalPrice() {
            const v = this.activeVariant;
            if (v) {
                const disc = parseFloat(v.discountPrice || v.salePrice || 0);
                if (disc > 0) return disc;
                const vp = parseFloat(v.price || 0);
                if (vp > 0) return vp;
            }
            const sale = parseFloat(this.product.salePrice || 0);
            if (sale > 0) return sale;
            const base = parseFloat(this.product.price || 0);
            const d = parseFloat(this.product.discount || 0);
            if (d > 0 && d <= 100) return Math.round(base - (base * d) / 100);
            return base;
        },

        get displayPrice() {
            const v = this.activeVariant;
            if (v) {
                const hasReg = !!parseFloat(v.price || 0);
                const hasDisc = !!parseFloat(v.discountPrice || v.salePrice || 0);
                return (hasReg && hasDisc) ? parseFloat(v.price) : null;
            }
            const sale = parseFloat(this.product.salePrice || 0);
            const base = parseFloat(this.product.price || 0);
            const d = parseFloat(this.product.discount || 0);
            if (sale > 0 && sale < base) return base;
            if (d > 0 && d <= 100) return base;
            return null;
        },

        get showOriginalPrice() {
            return this.displayPrice != null && this.displayPrice > this.finalPrice;
        },

        get discountPercent() {
            if (this.activeVariant) {
                const price = parseFloat(this.activeVariant.price || 0);
                const disc = parseFloat(this.activeVariant.discountPrice || this.activeVariant.salePrice || 0);
                if (!price || !disc || disc >= price) return 0;
                return Math.round(((price - disc) / price) * 100);
            }
            const sale = parseFloat(this.product.salePrice || 0);
            const base = parseFloat(this.product.price || 0);
            if (sale > 0 && base > 0 && sale < base) return Math.round(((base - sale) / base) * 100);
            const d = parseFloat(this.product.discount || 0);
            return d > 0 && d <= 100 ? Math.round(d) : 0;
        },

        get showDiscount() {
            return this.discountPercent > 0;
        },

        get showSelectOptionsPrice() {
            if (!this.needsOptions || this.allOptionsSelected) return false;
            const prices = this.variants
                .filter(v => v.availability !== false)
                .map(v => {
                    const disc = parseFloat(v.discountPrice || v.salePrice || 0);
                    if (disc > 0) return disc;
                    const vp = parseFloat(v.price || 0);
                    return vp > 0 ? vp : parseFloat(this.product.price || 0);
                });
            return [...new Set(prices)].length > 1;
        },

        get effectiveStock() {
            if (this.activeVariant) return parseInt(this.activeVariant.stock || 0, 10);
            if (this.variants.length) {
                return this.variants
                    .filter(v => v.availability !== false)
                    .reduce((s, v) => s + parseInt(v.stock || 0, 10), 0);
            }
            return parseInt(this.product.stock || 0, 10);
        },

        get variantOutOfStock() {
            if (!this.activeVariant || this.product.unlimitedStock) return false;
            return parseInt(this.activeVariant.stock || 0, 10) <= 0;
        },

        get outOfStock() {
            if (this.product.unlimitedStock) return false;
            return this.effectiveStock <= 0;
        },

        get minQty() {
            return Math.max(1, parseInt(this.product.minOrder || 1, 10));
        },

        get maxQty() {
            if (this.product.maxOrder) return parseInt(this.product.maxOrder, 10);
            if (this.product.unlimitedStock) return 99;
            if (this.activeVariant) return Math.max(1, parseInt(this.activeVariant.stock || 1, 10));
            return Math.max(1, parseInt(this.product.stock || 1, 10));
        },

        get stockLabel() {
            if (this.product.unlimitedStock) return { text: 'In Stock', className: 'bg-emerald-50 text-emerald-700' };
            const stock = this.effectiveStock;
            if (stock <= 0) {
                if (this.product.backorder) return { text: 'Available on Backorder', className: 'bg-amber-100 text-amber-700' };
                return { text: 'Out of Stock', className: 'bg-slate-100 text-slate-500' };
            }
            const alert = this.product.lowStockAlert || 5;
            if (stock <= alert) return { text: `Only ${stock} left`, className: 'bg-brand-orange-50 text-brand-orange-700' };
            return { text: 'In Stock', className: 'bg-emerald-50 text-emerald-700' };
        },

        get disableAddToCart() {
            return this.outOfStock || this.variantOutOfStock || (this.needsOptions && !this.allOptionsSelected);
        },

        optionValueMatches(opts, key, wanted) {
            if (!opts || !wanted) return false;
            const lowerWanted = String(wanted).toLowerCase();
            for (const [k, val] of Object.entries(opts)) {
                if (String(k).toLowerCase() === String(key).toLowerCase()
                    && String(val).toLowerCase() === lowerWanted) {
                    return true;
                }
            }
            return false;
        },

        findVariantsMatching(partial) {
            return this.variants.filter(v => {
                if (v.availability === false) return false;
                const opts = v.options || {};
                return Object.entries(partial).every(([key, val]) => {
                    if (!val) return true;
                    return this.optionValueMatches(opts, key, val);
                });
            });
        },

        isOptionAvailable(groupKey, value) {
            const probe = { ...this.selected, [groupKey]: value };
            const constrained = {};
            for (const [k, v] of Object.entries(probe)) {
                if (v) constrained[k] = v;
            }
            return this.findVariantsMatching(constrained).length > 0;
        },

        isOptionOos(groupKey, value) {
            const probe = { ...this.selected, [groupKey]: value };
            const matches = this.findVariantsMatching(probe);
            if (!matches.length) return false;
            const allKeys = this.optionGroups.map(g => g.key);
            if (!allKeys.every(k => probe[k])) return false;
            return matches.every(v => parseInt(v.stock || 0, 10) <= 0);
        },

        selectOption(groupKey, value) {
            if (!this.isOptionAvailable(groupKey, value)) return;
            const next = { ...this.selected, [groupKey]: value };
            for (const group of this.optionGroups) {
                if (group.key === groupKey) continue;
                const current = next[group.key];
                if (current && !this.findVariantsMatching({ ...next, [group.key]: current }).length) {
                    const fallback = (group.values || []).find(v =>
                        this.findVariantsMatching({ ...next, [group.key]: v.name }).length > 0
                    );
                    if (fallback) next[group.key] = fallback.name;
                    else delete next[group.key];
                }
            }
            this.selected = next;
            this.syncVariantImage();
        },

        selectGalleryImage(idx) {
            this.activeImageIndex = idx;
            this.variantImage = null;
        },

        normalizeMediaUrl(url) {
            if (!url) return null;
            const u = String(url).trim();
            if (!u) return null;
            if (
                u.startsWith('http://') ||
                u.startsWith('https://') ||
                u.startsWith('/storage/') ||
                u.startsWith('/uploads/') ||
                u.startsWith('/brand') ||
                u.startsWith('data:') ||
                u.startsWith('blob:')
            ) {
                return u;
            }
            if (u.startsWith('storage/')) return '/' + u;
            return '/storage/' + u.replace(/^\//, '');
        },

        syncVariantImage() {
            const v = this.activeVariant;
            if (v) {
                if (v.thumbnail) {
                    this.variantImage = this.normalizeMediaUrl(v.thumbnail);
                    return;
                }
                if (v.images && v.images[0]) {
                    this.variantImage = this.normalizeMediaUrl(v.images[0]);
                    return;
                }
            }
            const colorKey = Object.keys(this.selected).find(k => k.toLowerCase() === 'color' || k.toLowerCase() === 'colour');
            if (colorKey && this.selected[colorKey]) {
                const group = this.optionGroups.find(g => g.key === colorKey);
                const opt = (group?.values || []).find(o => o.name === this.selected[colorKey]);
                if (opt?.image) {
                    this.variantImage = this.normalizeMediaUrl(opt.image);
                    return;
                }
                const colorOpts = this.product.colorOptions || [];
                const match = colorOpts.find(c => (c.name || '').toLowerCase() === this.selected[colorKey].toLowerCase());
                if (match?.image) {
                    this.variantImage = this.normalizeMediaUrl(match.image);
                    return;
                }
            }
            this.variantImage = null;
        },

        formatPrice(n) {
            return '৳' + Math.round(Number(n) || 0).toLocaleString('en-BD');
        },

        addToCart(buyNow = false) {
            if (this.disableAddToCart) return;
            const v = this.activeVariant;
            const cartVariant = v ? {
                id: v.id,
                price: this.finalPrice,
                discountPrice: v.discountPrice,
                salePrice: v.salePrice || v.discountPrice,
                sku: v.sku,
                image: this.currentImage,
                thumbnail: v.thumbnail,
                images: v.images,
                options: v.options,
                stock: v.stock,
                color: this.selected.Color || this.selected.color || null,
                size: this.selected.Size || this.selected.size || null,
            } : null;

            Alpine.store('cart').addItem({
                id: this.product.id,
                title: this.product.title,
                slug: this.product.slug,
                price: this.finalPrice,
                effectivePrice: this.finalPrice,
                salePrice: this.product.salePrice,
                discount: this.product.discount,
                images: this.product.images,
                sku: this.product.sku,
            }, cartVariant, this.qty);

            if (buyNow) {
                window.location.href = this.checkoutUrl;
            }
        },
    }));
});

Alpine.start();
