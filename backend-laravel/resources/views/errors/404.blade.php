@extends('layouts.app', [
    'title' => 'Page Not Found (404) — Mama Bazar',
    'seo' => [
        'title' => 'Page Not Found (404) — Mama Bazar',
        'meta_description' => 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable. Browse Mama Bazar products.',
        'robots' => 'noindex, follow',
    ]
])

@section('content')
<main class="store-container py-12 sm:py-20 flex flex-col items-center justify-center text-center">
    <div class="max-w-lg space-y-6">
        {{-- Visual 404 Badge --}}
        <div class="relative mx-auto flex h-28 w-28 items-center justify-center rounded-3xl bg-brand-green-50 text-brand-green-600 shadow-soft">
            <span class="text-4xl font-black tracking-tight">404</span>
            <div class="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-full bg-brand-orange-500 text-white shadow-md">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>

        {{-- Headings --}}
        <div class="space-y-2">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Page Not Found
            </h1>
            <p class="text-sm sm:text-base text-slate-600">
                দুঃখিত! আপনি যে পৃষ্ঠাটি খুঁজছেন তা পাওয়া যায়নি অথবা সরানো হয়েছে।
            </p>
            <p class="text-xs text-slate-400">
                The link you followed may be broken or the product/page may have been removed.
            </p>
        </div>

        {{-- In-page Product Search --}}
        <div class="pt-2">
            <form action="{{ route('shop') }}" method="GET" class="relative max-w-md mx-auto" role="search">
                <input type="search" name="q" placeholder="Search products, groceries, electronics…"
                       class="h-11 w-full rounded-xl border border-brand-green-200 bg-white py-2 pl-10 pr-24 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100 shadow-sm" />
                <div class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg bg-brand-green-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-brand-green-700 transition">
                    Search
                </button>
            </form>
        </div>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap items-center justify-center gap-3 pt-3">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-green-700 px-5 py-2.5 text-xs font-bold text-white shadow-soft transition hover:bg-brand-green-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Back to Homepage
            </a>
            <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">
                <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Browse Shop
            </a>
            <a href="{{ route('track') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:border-slate-300">
                <svg class="h-4 w-4 text-brand-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Track Order
            </a>
        </div>

        {{-- Popular Categories / Shortcuts --}}
        <div class="pt-6 border-t border-slate-100 space-y-2">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Popular Destinations</p>
            <div class="flex flex-wrap items-center justify-center gap-2">
                @php
                    $popularCats = \App\Models\Category::where('status', 'active')->orderBy('name')->take(6)->get();
                @endphp
                @foreach($popularCats as $cat)
                    <a href="{{ route('shop', ['category' => $cat->slug]) }}"
                       class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-brand-green-50 hover:text-brand-green-700 transition">
                        {{ $cat->name }}
                    </a>
                @endforeach
                <a href="{{ route('contact') }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-brand-green-50 hover:text-brand-green-700 transition">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</main>
@endsection
