@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'type' => 'text',
])

@php
    $wrapperClass = $attributes->get('class');
@endphp

<div class="{{ $wrapperClass ?: 'space-y-1.5' }}">
    @if($label)
        <label class="block text-xs font-semibold text-slate-700">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        {{ $attributes->except('class')->merge([
            'class' => 'admin-control w-full'.($error ? ' border-red-300' : '')
        ]) }}
    >
    @if($error)
        <p class="text-[11px] font-medium text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="text-[11px] text-slate-400">{{ $hint }}</p>
    @endif
</div>
