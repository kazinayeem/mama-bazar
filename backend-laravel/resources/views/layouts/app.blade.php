<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <x-seo-head :seo="$seo ?? null" />
    <link rel="icon" type="image/png" href="{{ $business['favicon_url'] ?: '/brandlogo.png' }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-tracking-scripts />
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
    <header class="navbar-root sticky top-0 z-40 border-b border-brand-green-100/80 transition duration-300"
            x-data="{ scrolled: false }"
            x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 8 }, { passive: true })"
            :class="scrolled && 'navbar--scrolled'">
        <div class="store-container flex h-14 items-center justify-between gap-3 sm:h-16 sm:gap-4">
            
            <!-- Mobile Menu Toggle & Logo -->
            <div class="flex min-w-0 items-center gap-2.5">
                <button type="button" @click="mobileMenu = !mobileMenu" class="lg:hidden rounded-[8px] border border-brand-green-100 bg-brand-green-50 p-2 text-brand-green-700 hover:bg-brand-green-100" aria-label="Open menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ $business['logo_url'] ?: '/brandlogo.png' }}" alt="{{ $business['business_name'] }}" class="h-8 w-8 object-contain sm:h-9 sm:w-9">
                    <span class="hidden font-extrabold text-lg tracking-tight sm:inline sm:text-xl">
                        <span class="text-brand-green-500">{{ $business['name_first_part'] }}</span><span class="text-brand-orange-500">{{ $business['name_second_part'] }}</span>
                    </span>
                </a>
            </div>

            <!-- Search Bar (desktop/tablet) -->
            <div class="mx-2 hidden max-w-xl flex-1 md:block">
                <form action="{{ route('shop') }}" method="GET" class="relative" role="search">
                    <label class="sr-only" for="header-search">Search products</label>
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search products, brands, groceries…"
                        class="h-10 w-full rounded-[8px] border border-brand-green-200 bg-white py-2 pl-10 pr-4 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                    <div class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </form>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('track') }}" class="hidden text-xs font-bold uppercase tracking-wider text-brand-green-600 transition hover:text-brand-green-700 lg:inline-flex">
                    Track Order
                </a>

                <button type="button" @click="$store.cart.drawerOpen = true" class="relative inline-flex items-center gap-2 rounded-[8px] border border-brand-orange-200 bg-brand-orange-50 px-2.5 py-2 text-brand-orange-600 transition hover:bg-brand-orange-100 sm:px-3" aria-label="Open cart">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span class="hidden text-xs font-bold sm:inline" x-text="'৳' + $store.cart.subtotal.toFixed(0)">৳0</span>
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-orange-500 px-1 text-[10px] font-bold text-white" x-text="$store.cart.count">0</span>
                </button>

                @auth
                    <div class="relative" x-data="{ userMenu: false }">
                        <button type="button" @click="userMenu = !userMenu" class="flex items-center gap-2 rounded-[8px] border border-brand-green-200 bg-brand-green-50 px-2 py-1 text-brand-green-700 hover:bg-brand-green-100 transition" aria-label="Account menu">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-600 text-xs font-black text-white shadow-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden max-w-[95px] truncate text-xs font-bold sm:inline">{{ auth()->user()->name }}</span>
                            <svg class="h-3.5 w-3.5 text-brand-green-600 transition-transform duration-200" :class="{ 'rotate-180': userMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="userMenu" @click.away="userMenu = false" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl ring-1 ring-black/5">

                            {{-- Header in dropdown --}}
                            <div class="px-3 py-2 border-b border-slate-100 mb-1">
                                <div class="text-xs font-extrabold text-slate-900 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email ?: auth()->user()->phone }}</div>
                            </div>

                            @if(in_array(auth()->user()->role, ['admin', 'manager', 'editor', 'staff', 'super_admin']) || auth()->user()->custom_role)
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-brand-green-700 bg-brand-green-50/60 hover:bg-brand-green-100 transition">
                                    <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span>Admin Panel</span>
                                </a>
                                <div class="my-1 border-t border-slate-100"></div>
                            @endif

                            <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                <span>My Profile</span>
                            </a>

                            <a href="{{ route('account.orders') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <span>My Orders</span>
                            </a>

                            <a href="{{ route('track') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Track My Orders</span>
                            </a>

                            <a href="{{ route('account.addresses') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                <span>My Addresses</span>
                            </a>

                            <a href="{{ route('account.settings') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Account Settings</span>
                            </a>

                            <a href="{{ route('account.email') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>Email Preferences</span>
                            </a>

                            @if(auth()->user()->mustVerifyEmail())
                                <a href="{{ route('auth.verify-otp') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-bold text-amber-700 bg-amber-50/60 hover:bg-amber-100 transition">
                                    <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Verify Email</span>
                                </a>
                            @endif

                            <div class="my-1 border-t border-slate-100"></div>

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-xs font-semibold text-red-600 hover:bg-red-50 transition">
                                    <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-[8px] border border-brand-green-200 bg-brand-green-50 px-3 py-2 text-xs font-bold text-brand-green-700 transition hover:bg-brand-green-100">
                            <span>Sign In</span>
                        </a>
                        <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center gap-1 rounded-[8px] bg-brand-green-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-brand-green-700 shadow-xs">
                            <span>Register</span>
                        </a>
                    </div>
                @endauth
            </div>
        </div>

        {{-- Mobile search row --}}
        <div class="border-t border-brand-green-50 px-4 pb-3 pt-2 md:hidden">
            <form action="{{ route('shop') }}" method="GET" class="relative" role="search">
                <label class="sr-only" for="mobile-search">Search products</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search products…"
                    class="h-10 w-full rounded-[8px] border border-brand-green-200 bg-white py-2 pl-10 pr-3 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                <div class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </form>
        </div>

        <!-- Secondary Category Navigation Bar -->
        <div class="hidden border-t border-brand-green-500 bg-brand-green-600 text-white lg:block">
            <div class="store-container flex h-10 items-center justify-between text-xs font-bold uppercase tracking-wider">
                <nav class="flex items-center gap-6" aria-label="Primary">
                    <a href="{{ route('home') }}" class="transition hover:text-brand-orange-300 {{ request()->routeIs('home') ? 'text-brand-orange-300' : '' }}">Home</a>
                    <a href="{{ route('shop') }}" class="transition hover:text-brand-orange-300 {{ request()->routeIs('shop') && !request('sale') ? 'text-brand-orange-300' : '' }}">Shop</a>
                    <a href="{{ route('shop', ['sale' => 'true']) }}" class="flex items-center gap-1 text-brand-orange-300 transition hover:text-white">
                        <span>Deals</span>
                    </a>
                    <a href="{{ route('track') }}" class="transition hover:text-brand-orange-300">Order Tracking</a>
                    <a href="{{ route('about') }}" class="transition hover:text-brand-orange-300">About Us</a>
                    <a href="{{ route('contact') }}" class="transition hover:text-brand-orange-300">Contact</a>
                </nav>
                <div class="text-[11px] font-normal text-brand-green-100">
                    Helpline: <a href="tel:{{ $business['phone_raw'] }}" class="font-bold text-white hover:underline">{{ $business['primary_phone'] }}</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer -->
    <div x-show="mobileMenu" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div class="fixed inset-0 bg-black/50" @click="mobileMenu = false"></div>
        <div class="fixed inset-y-0 left-0 max-w-xs w-full bg-white shadow-xl z-50 p-5 flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <span class="font-extrabold text-lg text-brand-green-600">{{ $business['business_name'] }}</span>
                <button type="button" @click="mobileMenu = false" class="text-slate-500 hover:text-slate-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="mt-4 space-y-2 flex-1 overflow-y-auto">
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Home</a>
                <a href="{{ route('shop') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Shop Catalog</a>
                <a href="{{ route('shop', ['sale' => 'true']) }}" class="block px-3 py-2 rounded-lg text-sm font-semibold text-brand-orange-600 hover:bg-brand-orange-50">🔥 Hot Deals</a>
                <a href="{{ route('track') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Track Order</a>
                <a href="{{ route('about') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">About Us</a>
                <a href="{{ route('contact') }}" class="block px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-green-50">Contact Us</a>

                {{-- Mobile Customer Account Section --}}
                <div class="pt-4 border-t border-slate-100 mt-3 space-y-1">
                    @auth
                        <div class="px-3 py-2 mb-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-green-600 text-xs font-black text-white shrink-0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email ?: auth()->user()->phone }}</div>
                            </div>
                        </div>

                        @if(in_array(auth()->user()->role, ['admin', 'manager', 'editor', 'staff', 'super_admin']) || auth()->user()->custom_role)
                            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg text-xs font-bold text-brand-green-700 bg-brand-green-50">
                                Go to Admin Panel
                            </a>
                        @endif

                        <a href="{{ route('account.dashboard') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-brand-green-50">
                            My Account
                        </a>
                        <a href="{{ route('account.orders') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-brand-green-50">
                            My Orders
                        </a>
                        <a href="{{ route('account.addresses') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-brand-green-50">
                            My Addresses
                        </a>
                        <a href="{{ route('account.profile') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-brand-green-50">
                            Personal Profile
                        </a>
                        <a href="{{ route('account.settings') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-brand-green-50">
                            Security & Password
                        </a>
                        <a href="{{ route('account.email') }}" class="block px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 hover:bg-brand-green-50">
                            Email Preferences
                        </a>

                        <form action="{{ route('logout') }}" method="POST" class="pt-2 border-t border-slate-100">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50">
                                Logout
                            </button>
                        </form>
                    @else
                        <div class="p-2 space-y-2">
                            <a href="{{ route('login') }}" class="block w-full text-center py-2 px-4 rounded-xl bg-brand-green-600 text-white text-xs font-bold shadow-sm">
                                Sign In
                            </a>
                            <a href="{{ route('register') }}" class="block w-full text-center py-2 px-4 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50">
                                Register Account
                            </a>
                        </div>
                    @endauth
                </div>
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
            <div class="store-container mt-4">
                <div class="flex items-center gap-2 rounded-[10px] border border-brand-green-200 bg-brand-green-50 p-3 text-sm font-medium text-brand-green-800">
                    <svg class="h-5 w-5 shrink-0 text-brand-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="store-container mt-4">
                <div class="flex items-center gap-2 rounded-[10px] border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-800">
                    <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-12 border-t border-brand-green-100 bg-white pb-8 pt-12 text-slate-600">
        <div class="store-container">
            @if(!empty($footerTeamEnabled) && !empty($footerTeamMembers) && $footerTeamMembers->isNotEmpty())
                <div class="mb-10 pb-8 border-b border-brand-green-100/80">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                        <div>
                            <span class="inline-block rounded-full bg-brand-green-50 border border-brand-green-200/80 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-brand-green-700">Company Leadership</span>
                            <h3 class="mt-1 text-base font-extrabold text-slate-900 tracking-tight">{{ $footerTeamTitle ?? 'Leadership & Core Team' }}</h3>
                        </div>
                        <a href="{{ route('team') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand-green-700 hover:text-brand-green-800 transition group">
                            <span>View All Team Members</span>
                            <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                        @foreach($footerTeamMembers as $member)
                            <a href="{{ route('team') }}" class="group block rounded-2xl border border-slate-100 bg-slate-50/70 p-3 text-center transition duration-200 hover:-translate-y-0.5 hover:border-brand-green-200 hover:bg-white hover:shadow-xs">
                                <div class="relative mx-auto h-13 w-13 overflow-hidden rounded-full ring-2 ring-white shadow-2xs">
                                    <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-108" loading="lazy">
                                </div>
                                <h4 class="mt-2 truncate text-xs font-bold text-slate-800 group-hover:text-brand-green-700 transition">{{ $member->name }}</h4>
                                <p class="truncate text-[10px] font-medium text-slate-500">{{ $member->position }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-4 lg:grid-cols-5">
                <div class="col-span-2">
                    <div class="flex items-center gap-2">
                        <img src="{{ $business['logo_url'] ?: '/brandlogo.png' }}" alt="{{ $business['business_name'] }}" class="h-9 w-9 object-contain">
                        <span class="text-xl font-extrabold tracking-tight">
                            <span class="text-brand-green-500">{{ $business['name_first_part'] }}</span><span class="text-brand-orange-500">{{ $business['name_second_part'] }}</span>
                        </span>
                    </div>
                    <p class="mt-3 max-w-sm text-xs leading-5 text-slate-500">
                        {{ $business['footer_description'] ?: $business['business_description'] }}
                    </p>
                    @if(!empty($footerSocials))
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($footerSocials as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 transition hover:border-brand-green-500 hover:text-brand-green-700">{{ $social['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <nav aria-label="Shop">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-900">Shop</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="{{ route('shop') }}" class="inline-block py-1 transition hover:text-brand-green-600">All Products</a></li>
                        <li><a href="{{ route('shop') }}" class="inline-block py-1 transition hover:text-brand-green-600">Categories</a></li>
                        <li><a href="{{ route('shop', ['sale' => 'true']) }}" class="inline-block py-1 transition hover:text-brand-green-600">Deals</a></li>
                        <li><a href="{{ route('shop', ['sort' => 'newest']) }}" class="inline-block py-1 transition hover:text-brand-green-600">New Arrivals</a></li>
                        <li><a href="{{ route('track') }}" class="inline-block py-1 transition hover:text-brand-green-600">Track Your Order</a></li>
                    </ul>
                </nav>

                <nav aria-label="Customer Support">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-900">Customer Support</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="{{ route('contact') }}" class="inline-block py-1 transition hover:text-brand-green-600">Contact Us</a></li>
                        <li><a href="{{ $business['return_policy_url'] ?: route('page.show', 'return-refund') }}" class="inline-block py-1 transition hover:text-brand-green-600">Return &amp; Refund Policy</a></li>
                        <li><a href="{{ route('page.show', 'shipping-policy') }}" class="inline-block py-1 transition hover:text-brand-green-600">Shipping Policy</a></li>
                        <li><a href="{{ $business['privacy_policy_url'] ?: route('page.show', 'privacy-policy') }}" class="inline-block py-1 transition hover:text-brand-green-600">Privacy Policy</a></li>
                        <li><a href="{{ $business['terms_url'] ?: route('page.show', 'terms') }}" class="inline-block py-1 transition hover:text-brand-green-600">Terms &amp; Conditions</a></li>
                        <li><a href="{{ route('faq') }}" class="inline-block py-1 transition hover:text-brand-green-600">Frequently Asked Questions</a></li>
                    </ul>
                </nav>

                <nav aria-label="About">
                    <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-900">About</h4>
                    <ul class="space-y-1 text-xs">
                        <li><a href="{{ route('about') }}" class="inline-block py-1 transition hover:text-brand-green-600">About Mama Bazar</a></li>
                        <li><a href="{{ route('team') }}" class="inline-block py-1 transition hover:text-brand-green-600 font-semibold text-brand-green-700">Our Team</a></li>
                        <li><a href="{{ route('contact') }}" class="inline-block py-1 transition hover:text-brand-green-600">Contact Us</a></li>
                        <li><a href="{{ route('faq') }}" class="inline-block py-1 transition hover:text-brand-green-600">Help Center</a></li>
                    </ul>
                    <div class="mt-4 space-y-1 text-xs text-slate-500">
                        @if(!empty($business['formatted_address']))
                            <p>{{ $business['formatted_address'] }}</p>
                        @endif
                        @if(!empty($business['primary_phone']))
                            <p><a href="tel:{{ $business['phone_raw'] }}" class="hover:text-brand-green-600 transition">{{ $business['primary_phone'] }}</a></p>
                        @endif
                        @if(!empty($business['support_email']))
                            <p><a href="mailto:{{ $business['support_email'] }}" class="hover:text-brand-green-600 transition">{{ $business['support_email'] }}</a></p>
                        @endif
                        @if(!empty($business['whatsapp_number']))
                            <p><a href="{{ $business['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-600 transition font-medium">WhatsApp: {{ $business['whatsapp_number'] }}</a></p>
                        @endif
                    </div>
                </nav>
            </div>

            <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-slate-100 pt-6 text-xs text-slate-400 sm:flex-row">
                <p class="text-center sm:text-left">{{ $business['copyright_rendered'] }} · <button type="button" onclick="window.mbConsentShow && window.mbConsentShow()" class="underline hover:text-slate-600">Cookie settings</button><br class="sm:hidden">
                    <span class="mt-1 inline-block sm:ml-1 sm:mt-0">Crafted by &amp; Developed by <a href="https://bornosoft.bd/" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-600 underline decoration-slate-300 underline-offset-2 hover:text-brand-green-700 transition">Bornosoft</a></span></p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span class="mr-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">We Accept</span>
                    @forelse(($footerPaymentMethods ?? collect())->reject(fn ($m) => $m->code === 'cod') as $pm)
                        @php
                            $style = match($pm->code) {
                                'bkash' => 'border-pink-200 bg-pink-50 text-pink-700',
                                'nagad' => 'border-brand-orange-200 bg-brand-orange-50 text-brand-orange-700',
                                'rocket' => 'border-violet-200 bg-violet-50 text-violet-700',
                                'bank' => 'border-sky-200 bg-sky-50 text-sky-700',
                                default => 'border-brand-green-200 bg-brand-green-50 text-brand-green-700',
                            };
                        @endphp
                        <span class="rounded border px-2 py-0.5 text-[10px] font-bold {{ $style }}">{{ $pm->name }}</span>
                    @empty
                        <span class="rounded border border-brand-green-200 bg-brand-green-50 px-2 py-0.5 text-[10px] font-bold text-brand-green-700">Cash on Delivery</span>
                        <span class="rounded border border-pink-200 bg-pink-50 px-2 py-0.5 text-[10px] font-bold text-pink-700">bKash</span>
                        <span class="rounded border border-brand-orange-200 bg-brand-orange-50 px-2 py-0.5 text-[10px] font-bold text-brand-orange-700">Nagad</span>
                    @endforelse
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
    <x-consent-banner />

    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/6abfcac8beb8e034c00dd82d/1k3uj08dk';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
    })();
    </script>
    <!--End of Tawk.to Script-->
</body>
</html>
