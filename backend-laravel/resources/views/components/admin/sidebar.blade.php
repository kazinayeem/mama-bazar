{{--
  Admin sidebar is composed inside layouts/admin.blade.php to share Alpine adminShell() state
  (collapsed / mobileOpen). This component documents the contract and can be included later
  if the shell is split further.
--}}
@props([])
<div {{ $attributes }}>
    {{ $slot }}
</div>
