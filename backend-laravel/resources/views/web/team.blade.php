@extends('layouts.app')

@section('content')
<div class="store-container py-10 sm:py-16 space-y-10" x-data="{
    activeTab: '{{ request('role', 'all') }}',
    search: '{{ request('q', '') }}',
    filterRole(role) {
        this.activeTab = role;
    }
}">
    <!-- Header Hero Section -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 rounded-full border border-brand-green-200 bg-brand-green-50/80 px-3.5 py-1 text-xs font-bold text-brand-green-800 shadow-xs">
            <span class="flex h-2 w-2 rounded-full bg-brand-green-500 animate-pulse"></span>
            <span>The People Behind {{ $business['business_name'] }}</span>
        </div>
        <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
            Meet Our Exceptional Team
        </h1>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-2xl mx-auto">
            From supply chain logistics to platform engineering and customer happiness, our passionate team works tirelessly to bring quality groceries and daily essentials directly to your doorstep.
        </p>

        <!-- Stats counter banner -->
        <div class="pt-4 flex flex-wrap items-center justify-center gap-6 text-slate-600">
            <div class="flex items-center gap-2 text-xs font-medium">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-green-100 text-brand-green-800 font-bold text-xs">{{ $members->count() }}</span>
                <span>Active Members</span>
            </div>
            <div class="h-3 w-px bg-slate-200 hidden sm:block"></div>
            <div class="flex items-center gap-2 text-xs font-medium">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-orange-100 text-brand-orange-800 font-bold text-xs">24/7</span>
                <span>Customer Care</span>
            </div>
            <div class="h-3 w-px bg-slate-200 hidden sm:block"></div>
            <div class="flex items-center gap-2 text-xs font-medium">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-800 font-bold text-xs">100%</span>
                <span>Dedicated to Quality</span>
            </div>
        </div>
    </div>

    <!-- Quick Role Filter Tabs & Search Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 pb-5">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="filterRole('all')"
                    :class="activeTab === 'all' ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    class="rounded-full px-4 py-1.5 text-xs font-bold transition">
                All Members ({{ $members->count() }})
            </button>
            <button type="button"
                    @click="filterRole('leadership')"
                    :class="activeTab === 'leadership' ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    class="rounded-full px-4 py-1.5 text-xs font-bold transition">
                Leadership &amp; Founders
            </button>
            <button type="button"
                    @click="filterRole('engineer')"
                    :class="activeTab === 'engineer' ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    class="rounded-full px-4 py-1.5 text-xs font-bold transition">
                Engineering &amp; Tech
            </button>
            <button type="button"
                    @click="filterRole('design')"
                    :class="activeTab === 'design' ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    class="rounded-full px-4 py-1.5 text-xs font-bold transition">
                Design &amp; Marketing
            </button>
        </div>

        <form action="{{ route('team') }}" method="GET" class="relative min-w-[240px]">
            <input type="text"
                   name="q"
                   x-model="search"
                   value="{{ request('q') }}"
                   placeholder="Search member or role…"
                   class="h-9 w-full rounded-full border border-slate-200 bg-white pl-9 pr-4 text-xs font-medium text-slate-700 placeholder:text-slate-400 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
            <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            @if(request('q'))
                <a href="{{ route('team') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">Clear</a>
            @endif
        </form>
    </div>

    <!-- Team Members Grid -->
    @if($members->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center max-w-xl mx-auto space-y-4">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-green-50 text-brand-green-600">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-slate-800">No Team Members Found</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    @if(request('q'))
                        No team member matches your search query "{{ request('q') }}". Try searching for another name or position.
                    @else
                        We are currently updating our team directory. Please check back soon or browse our online shop.
                    @endif
                </p>
            </div>
            <div class="pt-2">
                @if(request('q'))
                    <a href="{{ route('team') }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-green-600 px-5 py-2 text-xs font-bold text-white transition hover:bg-brand-green-700">
                        View All Members
                    </a>
                @else
                    <a href="{{ route('shop') }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-green-600 px-5 py-2 text-xs font-bold text-white transition hover:bg-brand-green-700">
                        Explore Our Store
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 sm:gap-8">
            @foreach($members as $member)
                @php
                    $roleNormalized = strtolower($member->position);
                    $memberCategory = 'other';
                    if (str_contains($roleNormalized, 'ceo') || str_contains($roleNormalized, 'founder') || str_contains($roleNormalized, 'coo') || str_contains($roleNormalized, 'director')) {
                        $memberCategory = 'leadership';
                    } elseif (str_contains($roleNormalized, 'engineer') || str_contains($roleNormalized, 'cto') || str_contains($roleNormalized, 'tech') || str_contains($roleNormalized, 'software')) {
                        $memberCategory = 'engineer';
                    } elseif (str_contains($roleNormalized, 'design') || str_contains($roleNormalized, 'ui') || str_contains($roleNormalized, 'ux') || str_contains($roleNormalized, 'marketing') || str_contains($roleNormalized, 'pm') || str_contains($roleNormalized, 'manager')) {
                        $memberCategory = 'design';
                    }
                @endphp
                <div x-show="activeTab === 'all' || activeTab === '{{ $memberCategory }}' || (activeTab === 'engineer' && '{{ $memberCategory }}' === 'leadership' && '{{ str_contains($roleNormalized, 'cto') ? '1' : '0' }}' === '1')"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-3"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-soft transition-all duration-300 hover:-translate-y-1.5 hover:border-brand-green-200 hover:shadow-xl">
                    
                    <!-- Card Top: Avatar & Badges -->
                    <div class="space-y-4">
                        <div class="relative mx-auto flex justify-center">
                            <!-- Glow effect on hover -->
                            <div class="absolute -inset-1 rounded-full bg-gradient-to-tr from-brand-green-400 to-brand-orange-400 opacity-0 blur-sm transition-opacity duration-300 group-hover:opacity-70"></div>
                            
                            <div class="relative h-28 w-28 overflow-hidden rounded-full ring-4 ring-white shadow-md bg-slate-50">
                                <img src="{{ $member->avatar_url }}"
                                     alt="{{ $member->name }}"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-108"
                                     loading="lazy">
                            </div>

                            @if($member->show_in_footer)
                                <span class="absolute bottom-0 right-1/4 rounded-full bg-brand-orange-500 p-1 text-white shadow-xs" title="Key Leadership Team Member">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <!-- Name & Position -->
                        <div class="text-center space-y-1.5">
                            <h3 class="text-base font-extrabold text-slate-900 group-hover:text-brand-green-700 transition">
                                {{ $member->name }}
                            </h3>
                            <div class="inline-block rounded-full bg-brand-green-50 px-3 py-1 text-[11px] font-bold text-brand-green-700 border border-brand-green-100">
                                {{ $member->position }}
                            </div>
                        </div>

                        <!-- Bio / Description -->
                        @if($member->bio)
                            <p class="text-xs text-slate-500 leading-relaxed text-center line-clamp-3 group-hover:line-clamp-none transition-all">
                                {{ $member->bio }}
                            </p>
                        @endif
                    </div>

                    <!-- Card Bottom: Email & Social Links -->
                    <div class="mt-5 pt-4 border-t border-slate-100 space-y-3">
                        @if($member->show_email_publicly && $member->email)
                            <a href="mailto:{{ $member->email }}"
                               class="flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-brand-green-700 transition"
                               title="Email {{ $member->name }}">
                                <svg class="h-3.5 w-3.5 shrink-0 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="truncate max-w-[200px]">{{ $member->email }}</span>
                            </a>
                        @endif

                        <!-- Social Icons -->
                        @php $socials = $member->social_links; @endphp
                        @if(!empty($socials) && count($socials) > 0)
                            <div class="flex items-center justify-center gap-2">
                                @if(!empty($socials['linkedin']))
                                    <a href="{{ $socials['linkedin'] }}" target="_blank" rel="noopener noreferrer"
                                       class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-[#0A66C2] hover:text-white"
                                       aria-label="LinkedIn">
                                        <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.738-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                    </a>
                                @endif
                                @if(!empty($socials['twitter']) || !empty($socials['x']))
                                    <a href="{{ $socials['twitter'] ?? $socials['x'] }}" target="_blank" rel="noopener noreferrer"
                                       class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-black hover:text-white"
                                       aria-label="X (Twitter)">
                                        <svg class="h-3 w-3 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                    </a>
                                @endif
                                @if(!empty($socials['github']))
                                    <a href="{{ $socials['github'] }}" target="_blank" rel="noopener noreferrer"
                                       class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-900 hover:text-white"
                                       aria-label="GitHub">
                                        <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                                    </a>
                                @endif
                                @if(!empty($socials['website']))
                                    <a href="{{ $socials['website'] }}" target="_blank" rel="noopener noreferrer"
                                       class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-brand-green-600 hover:text-white"
                                       aria-label="Personal Website">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Join Us Callout Card -->
    <div class="rounded-3xl bg-gradient-to-r from-brand-green-800 via-brand-green-900 to-slate-900 p-8 sm:p-12 text-white shadow-xl">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center lg:text-left max-w-xl">
                <span class="inline-block rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-brand-green-200">We're Growing!</span>
                <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Want to Join the Mama Bazar Family?</h3>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    We are always looking for driven innovators, logistics masters, and passionate individuals to reshape e-commerce in Bangladesh.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-full bg-brand-orange-500 px-6 py-3 text-xs font-bold text-white shadow-lg transition hover:bg-brand-orange-600 hover:scale-102">
                    <span>Contact Us</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="{{ route('about') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-5 py-3 text-xs font-semibold text-white transition hover:bg-white/20">
                    <span>Learn More About Us</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
