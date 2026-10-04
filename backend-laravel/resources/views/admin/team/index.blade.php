@extends('layouts.admin', ['headerTitle' => 'Team Management'])

@section('content')
<div class="admin-page space-y-6" x-data="teamManager()" x-init="init()">
    <!-- Page Header -->
    <x-admin.page-header title="Team Management" subtitle="Manage public team members, roles, drag-and-drop order, and website footer integration">
        <x-slot:actions>
            <!-- Settings Modal Button -->
            <button type="button" @click="settingsModalOpen = true" class="admin-btn admin-btn--outline admin-btn--sm flex items-center gap-1.5">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Footer Settings</span>
            </button>

            <!-- Public Team Page Link -->
            <a href="{{ route('team') }}" target="_blank" rel="noopener" class="admin-btn admin-btn--outline admin-btn--sm flex items-center gap-1.5">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                <span>View Public Page</span>
            </a>

            <!-- Add Member Button -->
            <x-admin.button type="button" size="sm" @click="openCreateModal()">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Team Member</span>
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <!-- Success & Error Alerts -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please check the form for errors:</p>
            <ul class="mt-1 list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Members</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-extrabold text-slate-900">{{ $totalCount }}</div>
            <p class="mt-0.5 text-[11px] text-slate-400">In company directory</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Active</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-extrabold text-emerald-700">{{ $activeCount }}</div>
            <p class="mt-0.5 text-[11px] text-slate-400">Currently active status</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-brand-orange-700 uppercase tracking-wider">In Footer</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-orange-50 text-brand-orange-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-extrabold text-brand-orange-600">{{ $footerCount }}</div>
            <p class="mt-0.5 text-[11px] text-slate-400">Featured in site footer</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-sky-700 uppercase tracking-wider">Public Page</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-extrabold text-sky-700">{{ $publicCount }}</div>
            <p class="mt-0.5 text-[11px] text-slate-400">Visible on /team</p>
        </div>
    </div>

    <!-- Reorder Status Notification Banner -->
    <div x-show="reorderSaving" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-800 flex items-center gap-2">
        <svg class="h-4 w-4 animate-spin text-amber-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
        <span>Saving new team member order…</span>
    </div>
    <div x-show="reorderSaved" x-cloak class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-800 flex items-center gap-2">
        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>Display order saved successfully!</span>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs space-y-3">
        <form action="{{ route('admin.team.index') }}" method="GET" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex-1 flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative min-w-[220px] flex-1 max-w-sm">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search by name, email, role…"
                           class="admin-control w-full pl-9 text-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Status Filter -->
                <select name="status" class="admin-control text-xs" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>

                <!-- Footer Filter -->
                <select name="footer" class="admin-control text-xs" onchange="this.form.submit()">
                    <option value="">All Footer Options</option>
                    <option value="yes" {{ request('footer') === 'yes' ? 'selected' : '' }}>In Footer Only</option>
                    <option value="no" {{ request('footer') === 'no' ? 'selected' : '' }}>Not In Footer</option>
                </select>

                <!-- Public Filter -->
                <select name="public" class="admin-control text-xs" onchange="this.form.submit()">
                    <option value="">All Visibility</option>
                    <option value="yes" {{ request('public') === 'yes' ? 'selected' : '' }}>Public Only</option>
                    <option value="no" {{ request('public') === 'no' ? 'selected' : '' }}>Hidden Only</option>
                </select>

                @if(request()->hasAny(['search', 'status', 'footer', 'public']))
                    <a href="{{ route('admin.team.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 py-1.5 px-2">
                        Reset Filters
                    </a>
                @endif
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-1 border-t md:border-t-0 pt-2 md:pt-0">
                <button type="button"
                        @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 hover:text-slate-600'"
                        class="rounded-lg p-1.5 text-xs flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <span class="hidden sm:inline">Table</span>
                </button>
                <button type="button"
                        @click="viewMode = 'cards'"
                        :class="viewMode === 'cards' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-400 hover:text-slate-600'"
                        class="rounded-lg p-1.5 text-xs flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span class="hidden sm:inline">Cards</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Empty State -->
    @if($members->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center max-w-lg mx-auto space-y-3">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Team Members Found</h3>
            <p class="text-xs text-slate-500">
                @if(request()->hasAny(['search', 'status', 'footer', 'public']))
                    No members match the current filter criteria.
                @else
                    Start by adding your first leadership or company team member.
                @endif
            </p>
            <div class="pt-2">
                <x-admin.button type="button" size="sm" @click="openCreateModal()">
                    Add First Member
                </x-admin.button>
            </div>
        </div>
    @else
        <!-- Sortable Hint Banner -->
        <div class="flex items-center justify-between text-[11px] text-slate-500 px-1">
            <div class="flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                <span>Drag rows using the handle <strong class="text-slate-700">:::</strong> to instantly reorder. Order saves automatically.</span>
            </div>
            <span class="font-bold text-slate-700">{{ $members->count() }} member(s) listed</span>
        </div>

        <!-- Table View -->
        <div x-show="viewMode === 'table'" class="rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3 text-center">#</th>
                            <th class="px-4 py-3">Member</th>
                            <th class="px-4 py-3">Position / Role</th>
                            <th class="px-3 py-3 text-center">Footer</th>
                            <th class="px-3 py-3 text-center">Public</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teamSortableTable" class="divide-y divide-slate-100">
                        @foreach($members as $m)
                            <tr data-id="{{ $m->id }}" class="group hover:bg-slate-50/80 transition-colors">
                                <!-- Drag Handle & Order -->
                                <td class="px-3 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <div class="drag-handle cursor-grab active:cursor-grabbing p-1 text-slate-300 hover:text-slate-600 rounded" title="Drag to reorder">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                                        </div>
                                        <span class="order-badge font-mono text-[10px] text-slate-400 w-4 text-center">{{ $loop->iteration }}</span>
                                    </div>
                                </td>

                                <!-- Member Profile Info -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $m->avatar_url }}" alt="{{ $m->name }}" class="h-10 w-10 shrink-0 rounded-full object-cover border border-slate-200">
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 truncate">{{ $m->name }}</div>
                                            <div class="text-[11px] text-slate-400 truncate flex items-center gap-1.5">
                                                <span>{{ $m->email }}</span>
                                                @if($m->show_email_publicly)
                                                    <span class="inline-block rounded bg-sky-50 px-1 py-0.2 text-[9px] font-bold text-sky-700" title="Email is displayed on public cards">Public Email</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Position / Role Badge -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-block rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-800">
                                        {{ $m->position }}
                                    </span>
                                </td>

                                <!-- Show in Footer Toggle Button -->
                                <td class="px-3 py-3.5 text-center">
                                    <button type="button"
                                            @click="toggleFooter({{ $m->id }}, $event)"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold transition {{ $m->show_in_footer ? 'bg-brand-orange-50 text-brand-orange-700 border border-brand-orange-200 hover:bg-brand-orange-100' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' }}">
                                        @if($m->show_in_footer)
                                            <svg class="h-3 w-3 text-brand-orange-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            <span>Featured</span>
                                        @else
                                            <span>No</span>
                                        @endif
                                    </button>
                                </td>

                                <!-- Public Visibility Toggle Button -->
                                <td class="px-3 py-3.5 text-center">
                                    <button type="button"
                                            @click="togglePublic({{ $m->id }}, $event)"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold transition {{ $m->is_public ? 'bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' }}">
                                        <span>{{ $m->is_public ? 'Visible' : 'Hidden' }}</span>
                                    </button>
                                </td>

                                <!-- Active Status Toggle Button -->
                                <td class="px-3 py-3.5 text-center">
                                    <button type="button"
                                            @click="toggleStatus({{ $m->id }}, $event)"
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold transition {{ $m->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $m->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        <span>{{ $m->is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                @click="openEditModal({{ json_encode($m) }})"
                                                class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition"
                                                title="Edit Member">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button"
                                                @click="confirmDelete({{ $m->id }}, '{{ addslashes($m->name) }}')"
                                                class="rounded-lg p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition"
                                                title="Delete Member">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cards View -->
        <div x-show="viewMode === 'cards'" id="teamSortableCards" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($members as $m)
                <div data-id="{{ $m->id }}" class="group relative rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs transition-all hover:border-brand-green-300 hover:shadow-md flex flex-col justify-between">
                    <div>
                        <!-- Top Row: Drag Handle & Badges -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="drag-handle cursor-grab active:cursor-grabbing p-1 text-slate-300 hover:text-slate-600 rounded flex items-center gap-1" title="Drag to reorder">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                                <span class="order-badge font-mono text-[10px] text-slate-400">#{{ $loop->iteration }}</span>
                            </div>

                            <div class="flex items-center gap-1">
                                @if($m->show_in_footer)
                                    <span class="inline-block rounded-full bg-brand-orange-50 px-2 py-0.5 text-[9px] font-bold text-brand-orange-700 border border-brand-orange-200">Footer</span>
                                @endif
                                <span class="inline-block rounded-full {{ $m->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }} px-2 py-0.5 text-[9px] font-bold">
                                    {{ $m->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <!-- Avatar & Info -->
                        <div class="text-center space-y-2">
                            <img src="{{ $m->avatar_url }}" alt="{{ $m->name }}" class="mx-auto h-20 w-20 rounded-full object-cover border-2 border-white shadow-sm ring-1 ring-slate-200">
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900 truncate">{{ $m->name }}</h4>
                                <div class="mt-1 inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">
                                    {{ $m->position }}
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 truncate">{{ $m->email }}</p>
                            @if($m->bio)
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed pt-1">{{ $m->bio }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Card Actions Bottom -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <button type="button" @click="toggleStatus({{ $m->id }}, $event)" class="text-[11px] font-semibold text-slate-500 hover:text-slate-800">
                            {{ $m->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="openEditModal({{ json_encode($m) }})" class="p-1 rounded text-slate-400 hover:text-slate-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button type="button" @click="confirmDelete({{ $m->id }}, '{{ addslashes($m->name) }}')" class="p-1 rounded text-rose-400 hover:text-rose-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Add / Edit Member Modal -->
    <x-admin.modal name="memberModalOpen" title="Team Member Profile" subtitle="Enter details, upload a profile photo, assign positions, and configure visibility.">
        <form :action="formAction" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <template x-if="isEditing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <!-- Image Uploader with Instant Preview -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Profile Photo</label>
                <div class="flex items-center gap-4">
                    <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-50 shadow-inner">
                        <img :src="imagePreview || currentImageUrl || 'https://ui-avatars.com/api/?name=Team+Member&color=0F4D2C&background=E8F5E9&bold=true'"
                             alt="Preview"
                             class="h-full w-full object-cover">
                    </div>
                    <div class="flex-1 space-y-1.5">
                        <input type="file"
                               name="image"
                               accept="image/jpeg,image/png,image/webp,image/jpg"
                               @change="handleImageChange($event)"
                               id="memberImageInput"
                               class="hidden">
                        <div class="flex flex-wrap items-center gap-2">
                            <label for="memberImageInput" class="admin-btn admin-btn--outline admin-btn--sm cursor-pointer">
                                <svg class="mr-1 h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span x-text="imagePreview || currentImageUrl ? 'Change Photo' : 'Upload Photo'"></span>
                            </label>
                            <button type="button"
                                    x-show="imagePreview || currentImageUrl"
                                    @click="removePhoto()"
                                    class="admin-btn admin-btn--sm text-rose-600 hover:bg-rose-50 border border-rose-200">
                                Remove
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400">JPEG, PNG, or WebP. Max 5MB. Square aspect recommended.</p>
                        <input type="hidden" name="remove_image" :value="removeImageFlag ? '1' : '0'">
                    </div>
                </div>
            </div>

            <!-- Full Name & Email -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Full Name *</label>
                    <input type="text"
                           name="name"
                           x-model="form.name"
                           required
                           placeholder="e.g. Mohammad Ali Nayeem"
                           class="admin-control w-full text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address *</label>
                    <input type="email"
                           name="email"
                           x-model="form.email"
                           required
                           placeholder="member@mama-bazar.com"
                           class="admin-control w-full text-xs">
                </div>
            </div>

            <!-- Position / Role Input with Suggestions -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold text-slate-700">Position / Role *</label>
                    <span class="text-[10px] text-slate-400">Type custom or select suggestion</span>
                </div>
                <input type="text"
                       name="position"
                       x-model="form.position"
                       required
                       placeholder="e.g. Founder & CEO or Lead Software Engineer"
                       class="admin-control w-full text-xs">

                <!-- Quick suggestion pills -->
                <div class="mt-1.5 flex flex-wrap gap-1">
                    @foreach($predefinedPositions as $p)
                        <button type="button"
                                @click="form.position = '{{ addslashes($p) }}'"
                                class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 hover:bg-brand-green-100 hover:text-brand-green-800 transition">
                            {{ $p }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Bio / Short Description -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Bio / Profile Description</label>
                <textarea name="bio"
                          x-model="form.bio"
                          rows="3"
                          placeholder="Brief description of the member's leadership role, expertise, or background…"
                          class="admin-control w-full text-xs leading-relaxed"></textarea>
            </div>

            <!-- Social Links (Optional) -->
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-3 space-y-2">
                <span class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Social Links (Optional)</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">LinkedIn Profile</label>
                        <input type="url" name="social_linkedin" x-model="form.social_linkedin" placeholder="https://linkedin.com/in/username" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Twitter / X Profile</label>
                        <input type="url" name="social_twitter" x-model="form.social_twitter" placeholder="https://x.com/username" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">GitHub Profile</label>
                        <input type="url" name="social_github" x-model="form.social_github" placeholder="https://github.com/username" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Personal Website</label>
                        <input type="url" name="social_website" x-model="form.social_website" placeholder="https://example.com" class="admin-control w-full text-xs">
                    </div>
                </div>
            </div>

            <!-- Visibility & Status Toggles -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <label class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 p-3 cursor-pointer hover:bg-slate-50 transition">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                    <div>
                        <div class="text-xs font-bold text-slate-800">Active Member</div>
                        <div class="text-[10px] text-slate-500">Uncheck to temporarily deactivate</div>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 p-3 cursor-pointer hover:bg-slate-50 transition">
                    <input type="checkbox" name="is_public" value="1" x-model="form.is_public" class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                    <div>
                        <div class="text-xs font-bold text-slate-800">Public Team Page</div>
                        <div class="text-[10px] text-slate-500">Show profile on the public /team page</div>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 p-3 cursor-pointer hover:bg-slate-50 transition">
                    <input type="checkbox" name="show_in_footer" value="1" x-model="form.show_in_footer" class="h-4 w-4 rounded text-brand-orange-500 focus:ring-brand-orange-500">
                    <div>
                        <div class="text-xs font-bold text-slate-800">Show in Website Footer</div>
                        <div class="text-[10px] text-slate-500">Feature member in the homepage &amp; layout footer</div>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 p-3 cursor-pointer hover:bg-slate-50 transition">
                    <input type="checkbox" name="show_email_publicly" value="1" x-model="form.show_email_publicly" class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                    <div>
                        <div class="text-xs font-bold text-slate-800">Show Email Publicly</div>
                        <div class="text-[10px] text-slate-500">Display email address on public profile card</div>
                    </div>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="memberModalOpen = false">
                    Cancel
                </x-admin.button>
                <x-admin.button type="submit" size="sm">
                    <span x-text="isEditing ? 'Save Changes' : 'Create Team Member'"></span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Footer Settings Modal -->
    <x-admin.modal name="settingsModalOpen" title="Website Footer Team Section" subtitle="Configure whether the Leadership & Team section appears on website footers">
        <form action="{{ route('admin.team.settings.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 cursor-pointer bg-slate-50/50 hover:bg-slate-50">
                    <input type="checkbox" name="footer_team_enabled" value="1" {{ $footerTeamEnabled ? 'checked' : '' }} class="h-5 w-5 rounded text-brand-green-600 focus:ring-brand-green-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900">Enable Team Section in Website Footer</span>
                        <span class="block text-[11px] text-slate-500">When enabled, members marked "Show in Website Footer" are displayed across all website page footers.</span>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Section Title</label>
                <input type="text" name="footer_team_title" value="{{ $footerTeamTitle }}" placeholder="Leadership & Core Team" class="admin-control w-full text-xs">
                <p class="mt-1 text-[11px] text-slate-400">Heading displayed right above the footer team profiles.</p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="settingsModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Footer Settings</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal name="deleteModalOpen" title="Delete Team Member" subtitle="Are you sure you want to delete this team member? This action cannot be undone.">
        <form :action="deleteAction" method="POST">
            @csrf
            @method('DELETE')
            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 text-xs text-rose-800">
                You are about to delete <strong x-text="deleteMemberName"></strong>. Their profile and uploaded photos will be permanently removed from the system.
            </div>
            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="deleteModalOpen = false">Cancel</x-admin.button>
                <button type="submit" class="admin-btn admin-btn--sm bg-rose-600 text-white hover:bg-rose-700 shadow-xs">
                    Yes, Delete Member
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function teamManager() {
    return {
        viewMode: 'table',
        memberModalOpen: false,
        settingsModalOpen: false,
        deleteModalOpen: false,
        isEditing: false,
        formAction: '{{ route('admin.team.store') }}',
        deleteAction: '',
        deleteMemberName: '',
        imagePreview: null,
        currentImageUrl: null,
        removeImageFlag: false,
        reorderSaving: false,
        reorderSaved: false,
        form: {
            id: null,
            name: '',
            email: '',
            position: '',
            bio: '',
            is_active: true,
            is_public: true,
            show_in_footer: false,
            show_email_publicly: false,
            social_linkedin: '',
            social_twitter: '',
            social_github: '',
            social_website: '',
        },

        init() {
            this.$nextTick(() => {
                this.initSortable();
            });
        },

        initSortable() {
            const tableEl = document.getElementById('teamSortableTable');
            if (tableEl && !tableEl._sortableInitialized) {
                tableEl._sortableInitialized = true;
                Sortable.create(tableEl, {
                    handle: '.drag-handle',
                    animation: 200,
                    ghostClass: 'bg-emerald-50/80',
                    chosenClass: 'bg-emerald-100/50',
                    dragClass: 'shadow-lg',
                    onEnd: () => {
                        this.saveOrder(tableEl);
                    }
                });
            }

            const cardsEl = document.getElementById('teamSortableCards');
            if (cardsEl && !cardsEl._sortableInitialized) {
                cardsEl._sortableInitialized = true;
                Sortable.create(cardsEl, {
                    handle: '.drag-handle',
                    animation: 200,
                    ghostClass: 'bg-emerald-50/80',
                    chosenClass: 'ring-2 ring-emerald-500',
                    dragClass: 'shadow-2xl',
                    onEnd: () => {
                        this.saveOrder(cardsEl);
                    }
                });
            }
        },

        saveOrder(containerEl) {
            const rows = containerEl.querySelectorAll('[data-id]');
            const orderIds = Array.from(rows).map(el => parseInt(el.getAttribute('data-id'), 10));

            // Update badge numbers immediately
            rows.forEach((el, index) => {
                const badge = el.querySelector('.order-badge');
                if (badge) {
                    badge.textContent = badge.textContent.startsWith('#') ? '#' + (index + 1) : (index + 1);
                }
            });

            this.reorderSaving = true;
            this.reorderSaved = false;

            fetch('{{ route('admin.team.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ order: orderIds }),
            })
            .then(res => res.json())
            .then(data => {
                this.reorderSaving = false;
                if (data.success) {
                    this.reorderSaved = true;
                    setTimeout(() => { this.reorderSaved = false; }, 3000);
                } else {
                    alert(data.message || 'Error updating team order.');
                }
            })
            .catch(() => {
                this.reorderSaving = false;
                alert('Network error while saving order.');
            });
        },

        openCreateModal() {
            this.isEditing = false;
            this.formAction = '{{ route('admin.team.store') }}';
            this.imagePreview = null;
            this.currentImageUrl = null;
            this.removeImageFlag = false;
            this.form = {
                id: null,
                name: '',
                email: '',
                position: '',
                bio: '',
                is_active: true,
                is_public: true,
                show_in_footer: false,
                show_email_publicly: false,
                social_linkedin: '',
                social_twitter: '',
                social_github: '',
                social_website: '',
            };
            this.memberModalOpen = true;
        },

        openEditModal(member) {
            this.isEditing = true;
            this.formAction = '/admin/team/' + member.id;
            this.imagePreview = null;
            this.currentImageUrl = member.avatar_url;
            this.removeImageFlag = false;
            const socials = member.social_links || {};
            this.form = {
                id: member.id,
                name: member.name,
                email: member.email,
                position: member.position,
                bio: member.bio || '',
                is_active: !!member.is_active,
                is_public: !!member.is_public,
                show_in_footer: !!member.show_in_footer,
                show_email_publicly: !!member.show_email_publicly,
                social_linkedin: socials.linkedin || '',
                social_twitter: socials.twitter || '',
                social_github: socials.github || '',
                social_website: socials.website || '',
            };
            this.memberModalOpen = true;
        },

        confirmDelete(id, name) {
            this.deleteAction = '/admin/team/' + id;
            this.deleteMemberName = name;
            this.deleteModalOpen = true;
        },

        handleImageChange(e) {
            const file = e.target.files[0];
            if (file) {
                this.removeImageFlag = false;
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.imagePreview = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        removePhoto() {
            this.imagePreview = null;
            this.currentImageUrl = null;
            this.removeImageFlag = true;
            const input = document.getElementById('memberImageInput');
            if (input) input.value = '';
        },

        toggleStatus(id, event) {
            fetch('/admin/team/' + id + '/toggle-status', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        toggleFooter(id, event) {
            fetch('/admin/team/' + id + '/toggle-footer', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        togglePublic(id, event) {
            fetch('/admin/team/' + id + '/toggle-public', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }
    };
}
</script>
@endpush
@endsection
