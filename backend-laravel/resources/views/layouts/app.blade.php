<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <x-seo-head :seo="$seo ?? null" />
    <link rel="icon" type="image/png" href="{{ $business['favicon_url'] ?: '/brandlogo.png' }}">
    
    <!-- DNS Prefetch & Preconnect for Fast LCP & FCP -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Critical Responsive Media Queries (Solves Responsive Checkup) -->
    <style>
        @media (min-width: 640px) {
            .store-container { padding-left: 1.5rem; padding-right: 1.5rem; }
        }
        @media (min-width: 1024px) {
            .store-container { padding-left: 2rem; padding-right: 2rem; }
        }
        @media (max-width: 639px) {
            .store-container { padding-left: 1rem; padding-right: 1rem; }
        }
        img { max-width: 100%; height: auto; }
    </style>

    <!-- Non-render-blocking Web Fonts -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;600;700&display=swap">
    </noscript>

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
    @php
        $footerSocialIcons = [
            'facebook' => 'M24 12.07C24 5.41 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07',
            'instagram' => 'M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z',
            'youtube' => 'M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z',
            'tiktok' => 'M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
            'linkedin' => 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z',
            'twitter' => 'M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.4l-5.8-7.58-6.63 7.58H.49l8.6-9.83L0 1.15h7.59l5.24 6.93 6.07-6.93zm-1.29 19.5h2.04L6.48 3.24H4.3l13.31 17.41z',
        ];
        $footerLinkClass = 'inline-flex py-1 text-[13px] text-brand-green-100/75 transition hover:translate-x-0.5 hover:text-white';
        $returnPolicyUrl = $business['return_policy_url'] ?: route('page.show', 'return-refund');
    @endphp
    <footer class="mt-12 bg-brand-green-700 text-brand-green-100/80">
        {{-- Service highlights --}}
        <div class="border-b border-white/10 bg-brand-green-600">
            <div class="store-container grid grid-cols-2 gap-x-4 gap-y-5 py-6 lg:grid-cols-4">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-brand-orange-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm10 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-white">Nationwide delivery</p>
                        <p class="mt-0.5 text-xs text-brand-green-100/70">Doorstep delivery across Bangladesh</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-brand-orange-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3zm-3 9l2 2 4-4"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-white">Secure payment</p>
                        <p class="mt-0.5 text-xs text-brand-green-100/70">Protected checkout via SSLCommerz</p>
                    </div>
                </div>
                <a href="{{ $returnPolicyUrl }}" class="group flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-brand-orange-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 9h11a5 5 0 010 10H9M4 9l4-4M4 9l4 4"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-white group-hover:underline">Easy returns</p>
                        <p class="mt-0.5 text-xs text-brand-green-100/70">See our return &amp; refund policy</p>
                    </div>
                </a>
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-brand-orange-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 13v-1a8 8 0 0116 0v1M4 13a2 2 0 012-2h1v6H6a2 2 0 01-2-2v-2zm16 0a2 2 0 00-2-2h-1v6h1a2 2 0 002-2v-2zM17 17c0 2-2 3-5 3"/></svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-white">Need help?</p>
                        @if(!empty($business['primary_phone']))
                            <a href="tel:{{ $business['phone_raw'] }}" class="mt-0.5 block text-xs text-brand-green-100/70 hover:text-white">Call {{ $business['primary_phone'] }}</a>
                        @else
                            <a href="{{ route('contact') }}" class="mt-0.5 block text-xs text-brand-green-100/70 hover:text-white">Contact our support team</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="store-container pt-12">
            @if(!empty($footerTeamEnabled) && !empty($footerTeamMembers) && $footerTeamMembers->isNotEmpty())
                <div class="mb-12 border-b border-white/10 pb-10">
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <span class="inline-block rounded-full border border-white/15 bg-white/5 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-brand-orange-300">Company Leadership</span>
                            <h3 class="mt-2 text-lg font-extrabold tracking-tight text-white">{{ $footerTeamTitle ?? 'Leadership & Core Team' }}</h3>
                        </div>
                        <a href="{{ route('team') }}" class="group inline-flex items-center gap-1 text-xs font-bold text-brand-orange-300 transition hover:text-brand-orange-200">
                            <span>View All Team Members</span>
                            <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-6">
                        @foreach($footerTeamMembers as $member)
                            <a href="{{ route('team') }}" class="group block rounded-2xl border border-white/10 bg-white/5 p-3 text-center transition duration-200 hover:-translate-y-0.5 hover:border-white/25 hover:bg-white/10">
                                <div class="relative mx-auto h-13 w-13 overflow-hidden rounded-full ring-2 ring-white/20">
                                    <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-108" loading="lazy">
                                </div>
                                <h4 class="mt-2 truncate text-xs font-bold text-white">{{ $member->name }}</h4>
                                <p class="truncate text-[10px] font-medium text-brand-green-100/60">{{ $member->position }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-12 lg:gap-x-10">
                {{-- Brand, contact & social --}}
                <div class="col-span-2 lg:col-span-4">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm">
                            <img src="{{ $business['logo_url'] ?: '/brandlogo.png' }}" alt="{{ $business['business_name'] }}" class="h-full w-full object-contain">
                        </span>
                        <span class="text-xl font-extrabold tracking-tight">
                            <span class="text-white">{{ $business['name_first_part'] }}</span><span class="text-brand-orange-400">{{ $business['name_second_part'] }}</span>
                        </span>
                    </a>
                    <p class="mt-4 max-w-sm text-[13px] leading-6 text-brand-green-100/70">
                        {{ $business['footer_description'] ?: $business['business_description'] }}
                    </p>

                    <ul class="mt-5 space-y-2.5 text-[13px]">
                        @if(!empty($business['formatted_address']))
                            <li class="flex items-start gap-2.5">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-7-6.2-7-11.5a7 7 0 1114 0C19 14.8 12 21 12 21zm0-9a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/></svg>
                                <span>{{ $business['formatted_address'] }}</span>
                            </li>
                        @endif
                        @if(!empty($business['primary_phone']))
                            <li class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-brand-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2"/></svg>
                                <a href="tel:{{ $business['phone_raw'] }}" class="transition hover:text-white">{{ $business['primary_phone'] }}</a>
                            </li>
                        @endif
                        @if(!empty($business['support_email']))
                            <li class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-brand-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <a href="mailto:{{ $business['support_email'] }}" class="break-all transition hover:text-white">{{ $business['support_email'] }}</a>
                            </li>
                        @endif
                        @if(!empty($business['whatsapp_number']))
                            <li class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-brand-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20l1.3-3.9A8 8 0 1112 20a8 8 0 01-4.1-1.1L4 20z"/></svg>
                                <a href="{{ $business['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="transition hover:text-white">WhatsApp: {{ $business['whatsapp_number'] }}</a>
                            </li>
                        @endif
                    </ul>

                    @if(!empty($footerSocials))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach($footerSocials as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                                   aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}"
                                   class="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/5 text-white transition hover:-translate-y-0.5 hover:border-brand-orange-400 hover:bg-brand-orange-500">
                                    @if(isset($footerSocialIcons[$social['icon'] ?? '']))
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $footerSocialIcons[$social['icon']] }}"/></svg>
                                    @else
                                        <span class="text-[11px] font-bold">{{ mb_substr($social['label'], 0, 1) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <nav aria-label="Shop" class="lg:col-span-2">
                    <h4 class="mb-4 text-xs font-bold uppercase tracking-[0.14em] text-white">Shop</h4>
                    <ul class="space-y-1">
                        <li><a href="{{ route('shop') }}" class="{{ $footerLinkClass }}">All Products</a></li>
                        <li><a href="{{ route('shop') }}" class="{{ $footerLinkClass }}">Categories</a></li>
                        <li><a href="{{ route('shop', ['sale' => 'true']) }}" class="{{ $footerLinkClass }}">Deals</a></li>
                        <li><a href="{{ route('shop', ['sort' => 'newest']) }}" class="{{ $footerLinkClass }}">New Arrivals</a></li>
                        <li><a href="{{ route('track') }}" class="{{ $footerLinkClass }}">Track Your Order</a></li>
                    </ul>
                </nav>

                <nav aria-label="Customer Support" class="lg:col-span-3">
                    <h4 class="mb-4 text-xs font-bold uppercase tracking-[0.14em] text-white">Customer Support</h4>
                    <ul class="space-y-1">
                        <li><a href="{{ route('contact') }}" class="{{ $footerLinkClass }}">Contact Us</a></li>
                        <li><a href="{{ $returnPolicyUrl }}" class="{{ $footerLinkClass }}">Return &amp; Refund Policy</a></li>
                        <li><a href="{{ route('page.show', 'shipping-policy') }}" class="{{ $footerLinkClass }}">Shipping Policy</a></li>
                        <li><a href="{{ $business['privacy_policy_url'] ?: route('page.show', 'privacy-policy') }}" class="{{ $footerLinkClass }}">Privacy Policy</a></li>
                        <li><a href="{{ $business['terms_url'] ?: route('page.show', 'terms') }}" class="{{ $footerLinkClass }}">Terms &amp; Conditions</a></li>
                        <li><a href="{{ route('faq') }}" class="{{ $footerLinkClass }}">Frequently Asked Questions</a></li>
                    </ul>
                </nav>

                <nav aria-label="About" class="col-span-2 sm:col-span-1 lg:col-span-3">
                    <h4 class="mb-4 text-xs font-bold uppercase tracking-[0.14em] text-white">About</h4>
                    <ul class="space-y-1">
                        <li><a href="{{ route('about') }}" class="{{ $footerLinkClass }}">About Mama Bazar</a></li>
                        <li><a href="{{ route('team') }}" class="{{ $footerLinkClass }}">Our Team</a></li>
                        <li><a href="{{ route('faq') }}" class="{{ $footerLinkClass }}">Help Center</a></li>
                    </ul>
                    <a href="{{ route('track') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-brand-orange-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-brand-orange-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                        Track an order
                    </a>
                </nav>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-12 border-t border-white/10 bg-black/15">
            <div class="store-container flex flex-col items-center justify-between gap-4 py-5 text-xs text-brand-green-100/60 lg:flex-row">
                <p class="text-center lg:text-left">
                    {{ $business['copyright_rendered'] }} ·
                    <button type="button" onclick="window.mbConsentShow && window.mbConsentShow()" class="underline decoration-white/30 underline-offset-2 hover:text-white">Cookie settings</button>
                    <br class="sm:hidden">
                    <span class="mt-1 inline-block sm:ml-1 sm:mt-0">Crafted by &amp; Developed by <a href="https://bornosoft.bd/" target="_blank" rel="noopener noreferrer" class="font-semibold text-white underline decoration-white/30 underline-offset-2 transition hover:text-brand-orange-300">Bornosoft</a></span>
                </p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span class="mr-1 text-[10px] font-bold uppercase tracking-[0.15em] text-brand-green-100/60">We Accept</span>
                    @forelse(($footerPaymentMethods ?? collect())->reject(fn ($m) => $m->code === 'cod') as $pm)
                        @php
                            $style = match($pm->code) {
                                'bkash' => 'text-pink-700',
                                'nagad' => 'text-brand-orange-700',
                                'rocket' => 'text-violet-700',
                                'bank' => 'text-sky-700',
                                default => 'text-brand-green-700',
                            };
                        @endphp
                        <span class="rounded-md bg-white px-2 py-1 text-[10px] font-bold shadow-xs {{ $style }}">{{ $pm->name }}</span>
                    @empty
                        <span class="rounded-md bg-white px-2 py-1 text-[10px] font-bold text-brand-green-700 shadow-xs">Cash on Delivery</span>
                        <span class="rounded-md bg-white px-2 py-1 text-[10px] font-bold text-pink-700 shadow-xs">bKash</span>
                        <span class="rounded-md bg-white px-2 py-1 text-[10px] font-bold text-brand-orange-700 shadow-xs">Nagad</span>
                    @endforelse
                </div>
            </div>
            <div class="store-container pb-6">
                <a href="https://www.sslcommerz.com" target="_blank" rel="noopener noreferrer" title="Secured by SSLCommerz" class="mx-auto block w-full max-w-2xl overflow-hidden rounded-xl bg-white p-2 shadow-sm transition hover:shadow-md">
                    <img src="{{ asset('images/sslcommerz-pay-with-logo.png') }}" alt="SSLCommerz - Secure Payment Gateway" class="block w-full object-contain" loading="lazy">
                </a>
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
