import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Cart Store
document.addEventListener('alpine:init', () => {
    Alpine.store('cart', {
        items: JSON.parse(localStorage.getItem('mamabazar_cart') || '[]'),
        drawerOpen: false,

        init() {
            this.save();
        },

        /**
         * Resolve unit price: prefer explicit price, then variant discount/sale/regular, then product sale/discount.
         */
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
            // Treat discount as percent only when <= 100 (flat BDT offs are stored as salePrice)
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
                // Keep price/image in sync with the latest selection
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

        // Wishlist helpers
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

        // Compare helpers
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
});

Alpine.start();
