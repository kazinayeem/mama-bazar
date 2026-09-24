@props([
    'label' => null,
    'hint' => null,
    'error' => null,
])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-xs font-bold text-slate-800">{{ $label }}</label>
    @endif
    <select {{ $attributes->merge([
        'class' => 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-500/20'.($error ? ' border-red-300' : '')
    ]) }}>
        {{ $slot }}
    </select>
    @if($error)
        <p class="text-[11px] font-medium text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="text-[11px] text-slate-400">{{ $hint }}</p>
    @endif
</div>
