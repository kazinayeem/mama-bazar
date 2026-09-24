@props(['padding' => true])

<div {{ $attributes->merge([
    'class' => 'admin-surface'.($padding ? ' p-4' : '')
]) }}>
    {{ $slot }}
</div>
