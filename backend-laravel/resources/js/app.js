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

        addItem(product, variant = null, quantity = 1) {
            const price = variant && variant.price ? parseFloat(variant.price) : parseFloat(product.price || 0);
            const image = variant && variant.image ? variant.image : (product.images && product.images[0] ? product.images[0] : (product.image || ''));
            const key = variant ? `${product.id}-${variant.id}` : `${product.id}`;

            const existingIndex = this.items.findIndex(i => i.key === key);
            if (existingIndex > -1) {
                this.items[existingIndex].quantity += quantity;
            } else {
                this.items.push({
                    key,
                    id: product.id,
                    variantId: variant ? variant.id : null,
                    title: product.title,
                    price: price,
                    image: image,
                    slug: product.slug,
                    quantity: quantity
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
        }
    });
});

Alpine.start();
