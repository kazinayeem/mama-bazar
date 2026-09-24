@php
    $type = $field['type'] ?? 'text';
    $name = $field['name'];
    $label = $field['label'];
    $required = !empty($field['required']);
@endphp

@if($type === 'checkbox')
    <label class="flex h-10 items-center justify-between rounded-[6px] border border-[var(--admin-border)] px-3 text-xs">
        <span class="font-medium text-slate-700">{{ $label }}</span>
        <input type="checkbox" name="{{ $name }}" value="1" @checked($value) class="rounded text-brand-green-600 focus:ring-brand-green-500">
    </label>
@else
    <label class="mb-1 block text-xs font-semibold text-slate-700">{{ $label }}@if($required) * @endif</label>

    @if($type === 'textarea')
        <textarea name="{{ $name }}" rows="3" @if($required) required @endif
                  class="w-full rounded-[6px] border border-[var(--admin-border)] px-3 py-2.5 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-[3px] focus:ring-[var(--admin-ring)]">{{ $value }}</textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" @if($required) required @endif
                class="admin-control w-full">
            @foreach(($field['options'] ?? []) as $optVal => $optLabel)
                <option value="{{ $optVal }}" @selected((string)$value === (string)$optVal)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif($type === 'hex')
        <div class="flex items-center gap-2">
            <input type="color" value="{{ $value ?: '#176B3A' }}"
                   oninput="this.nextElementSibling.value = this.value"
                   class="h-10 w-10 cursor-pointer rounded-[6px] border border-[var(--admin-border)] p-0.5">
            <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="#176B3A"
                   class="admin-control flex-1 font-mono">
        </div>
    @elseif($type === 'number')
        <input type="number" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif
               class="admin-control w-full">
    @else
        <input type="text" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif
               class="admin-control w-full">
    @endif
@endif
