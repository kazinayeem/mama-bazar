{{--
  Admin sidebar chrome lives in layouts/admin.blade.php (shared Alpine shell).
  This component is a thin wrapper for any page that needs the same aside shell class.
--}}
@props([])
<aside {{ $attributes->merge(['class' => 'admin-sidebar']) }}>
    {{ $slot }}
</aside>
