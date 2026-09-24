<div x-data="{
        open: false,
        loading: false,
        product: null,
        activeImage: '',
        selectedColor: '',
        selectedSize: '',
        quantity: 1,
        added: false,
        loadProduct(id) {
            this.loading = true;
            this.open = true;
            this.product = null;
            this.quantity = 1;
            fetch('/api/products/' + id)
                .then(r => r.json())
                .then(res => {
                    this.product = res.data;
                    this.activeImage = (this.product.images && this.product.images.length > 0) ? this.product.images[0] : '/brandlogo.png';
                    this.selectedColor = (this.product.colorOptions && this.product.colorOptions.length > 0) ? this.product.colorOptions[0].name : '';
                    this.selectedSize = (this.product.sizeOptions && this.product.sizeOptions.length > 0) ? this.product.sizeOptions[0] : '';
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
        },
        addToCart() {
            if (!this.product || this.product.stock <= 0) return;
            let price = this.product.salePrice ? parseFloat(this.product.salePrice) : parseFloat(this.product.price);
            $store.cart.addItem({
                id: this.product.id,
                title: this.product.title,
                slug: this.product.slug,
                price: price,
                image: this.activeImage,
                color: this.selectedColor,
                size: this.selectedSize,
                quantity: this.quantity
            });
            this.added = true;
            setTimeout(() => {
                this.added = false;
                this.open = false;
            }, 800);
        }
     }"
     @open-quick-view.window="loadProduct($event.detail.id)"
     x-show="open" 
     x-cloak 
     class="fixed inset-0 z-50 overflow-y-auto" 
     role="dialog" 
     aria-modal="true">

    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" 
         @click="open = false" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    <!-- Modal Content -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden p-6 sm:p-8"
             @click.away="open = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Close Button -->
            <button type="button" @click="open = false" class="absolute right-4 top-4 p-2 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Loading State -->
            <template x-if="loading">
                <div class="py-16 flex flex-col items-center justify-center gap-3 text-slate-400">
                    <div class="w-8 h-8 rounded-full border-2 border-brand-green-500 border-t-transparent animate-spin"></div>
                    <p class="text-xs font-semibold">Loading product details...</p>
                </div>
            </template>

            <!-- Product Display -->
            <template x-if="!loading && product">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Gallery preview -->
                    <div class="flex flex-col gap-3">
                        <div class="aspect-square rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center p-4">
                            <img :src="activeImage" :alt="product.title" class="max-h-full max-w-full object-contain">
                        </div>
                        <div class="flex items-center gap-2 overflow-x-auto">
                            <template x-for="(img, idx) in product.images" :key="idx">
                                <button type="button" @click="activeImage = img" :class="activeImage === img ? 'ring-2 ring-brand-green-500' : 'opacity-70 hover:opacity-100'" class="w-12 h-12 rounded-lg border border-slate-200 overflow-hidden p-1 shrink-0 bg-white">
                                    <img :src="img" class="w-full h-full object-contain">
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Details & Controls -->
                    <div class="flex flex-col justify-between">
                        <div class="space-y-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-green-600" x-text="product.brand || 'Mama Bazar'"></p>
                            <h2 class="text-lg font-extrabold text-slate-900 leading-snug" x-text="product.title"></h2>

                            <!-- Price -->
                            <div class="flex items-baseline gap-2 pt-1">
                                <span class="text-xl font-extrabold text-slate-900" x-text="'৳' + (product.salePrice ? parseFloat(product.salePrice).toFixed(0) : parseFloat(product.price).toFixed(0))"></span>
                                <template x-if="product.salePrice && parseFloat(product.salePrice) < parseFloat(product.price)">
                                    <span class="text-xs text-slate-400 line-through" x-text="'৳' + parseFloat(product.price).toFixed(0)"></span>
                                </template>
                            </div>

                            <!-- Colors -->
                            <template x-if="product.colorOptions && product.colorOptions.length > 0">
                                <div class="pt-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Color: <span class="text-slate-900" x-text="selectedColor"></span></label>
                                    <div class="flex items-center gap-2">
                                        <template x-for="c in product.colorOptions" :key="c.name">
                                            <button type="button" @click="selectedColor = c.name" :class="selectedColor === c.name ? 'ring-2 ring-brand-green-500 scale-110' : 'ring-1 ring-slate-200'" class="h-6 w-6 rounded-full transition" :style="'background-color: ' + (c.value || '#ccc')"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Sizes -->
                            <template x-if="product.sizeOptions && product.sizeOptions.length > 0">
                                <div class="pt-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Size: <span class="text-slate-900" x-text="selectedSize"></span></label>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <template x-for="s in product.sizeOptions" :key="s">
                                            <button type="button" @click="selectedSize = s" :class="selectedSize === s ? 'border-brand-green-600 bg-brand-green-50 text-brand-green-700 font-bold' : 'border-slate-200 text-slate-600 hover:border-slate-300'" class="px-3 py-1 text-xs border rounded-lg transition" x-text="s"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Quantity Stepper -->
                            <div class="pt-2 flex items-center gap-3">
                                <label class="text-xs font-semibold text-slate-600">Quantity:</label>
                                <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50">
                                    <button type="button" @click="if(quantity > 1) quantity--" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-slate-900 font-bold text-sm">-</button>
                                    <span class="w-8 text-center text-xs font-bold" x-text="quantity"></span>
                                    <button type="button" @click="quantity++" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-slate-900 font-bold text-sm">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Action CTA -->
                        <div class="pt-6 border-t border-slate-100 flex items-center gap-3">
                            <button type="button" @click="addToCart()" :disabled="product.stock <= 0" :class="added ? 'bg-brand-green-600 text-white' : (product.stock <= 0 ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-brand-orange-500 hover:bg-brand-orange-600 text-white')" class="flex-1 py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-md transition">
                                <span x-show="!added">Add to Cart</span>
                                <span x-show="added">✓ Added to Cart!</span>
                            </button>
                            <a :href="'/products/' + product.slug" class="py-3 px-4 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition">
                                Full Details
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
