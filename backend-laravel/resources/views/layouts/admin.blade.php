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
        $lucide = \App\Support\AdminNav::lucide();
        $adminNavSections = \App\Support\AdminNav::sections();
        $allAdminNavItems = \App\Support\AdminNav::allItems();
    @endphp

    {{-- Mobile backdrop --}}
    <div x-show="mobileOpen" x-cloak
         class="fixed inset-0 z-[200] bg-black/50 lg:hidden"
         @click="mobileOpen = false"
         x-transition.opacity></div>

    {{-- Sidebar: ~256px desktop, drawer on mobile --}}
    <aside class="fixed inset-y-0 left-0 z-[201] flex w-64 shrink-0 flex-col border-r border-[var(--admin-border)] bg-white transition-transform duration-200 ease-out lg:static lg:w-auto"
           :class="[
               mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
               collapsed ? 'lg:w-16' : 'lg:w-64',
           ]">

        {{-- Brand header --}}
        <div class="relative flex h-14 shrink-0 items-center gap-2.5 border-b border-[var(--admin-border)]"
             :class="collapsed ? 'justify-center px-0' : 'px-3.5'">
            <button type="button" @click="mobileOpen = false" class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-[6px] p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden" aria-label="Close sidebar">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] bg-brand-green-500 text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $lucide['store'] !!}</svg>
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
        <nav class="flex-1 space-y-3 overflow-y-auto py-3" :class="collapsed ? 'px-1.5' : 'px-2.5'">
            @foreach($adminNavSections as $section)
                @php $sectionActive = collect($section['items'])->contains(fn($item) => request()->routeIs($item['match'])); @endphp
                <div x-data="{ open: {{ $sectionActive ? 'true' : 'true' }} }">
                    <button type="button"
                            @click="if(!collapsed) open = !open"
                            class="flex w-full items-center gap-2 rounded-[6px] px-2 py-1 text-[10px] font-semibold uppercase tracking-wider transition-colors hover:text-slate-800"
                            :class="{
                                'justify-center': collapsed,
                                'text-brand-green-600': {{ $sectionActive ? 'true' : 'false' }},
                                'text-slate-400': {{ $sectionActive ? 'false' : 'true' }}
                            }">
                        <span class="flex-1 text-left" x-show="!collapsed">{{ $section['label'] }}</span>
                        <svg x-show="!collapsed" class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                               class="flex items-center gap-2.5 rounded-[6px] py-1.5 text-[13px] font-medium transition-colors {{ $active ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}"
                               :class="collapsed ? 'justify-center px-1.5' : 'px-2.5'">
                                <svg class="h-[16px] w-[16px] shrink-0 {{ $active ? 'text-brand-green-600' : '' }}"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    {!! $lucide[$item['icon']] ?? '' !!}
                                </svg>
                                <span class="truncate" x-show="!collapsed">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-[var(--admin-border)] p-2.5">
            <div class="rounded-[6px] bg-[var(--admin-muted)] px-2.5 py-1.5 text-center" :class="collapsed && 'px-1'">
                <p class="text-[10px] font-semibold text-slate-400" x-show="!collapsed">MamaBazar Admin v1.0</p>
                <p class="text-[10px] font-semibold text-slate-400" x-show="collapsed">v1.0</p>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
        <header class="sticky top-0 z-40 flex h-14 shrink-0 items-center gap-2.5 border-b border-[var(--admin-border)] bg-white/95 px-3 backdrop-blur sm:px-5">
            <button type="button" @click="mobileOpen = true" class="rounded-[6px] p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Open sidebar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <button type="button" @click="toggleCollapse()" class="hidden rounded-[6px] p-2 text-slate-500 hover:bg-slate-100 lg:inline-flex" aria-label="Toggle sidebar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <div class="hidden items-center gap-1.5 text-sm md:flex">
                <span class="text-xs text-slate-400">Admin</span>
                <svg class="h-3.5 w-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-sm font-semibold text-slate-800">{{ $headerTitle ?? 'Dashboard' }}</span>
            </div>

            <div class="flex-1"></div>

            <button type="button" @click="commandOpen = true"
                    class="hidden items-center gap-2 rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] px-2.5 py-1.5 text-sm text-slate-400 hover:bg-slate-100 sm:flex">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
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

        <main class="flex-1 overflow-y-auto p-3 sm:p-5">
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
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input x-model="q" x-ref="cmdInput" type="text" placeholder="Search pages..."
                       class="h-full flex-1 bg-transparent text-sm outline-none"
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
