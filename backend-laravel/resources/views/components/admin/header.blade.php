{{--
  Admin header/topbar lives in layouts/admin.blade.php (sticky h-16, breadcrumbs, ⌘K,
  notifications, avatar). Shared Alpine state with the shell.
--}}
@props([])
<header {{ $attributes->merge(['class' => 'sticky top-0 z-40 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6']) }}>
    {{ $slot }}
</header>
