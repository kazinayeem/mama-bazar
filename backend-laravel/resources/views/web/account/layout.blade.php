@extends('layouts.app')

@php
    $seo = \App\Services\SeoService::getForPrivate('Customer Account');
    $accountUser = $user ?? auth()->user();
@endphp

@section('content')
<div class="bg-slate-50/60 min-h-screen py-6 sm:py-8">
    <div class="store-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Breadcrumbs --}}
        <nav class="flex items-center text-xs font-semibold text-slate-500 gap-2" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-brand-green-700 transition">Home</a>
            <svg class="h-3 w-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('account.dashboard') }}" class="hover:text-brand-green-700 transition">My Account</a>
            @hasSection('account-title')
                <svg class="h-3 w-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-800 font-bold">@yield('account-title')</span>
            @endif
        </nav>

        {{-- Customer Profile Top Header Banner --}}
        <div class="relative overflow-hidden rounded-2xl border border-brand-green-100 bg-white p-4 sm:p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                    <div class="relative flex h-12 w-12 sm:h-16 sm:w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-brand-green-600 to-brand-green-500 text-lg sm:text-2xl font-black text-white shadow-md shadow-brand-green-500/20">
                        {{ strtoupper(substr($accountUser?->name ?? 'U', 0, 1)) }}
                        <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 sm:h-4 sm:w-4 rounded-full border-2 border-white bg-emerald-500" title="Account Active"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-base sm:text-xl font-extrabold text-slate-900 truncate tracking-tight">
                                {{ $accountUser?->name ?? 'Customer Account' }}
                            </h1>
                            @if(in_array(($accountUser?->role ?? 'customer'), ['admin', 'super_admin', 'manager', 'staff', 'editor']) || ($accountUser?->custom_role ?? null))
                                <span class="rounded-full bg-brand-green-100 px-2.5 py-0.5 text-[11px] font-bold text-brand-green-800">Staff / Admin</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">Customer</span>
                            @endif
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                            @if($accountUser?->email)
                                <span class="flex items-center gap-1.5 truncate max-w-full">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span class="truncate">{{ $accountUser->email }}</span>
                                    @if($accountUser->email_verified_at)
                                        <svg class="h-3.5 w-3.5 shrink-0 text-brand-green-600" title="Verified Email" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    @endif
                                </span>
                            @endif
                            @if($accountUser?->phone)
                                <span class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span>{{ $accountUser->phone }}</span>
                                </span>
                            @endif
                            <span class="flex items-center gap-1.5 text-slate-400">
                                <span>Member since {{ $accountUser?->created_at?->format('M Y') ?? '2026' }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action shortcuts --}}
                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <a href="{{ route('track') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50 transition">
                        <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Track Order</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50/60 px-3 py-2 text-xs font-semibold text-red-700 shadow-xs hover:bg-red-100 transition">
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Mobile & Tablet Horizontal Navigation Bar (Visible < lg) --}}
        <div class="lg:hidden">
            <nav class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs font-bold" aria-label="Account Mobile Navigation">
                <a href="{{ route('account.dashboard') }}"
                   class="whitespace-nowrap rounded-xl px-3.5 py-2 transition shrink-0 {{ request()->routeIs('account.dashboard') ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    Dashboard
                </a>
                <a href="{{ route('account.orders') }}"
                   class="whitespace-nowrap rounded-xl px-3.5 py-2 transition shrink-0 {{ request()->routeIs('account.orders*') ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    My Orders
                </a>
                <a href="{{ route('account.addresses') }}"
                   class="whitespace-nowrap rounded-xl px-3.5 py-2 transition shrink-0 {{ request()->routeIs('account.addresses*') ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    Saved Addresses
                </a>
                <a href="{{ route('account.profile') }}"
                   class="whitespace-nowrap rounded-xl px-3.5 py-2 transition shrink-0 {{ request()->routeIs('account.profile') ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    Profile
                </a>
                <a href="{{ route('account.settings') }}"
                   class="whitespace-nowrap rounded-xl px-3.5 py-2 transition shrink-0 {{ request()->routeIs('account.settings') ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    Security
                </a>
                <a href="{{ route('account.email') }}"
                   class="whitespace-nowrap rounded-xl px-3.5 py-2 transition shrink-0 {{ request()->routeIs('account.email*') ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    Email Preferences
                </a>
                @if(in_array(($accountUser?->role ?? 'customer'), ['admin', 'super_admin', 'manager', 'staff', 'editor']) || ($accountUser?->custom_role ?? null))
                    <a href="{{ route('admin.dashboard') }}"
                       class="whitespace-nowrap rounded-xl px-3.5 py-2 transition shrink-0 bg-brand-green-50 text-brand-green-800 border border-brand-green-200 hover:bg-brand-green-100">
                        Admin Panel
                    </a>
                @endif
            </nav>
        </div>

        {{-- Layout Content: Sidebar + Main Workspace --}}
        <div class="account-layout">

            {{-- Sidebar Navigation (Desktop only: lg:block) --}}
            <aside class="account-sidebar hidden lg:block space-y-4">
                {{-- Navigation Card --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-3 shadow-xs">
                    <div class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Account Menu</div>
                    <nav class="space-y-1">
                        {{-- Dashboard --}}
                        <a href="{{ route('account.dashboard') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold transition {{ request()->routeIs('account.dashboard') ? 'bg-brand-green-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('account.dashboard') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Dashboard Overview</span>
                        </a>

                        {{-- My Orders --}}
                        <a href="{{ route('account.orders') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold transition {{ request()->routeIs('account.orders*') ? 'bg-brand-green-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('account.orders*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span>My Orders</span>
                        </a>

                        {{-- Addresses --}}
                        <a href="{{ route('account.addresses') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold transition {{ request()->routeIs('account.addresses*') ? 'bg-brand-green-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('account.addresses*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Saved Addresses</span>
                        </a>

                        {{-- Profile --}}
                        <a href="{{ route('account.profile') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold transition {{ request()->routeIs('account.profile') ? 'bg-brand-green-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('account.profile') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Personal Profile</span>
                        </a>

                        {{-- Security & Password --}}
                        <a href="{{ route('account.settings') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold transition {{ request()->routeIs('account.settings') ? 'bg-brand-green-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('account.settings') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Security & Password</span>
                        </a>

                        {{-- Email Preferences --}}
                        <a href="{{ route('account.email') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold transition {{ request()->routeIs('account.email*') ? 'bg-brand-green-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('account.email*') ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Email Preferences</span>
                        </a>

                        @if(in_array(($accountUser?->role ?? 'customer'), ['admin', 'super_admin', 'manager', 'staff', 'editor']) || ($accountUser?->custom_role ?? null))
                            <div class="pt-2 border-t border-slate-100 my-1"></div>
                            <a href="{{ route('admin.dashboard') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-brand-green-700 bg-brand-green-50/60 hover:bg-brand-green-100/70 transition">
                                <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>Go to Admin Panel</span>
                            </a>
                        @endif
                    </nav>
                </div>

                {{-- Helpline Widget --}}
                <div class="rounded-2xl border border-brand-green-100 bg-brand-green-50/60 p-4 text-xs text-brand-green-900 space-y-1.5">
                    <p class="font-bold flex items-center gap-1.5 text-brand-green-800">
                        <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Need Help With An Order?
                    </p>
                    <p class="text-[11px] text-brand-green-700/80 leading-relaxed">
                        Our customer service team is available Saturday to Thursday to support your shopping experience.
                    </p>
                    <a href="{{ route('contact') }}" class="inline-block mt-1 font-bold text-brand-green-800 hover:underline text-[11px]">
                        Contact Support &rarr;
                    </a>
                </div>
            </aside>

            {{-- Main Content Area --}}
            <main class="account-main space-y-6">

                {{-- Flash Success / Error Messages --}}
                @if(session('success'))
                    <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 shadow-xs" role="alert">
                        <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <div class="flex-1">{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-800 shadow-xs" role="alert">
                        <svg class="h-5 w-5 shrink-0 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <div class="flex-1">{{ session('error') }}</div>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-medium text-red-800 space-y-1 shadow-xs">
                        <div class="font-bold flex items-center gap-1.5 text-red-900">
                            <svg class="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Please correct the errors below:
                        </div>
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('account-content')
            </main>
        </div>
    </div>
</div>
@endsection
