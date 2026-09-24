<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headerTitle ?? ($title ?? 'Dashboard') }} — Mama Bazar Admin</title>
    <link rel="icon" type="image/png" href="/brandlogo.png">
    <link rel="preload" href="/fonts/inter-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell flex h-screen overflow-hidden font-body antialiased"
      x-data="adminShell()"
      x-init="init()"
      @keydown.window="onKey($event)"
      @keydown.escape.window="mobileOpen = false">

    @php
        $adminNavSections = \App\Support\AdminNav::sections();
        $allAdminNavItems = \App\Support\AdminNav::allItems();
    @endphp

    {{-- Mobile backdrop (<768px) --}}
    <div x-show="mobileOpen" x-cloak
         class="fixed inset-0 z-[200] bg-black/50 md:hidden"
         @click="mobileOpen = false"
         x-transition.opacity></div>

    {{-- Sidebar: ~248px desktop, drawer on mobile, optional icons-only collapse --}}
    <aside class="admin-sidebar fixed inset-y-0 left-0 z-[201] transition-transform duration-200 ease-out md:static md:translate-x-0"
           :class="[
               mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
               collapsed ? 'is-collapsed' : '',
           ]"
           :style="collapsed ? 'width: var(--admin-sidebar-collapsed)' : 'width: var(--admin-sidebar-w)'"
           aria-label="Admin navigation">

        {{-- Brand header (~64px) --}}
        <div class="admin-sidebar__brand relative">
            <button type="button" @click="mobileOpen = false"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-[6px] p-1.5 text-slate-400 hover:bg-slate-100 md:hidden"
                    aria-label="Close sidebar">
                <svg width="16" height="16" class="admin-sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="admin-sidebar__logo">
                <x-admin.icon name="store" :size="18" />
            </div>
            <div class="min-w-0" x-show="!collapsed">
                <p class="truncate text-sm font-bold leading-tight text-slate-900">MamaBazar</p>
                <div class="mt-0.5 flex items-center gap-1.5">
                    @php $isSuper = in_array(auth()->user()->role ?? '', ['super_admin', 'admin']); @endphp
                    <span class="inline-block h-1.5 w-1.5 rounded-full {{ $isSuper ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                    <p class="truncate text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        {{ auth()->user()->custom_role ?? auth()->user()->role ?? 'Admin' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="admin-sidebar__nav">
            @foreach($adminNavSections as $section)
                @php $sectionActive = collect($section['items'])->contains(fn($item) => request()->routeIs($item['match'])); @endphp
                <div class="admin-sidebar__section" x-data="{ open: true }">
                    <button type="button"
                            @click="if(!collapsed) open = !open"
                            class="admin-sidebar__section-label"
                            :class="{
                                'justify-center': collapsed,
                                'is-active': {{ $sectionActive ? 'true' : 'false' }}
                            }">
                        <span class="flex-1 text-left" x-show="!collapsed">{{ $section['label'] }}</span>
                        <svg x-show="!collapsed" width="12" height="12" class="admin-sidebar-icon transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        @if($sectionActive)
                            <span x-show="collapsed" class="block h-1.5 w-1.5 rounded-full bg-brand-green-500"></span>
                        @endif
                    </button>

                    <div x-show="open || collapsed" class="mt-0.5 space-y-0.5">
                        @foreach($section['items'] as $item)
                            @php
                                $active = request()->routeIs($item['match']);
                                if (($item['params']['tab'] ?? null) === 'profit') {
                                    $active = $active && request('tab') === 'profit';
                                } elseif (($item['label'] ?? '') === 'Expense Reports') {
                                    $active = $active && request('tab') !== 'profit';
                                }
                                $url = route($item['route'], $item['params'] ?? []);
                            @endphp
                            <a href="{{ $url }}"
                               title="{{ $item['label'] }}"
                               @click="mobileOpen = false"
                               class="admin-sidebar__link {{ $active ? 'is-active' : '' }}">
                                <x-admin.icon :name="$item['icon']" :size="18" />
                                <span class="truncate" x-show="!collapsed">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Footer (~44px) --}}
        <div class="admin-sidebar__footer">
            <div class="admin-sidebar__footer-chip">
                <span x-show="!collapsed">MamaBazar Admin v1.0</span>
                <span x-show="collapsed">v1.0</span>
            </div>
        </div>
    </aside>

    {{-- Main (header starts after sidebar; no overlap) --}}
    <div class="admin-main">
        <header class="sticky top-0 z-40 flex h-14 shrink-0 items-center gap-2.5 border-b border-[var(--admin-border)] bg-white/95 px-3 backdrop-blur sm:px-5">
            <button type="button" @click="mobileOpen = true" class="rounded-[6px] p-2 text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Open sidebar">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <button type="button" @click="toggleCollapse()" class="hidden rounded-[6px] p-2 text-slate-500 hover:bg-slate-100 md:inline-flex" aria-label="Toggle sidebar">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <div class="hidden items-center gap-1.5 text-sm md:flex">
                <span class="text-xs text-slate-400">Admin</span>
                <svg width="14" height="14" class="text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-sm font-semibold text-slate-800">{{ $headerTitle ?? 'Dashboard' }}</span>
            </div>

            <div class="flex-1 min-w-0"></div>

            <button type="button" @click="commandOpen = true"
                    class="hidden items-center gap-2 rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] px-2.5 py-1.5 text-sm text-slate-400 hover:bg-slate-100 sm:flex">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="hidden text-xs lg:inline">Search…</span>
                <kbd class="ml-1 rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-[10px] text-slate-400">⌘K</kbd>
            </button>

            @php
                $pendingNotifs = \App\Models\Order::whereIn('status', ['pending', 'payment_pending'])->orderByDesc('id')->limit(5)->get();
                $lowStockNotifs = \App\Models\Product::where('track_inventory', true)->where('stock', '>', 0)->where('stock', '<=', 10)->orderBy('stock')->limit(3)->get();
                $notifCount = $pendingNotifs->count() + $lowStockNotifs->count();
            @endphp
            <div class="relative" x-data="{ notifOpen: false }">
                <button type="button" @click="notifOpen = !notifOpen" @click.away="notifOpen = false"
                        class="relative rounded-[6px] p-2 text-slate-500 hover:bg-slate-100">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if($notifCount > 0)
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500"></span>
                    @endif
                </button>
                <div x-show="notifOpen" x-cloak x-transition
                     class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-[10px] border border-[var(--admin-border)] bg-white shadow-panel">
                    <div class="flex h-11 items-center justify-between border-b border-[var(--admin-border)] px-3">
                        <span class="text-sm font-bold">Notifications</span>
                        <span class="rounded-[6px] bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $notifCount }}</span>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        @forelse($pendingNotifs as $o)
                            <a href="{{ route('admin.orders.show', $o->id) }}" class="flex gap-3 border-b border-slate-50 px-4 py-3 text-sm hover:bg-slate-50">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-green-500"></span>
                                <div>
                                    <p class="font-semibold">Order {{ $o->order_id }}</p>
                                    <p class="text-xs text-slate-500">{{ $o->customer_name }} · {{ $o->status }}</p>
                                </div>
                            </a>
                        @empty
                        @endforelse
                        @foreach($lowStockNotifs as $p)
                            <a href="{{ route('admin.inventory.index', ['filter' => 'low']) }}" class="flex gap-3 border-b border-slate-50 px-4 py-3 text-sm hover:bg-slate-50">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
                                <div>
                                    <p class="font-semibold">Low stock: {{ \Illuminate\Support\Str::limit($p->title, 28) }}</p>
                                    <p class="text-xs text-slate-500">{{ $p->stock }} left</p>
                                </div>
                            </a>
                        @endforeach
                        @if($notifCount === 0)
                            <div class="px-4 py-6 text-center text-xs text-slate-400">No new notifications</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="relative" x-data="{ userMenu: false }">
                <button type="button" @click="userMenu = !userMenu" @click.away="userMenu = false"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-green-500 text-xs font-bold text-white ring-2 ring-brand-green-100">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </button>
                <div x-show="userMenu" x-cloak x-transition
                     class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-[10px] border border-[var(--admin-border)] bg-white py-1 shadow-panel">
                    <div class="border-b border-[var(--admin-border)] px-4 py-3">
                        <p class="text-sm font-bold">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="truncate text-xs text-slate-400">{{ auth()->user()->phone ?? auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">View Storefront</a>
                    <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Settings</a>
                    <div class="my-1 border-t border-[var(--admin-border)]"></div>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-3 sm:p-5 min-w-0">
            @yield('content')
        </main>
    </div>

    {{-- Command Palette ⌘K --}}
    <div x-show="commandOpen" x-cloak class="fixed inset-0 z-[300] flex items-start justify-center bg-black/40 pt-[12%] px-4"
         @click.self="commandOpen = false">
        <div class="w-full max-w-lg overflow-hidden rounded-[10px] border border-[var(--admin-border)] bg-white shadow-panel"
             x-data="{ q: '', selected: 0 }"
             @keydown.escape.window="commandOpen = false">
            <div class="flex h-12 items-center gap-2 border-b border-[var(--admin-border)] px-4">
                <svg width="16" height="16" class="text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input x-model="q" x-ref="cmdInput" type="text" placeholder="Search pages..."
                       class="h-full flex-1 bg-transparent text-sm outline-none min-w-0"
                       @keydown.enter.prevent="const links = [...$el.closest('div').parentElement.querySelectorAll('[data-cmd-link]')].filter(a => !a.classList.contains('hidden')); if(links[selected]) location.href = links[selected].href">
            </div>
            <div class="max-h-80 overflow-y-auto p-2">
                @foreach($allAdminNavItems as $i => $item)
                    <a data-cmd-link href="{{ route($item['route'], $item['params'] ?? []) }}"
                       x-show="!q || '{{ strtolower($item['label'].' '.$item['section']) }}'.includes(q.toLowerCase())"
                       class="flex items-center gap-3 rounded-[6px] px-3 py-2 text-sm text-slate-700 hover:bg-brand-green-50 hover:text-brand-green-700"
                       @click="commandOpen = false">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item['section'] }}</span>
                        <span class="font-medium">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <x-admin.toast />

    <script>
        function adminShell() {
            return {
                collapsed: false,
                mobileOpen: false,
                commandOpen: false,
                init() {
                    try { this.collapsed = localStorage.getItem('mamabazar:admin_sidebar_collapsed') === 'true'; } catch (e) {}
                    this.$watch('commandOpen', (v) => {
                        if (v) this.$nextTick(() => this.$refs.cmdInput?.focus());
                    });
                },
                toggleCollapse() {
                    this.collapsed = !this.collapsed;
                    try { localStorage.setItem('mamabazar:admin_sidebar_collapsed', String(this.collapsed)); } catch (e) {}
                },
                onKey(e) {
                    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                        e.preventDefault();
                        this.commandOpen = !this.commandOpen;
                    }
                }
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
