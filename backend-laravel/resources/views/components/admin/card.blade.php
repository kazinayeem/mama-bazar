@props(['padding' => true])

<div {{ $attributes->merge([
    'class' => 'rounded-xl border border-slate-200 bg-white shadow-soft'.($padding ? ' p-5' : '')
]) }}>
    {{ $slot }}
</div>
