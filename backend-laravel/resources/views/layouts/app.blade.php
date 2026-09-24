<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Mama Bazar - Online Grocery & Essentials' }}</title>
    <link rel="icon" type="image/png" href="/brandlogo.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-[#F8FAF8] font-body text-slate-800 antialiased" x-data="{ mobileMenu: false }">

    <!-- Top Announcement Bar -->
    @php
        $announcement = $announcement ?? ['enabled' => false, 'text' => '', 'backgroundColor' => '#0F4D2C', 'textColor' => '#ffffff'];
    @endphp
    @if(!empty($announcement['enabled']) && !empty($announcement['text']))
        <div class="px-4 py-1.5 text-center text-xs font-medium" style="background-color: {{ $announcement['backgroundColor'] ?? '#0F4D2C' }}; color: {{ $announcement['textColor'] ?? '#ffffff' }}">
            <div class="mx-auto flex max-w-7xl items-center justify-center gap-2">
                <svg class="h-3.5 w-3.5 shrink-0 fill-current opacity-80" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                <span class="truncate">{{ $announcement['text'] }}</span>
            </div>
        </div>
    @endif

    <!-- Main Navigation Header -->
    <header class="navbar-root sticky top-0 z-40 border-b border-brand-green-100 transition duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            
            <!-- Mobile Menu Toggle & Logo -->
            <div class="flex items-center gap-3">
                <button type="button" @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 rounded-full border border-brand-green-100 bg-brand-green-50 text-brand-green-700 hover:bg-brand-green-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="/brandlogo.png" alt="Mama Bazar" class="h-9 w-9 object-contain">
                    <span class="font-extrabold text-xl tracking-tight hidden sm:inline">
                        <span class="text-brand-green-500">Mama</span><span class="text-brand-orange-500">Bazar</span>
                    </span>
                </a>
            </div>

            <!-- Search Bar -->
            <div class="flex-1 max-w-md hidden md:block mx-4">
                <form action="{{ route('shop') }}" method="GET" class="relative">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search products, brands, groceries..." 
                        class="w-full pl-10 pr-4 py-2 text-sm rounded-full border border-brand-green-200 bg-white focus:outline-none focus:border-brand-green-500 focus:ring-2 focus:ring-brand-green-100">
                    <div class="absolute left-3.5 top-2.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </form>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-3">
                <a href="{{ route('track') }}" class="hidden lg:inline-flex text-xs font-bold uppercase tracking-wider text-brand-green-600 hover:text-brand-green-700">
                    Track Order
                </a>

                <!-- Cart Button with Slide-over toggle -->
                <button type="button" @click="$store.cart.drawerOpen = true" class="relative flex items-center gap-2 px-3 py-2 rounded-full border border-brand-orange-200 bg-brand-orange-50 text-brand-orange-600 hover:bg-brand-orange-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span class="text-xs font-bold hidden sm:inline" x-text="'৳' + $store.cart.subtotal.toFixed(0)">৳0</span>
                    <span class="flex items-center justify-center h-5 min-w-5 rounded-full bg-brand-orange-500 text-white text-[10px] font-bold px-1" x-text="$store.cart.count">0</span>
                </button>

                <!-- Auth / User Menu -->
                @auth
                    <div class="relative" x-data="{ userMenu: false }">
                        <button type="button" @click="userMenu = !userMenu" class="flex items-center gap-2 py-1.5 px-3 rounded-full border border-brand-green-200 bg-brand-green-50 text-brand-green-700 hover:bg-brand-green-100">
                            <span class="h-6 w-6 rounded-full bg-brand-green-500 text-white text-xs font-bold flex items-center justify-center">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="text-xs font-semibold max-w-[80px] truncate hidden md:inline">{{ auth()->user()->name }}</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="userMenu" @click.away="userMenu = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-brand-green-100 rounded-2xl shadow-card py-2 z-50">
                            @if(in_array(auth()->user()->role, ['admin', 'manager', 'editor', 'staff', 'super_admin']) || auth()->user()->custom_role)
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-xs font-semibold text-brand-green-600 hover:bg-brand-green-50">Admin Panel</a>
                                <div class="border-t border-slate-100 my-1"></div>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Logout</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-brand-green-200 bg-brand-green-50 text-brand-green-700 text-xs font-bold hover:bg-brand-green-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        <span>Login</span>
                    </a>
                @endauth
            </div>
        </div>

        <!-- Secondary Category Navigation Bar -->
        <div class="hidden lg:block bg-brand-green-600 text-white border-t border-brand-green-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-10 flex items-center justify-between text-xs font-bold uppercase tracking-wider">
                <nav class="flex items-center gap-6">
                    <a href="{{ route('home') }}" class="hover:text-brand-orange-300 transition {{ request()->routeIs('home') ? 'text-brand-orange-300' : '' }}">Home</a>
                    <a href="{{ route('shop') }}" class="hover:text-brand-orange-300 transition {{ request()->routeIs('shop') && !request('sale') ? 'text-brand-orange-300' : '' }}">Shop</a>
                    <a href="{{ route('shop', ['sale' => 'true']) }}" class="text-brand-orange-300 hover:text-white transition flex items-center gap-1">
                        <span>🔥 Deals</span>
                    </a>
                    <a href="{{ route('track') }}" class="hover:text-brand-orange-300 transition">Order Tracking</a>
                    <a href="{{ route('about') }}" class="hover:text-brand-orange-300 transition">About Us</a>
                    <a href="{{ route('contact') }}" class="hover:text-brand-orange-300 transition">Contact</a>
                </nav>
                <div class="text-[11px] text-brand-green-100 font-normal">
                    Helpline: <span class="font-bold text-white">01700-000000</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer -->
    <div x-show="mobileMenu" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div class="fixed inset-0 bg-black/50" @click="mobileMenu = false"></div>
        <div class="fixed inset-y-0 left-0 max-w-xs w-full bg-white shadow-xl z-50 p-5 flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <span class="font-extrabold text-lg text-brand-green-600">MamaBazar Menu</span>
                <button type="button" @click="mobileMenu = false" class="text-slate-500 hover:text-slate-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="mt-4 space-y-3 flex-1 overflow-y-auto">
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Home</a>
                <a href="{{ route('shop') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Shop Catalog</a>
                <a href="{{ route('shop', ['sale' => 'true']) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold text-brand-orange-600 hover:bg-brand-orange-50">🔥 Hot Deals</a>
                <a href="{{ route('track') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Track Order</a>
                <a href="{{ route('about') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">About Us</a>
                <a href="{{ route('contact') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Contact Us</a>
            </nav>
        </div>
    </div>

    <!-- Slide-over Cart Drawer -->
    <div x-show="$store.cart.drawerOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-xs transition-opacity" @click="$store.cart.drawerOpen = false"></div>
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-brand-green-50">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <h2 class="font-bold text-slate-800">Your Shopping Cart (<span x-text="$store.cart.count">0</span>)</h2>
                    </div>
                    <button type="button" @click="$store.cart.drawerOpen = false" class="text-slate-500 hover:text-slate-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 divide-y divide-slate-100">
                    <template x-if="$store.cart.items.length === 0">
                        <div class="py-12 text-center text-slate-400">
                            <p class="text-sm">Your cart is currently empty.</p>
                            <a href="{{ route('shop') }}" @click="$store.cart.drawerOpen = false" class="inline-block mt-3 px-4 py-2 rounded-full bg-brand-green-500 text-white text-xs font-bold">Start Shopping</a>
                        </div>
                    </template>
                    <template x-for="item in $store.cart.items" :key="item.key">
                        <div class="py-3 flex items-center gap-3">
                            <img :src="item.image || '/brandlogo.png'" class="w-14 h-14 rounded-lg object-cover border border-slate-100 shrink-0">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xs font-bold text-slate-800 truncate" x-text="item.title"></h3>
                                <p class="text-xs font-semibold text-brand-orange-500 mt-0.5" x-text="'৳' + item.price.toFixed(0)"></p>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <button type="button" @click="$store.cart.updateQuantity(item.key, item.quantity - 1)" class="w-5 h-5 rounded bg-slate-100 text-xs font-bold flex items-center justify-center">-</button>
                                    <span class="text-xs font-bold" x-text="item.quantity"></span>
                                    <button type="button" @click="$store.cart.updateQuantity(item.key, item.quantity + 1)" class="w-5 h-5 rounded bg-slate-100 text-xs font-bold flex items-center justify-center">+</button>
                                </div>
                            </div>
                            <button type="button" @click="$store.cart.removeItem(item.key)" class="text-slate-400 hover:text-red-500 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50 space-y-3">
                    <div class="flex items-center justify-between text-sm font-bold">
                        <span class="text-slate-600">Subtotal:</span>
                        <span class="text-brand-orange-600" x-text="'৳' + $store.cart.subtotal.toFixed(0)">৳0</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('cart') }}" @click="$store.cart.drawerOpen = false" class="block text-center py-2.5 px-4 rounded-full border border-brand-green-500 text-brand-green-700 text-xs font-bold hover:bg-brand-green-50 transition">
                            View Cart
                        </a>
                        <a href="{{ route('checkout') }}" @click="$store.cart.drawerOpen = false" class="block text-center py-2.5 px-4 rounded-full bg-brand-orange-500 text-white text-xs font-bold hover:bg-brand-orange-600 transition shadow-sm">
                            Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1">
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 mt-4">
                <div class="p-3 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-sm font-medium flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 mt-4">
                <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-brand-green-100 bg-white pt-14 pb-12 mt-16 text-slate-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8">
                <!-- Brand Info -->
                <div class="col-span-2">
                    <div class="flex items-center gap-2">
                        <img src="/brandlogo.png" alt="Mama Bazar" class="h-9 w-9 object-contain">
                        <span class="font-extrabold text-xl tracking-tight">
                            <span class="text-brand-green-500">Mama</span><span class="text-brand-orange-500">Bazar</span>
                        </span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500 max-w-sm">
                        Your trusted daily online grocery, lifestyle and essentials store. High quality products delivered directly to your doorstep across Bangladesh.
                    </p>
                    <div class="mt-4 text-xs space-y-1.5 text-slate-500">
                        <p>📍 Dhaka, Bangladesh</p>
                        <p>📞 +880 1700-000000</p>
                        <p>✉️ support@mamabazar.com</p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Shop</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('shop') }}" class="hover:text-brand-green-600 transition">All Products</a></li>
                        <li><a href="{{ route('shop', ['sale' => 'true']) }}" class="hover:text-brand-green-600 transition">Flash Deals</a></li>
                        <li><a href="{{ route('track') }}" class="hover:text-brand-green-600 transition">Track Your Order</a></li>
                    </ul>
                </div>

                <!-- Policies -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Customer Care</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('page.show', 'return-refund') }}" class="hover:text-brand-green-600 transition">Return & Refund</a></li>
                        <li><a href="{{ route('page.show', 'shipping') }}" class="hover:text-brand-green-600 transition">Shipping Policy</a></li>
                        <li><a href="{{ route('page.show', 'privacy-policy') }}" class="hover:text-brand-green-600 transition">Privacy Policy</a></li>
                        <li><a href="{{ route('page.show', 'terms-and-conditions') }}" class="hover:text-brand-green-600 transition">Terms & Conditions</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Company</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('about') }}" class="hover:text-brand-green-600 transition">About Us</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-brand-green-600 transition">FAQ</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-brand-green-600 transition">Contact Us</a></li>
                        <li><a href="{{ route('admin.login') }}" class="text-slate-400 hover:text-slate-600 transition">Admin Portal</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-4">
                <p>&copy; {{ date('Y') }} MamaBazar. All rights reserved. Developed with Laravel & SQLite.</p>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded border border-brand-green-200 bg-brand-green-50 text-[10px] font-bold text-brand-green-700">Cash on Delivery</span>
                    <span class="px-2 py-0.5 rounded border border-pink-200 bg-pink-50 text-[10px] font-bold text-pink-700">bKash</span>
                    <span class="px-2 py-0.5 rounded border border-brand-orange-200 bg-brand-orange-50 text-[10px] font-bold text-brand-orange-700">Nagad</span>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
