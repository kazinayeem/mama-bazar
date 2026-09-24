@props([
    'open' => 'filtersOpen',
])

<div {{ $attributes->merge(['class' => 'admin-filter-bar']) }} x-data="{ {{ $open }}: false }">
    {{ $slot }}

    @isset($mobile)
        <button type="button"
                @click="{{ $open }} = !{{ $open }}"
                class="flex w-full items-center justify-between rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] px-3 py-2.5 text-xs font-semibold text-slate-700 md:hidden">
            <span>{{ $mobileToggle ?? 'Filters' }}</span>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="{{ $open }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
             :class="{{ $open }} ? 'flex' : 'hidden md:flex'">
            {{ $mobile }}
        </div>
    @endisset
</div>
