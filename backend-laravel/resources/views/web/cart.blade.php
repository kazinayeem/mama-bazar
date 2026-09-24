@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data>

    <div class="flex items-center justify-between pb-4 border-b border-brand-green-100">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Shopping Cart</h1>
        <button type="button" @click="$store.cart.clear()" class="text-xs font-semibold text-red-500 hover:text-red-700" x-show="$store.cart.items.length > 0">
            Clear All Items
        </button>
    </div>

    <!-- Empty State -->
    <div x-show="$store.cart.items.length === 0" x-cloak class="bg-white p-12 rounded-3xl border border-brand-green-100 text-center space-y-4">
        <div class="w-16 h-16 rounded-full bg-brand-green-50 text-brand-green-600 flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <h2 class="text-lg font-bold text-slate-800">Your cart is empty</h2>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">Looks like you haven't added anything to your cart yet. Explore our grocery and essentials catalog!</p>
        <a href="{{ route('shop') }}" class="inline-block px-6 py-2.5 rounded-full bg-brand-green-600 text-white text-xs font-bold shadow-md hover:bg-brand-green-700 transition">
            Continue Shopping
        </a>
    </div>

    <!-- Items Grid & Checkout summary -->
    <div x-show="$store.cart.items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Cart Items List -->
        <div class="lg:col-span-2 space-y-3">
            <template x-for="item in $store.cart.items" :key="item.key">
                <div class="bg-white p-4 rounded-2xl border border-brand-green-100 flex items-center gap-4 shadow-soft">
                    <img :src="item.image || '/brandlogo.png'" class="w-16 h-16 rounded-xl object-cover border border-slate-100 shrink-0">
                    <div class="flex-1 min-w-0">
                        <a :href="'/products/' + item.slug" class="text-sm font-bold text-slate-800 hover:text-brand-green-600 truncate block" x-text="item.title"></a>
                        <p class="text-xs font-semibold text-brand-orange-600 mt-0.5" x-text="'৳' + item.price.toFixed(0)"></p>
                    </div>

                    <!-- Quantity Control -->
                    <div class="flex items-center border border-slate-200 rounded-full bg-slate-50 p-0.5">
                        <button type="button" @click="$store.cart.updateQuantity(item.key, item.quantity - 1)" class="w-7 h-7 rounded-full bg-white text-xs font-bold shadow-xs hover:bg-slate-100">-</button>
                        <span class="w-8 text-center text-xs font-bold" x-text="item.quantity"></span>
                        <button type="button" @click="$store.cart.updateQuantity(item.key, item.quantity + 1)" class="w-7 h-7 rounded-full bg-white text-xs font-bold shadow-xs hover:bg-slate-100">+</button>
                    </div>

                    <!-- Item Total -->
                    <div class="text-sm font-extrabold text-slate-800 w-20 text-right" x-text="'৳' + (item.price * item.quantity).toFixed(0)"></div>

                    <!-- Remove -->
                    <button type="button" @click="$store.cart.removeItem(item.key)" class="text-slate-400 hover:text-red-500 p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Order Summary -->
        <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-4 h-fit">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Order Summary</h2>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Items Total:</span>
                    <span class="font-bold text-slate-800" x-text="'৳' + $store.cart.subtotal.toFixed(0)">৳0</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Estimated Shipping:</span>
                    <span class="text-slate-400">Calculated at checkout</span>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-between text-sm font-extrabold">
                <span class="text-slate-800">Subtotal:</span>
                <span class="text-brand-orange-600" x-text="'৳' + $store.cart.subtotal.toFixed(0)">৳0</span>
            </div>

            <a href="{{ route('checkout') }}" class="block w-full py-3 px-4 rounded-full bg-brand-orange-500 hover:bg-brand-orange-600 text-white text-center text-xs font-bold shadow-md transition">
                Proceed To Checkout &rarr;
            </a>

            <div class="pt-2 text-[11px] text-center text-slate-400">
                🔒 Safe & Secure Checkout Guaranteed
            </div>
        </div>

    </div>

</div>
@endsection
