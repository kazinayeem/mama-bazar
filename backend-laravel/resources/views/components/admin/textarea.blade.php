@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'rows' => 3,
])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-xs font-semibold text-slate-700">{{ $label }}</label>
    @endif
    <textarea
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'w-full rounded-[6px] border border-[var(--admin-border)] bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-green-500 focus:outline-none focus:ring-[3px] focus:ring-[var(--admin-ring)] disabled:bg-slate-50 disabled:text-slate-400'.($error ? ' border-red-300' : '')
        ]) }}
    >{{ $slot }}</textarea>
    @if($error)
        <p class="text-[11px] font-medium text-red-600">{{ $error }}</p>
    @elseif($hint)
        <p class="text-[11px] text-slate-400">{{ $hint }}</p>
    @endif
</div>
