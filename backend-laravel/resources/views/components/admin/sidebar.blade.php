{{-- Sidebar chrome is rendered in layouts/admin.blade.php for shared Alpine shell state. --}}
@props([])
<aside {{ $attributes->merge(['class' => 'flex w-64 shrink-0 flex-col border-r border-[var(--admin-border)] bg-white']) }}>
    {{ $slot }}
</aside>
