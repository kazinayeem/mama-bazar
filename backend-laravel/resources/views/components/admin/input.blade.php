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
        <label class="block text-xs font-bold text-slate-800">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        {{ $attributes->except('class')->merge([
            'class' => 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-500/20 disabled:bg-slate-50 disabled:text-slate-400'.($error ? ' border-red-300' : '')
        ]) }}
    >
    @if($error)
        <p class="text-[11px] font-medium text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="text-[11px] text-slate-400">{{ $hint }}</p>
    @endif
</div>
