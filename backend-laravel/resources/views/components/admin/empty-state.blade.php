@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-[8px] border border-dashed border-[var(--admin-border)] bg-white px-6 py-12 text-center']) }}>
    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-[8px] bg-[var(--admin-muted)] text-slate-400">
        {{ $icon ?? '' }}
        @unless(isset($icon))
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2h-3.5M4 13V7a2 2 0 012-2h3.5M9.5 5h5M12 17v4m-4-4h8"/></svg>
        @endunless
    </div>
    <h3 class="text-sm font-bold text-slate-900">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $description }}</p>
    @endif
    @if($actionLabel && $actionHref)
        <x-admin.button :href="$actionHref" class="mt-4" size="sm">{{ $actionLabel }}</x-admin.button>
    @endif
    {{ $slot }}
</div>
