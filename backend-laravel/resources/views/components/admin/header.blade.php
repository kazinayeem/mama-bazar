{{--
  Admin header/topbar lives in layouts/admin.blade.php (sticky h-14, breadcrumbs, ⌘K,
  notifications, avatar). Shared Alpine state with the shell.
--}}
@props([])
<header {{ $attributes->merge(['class' => 'sticky top-0 z-40 flex h-14 shrink-0 items-center gap-2.5 border-b border-[var(--admin-border)] bg-white/95 px-3 backdrop-blur sm:px-5']) }}>
    {{ $slot }}
</header>
