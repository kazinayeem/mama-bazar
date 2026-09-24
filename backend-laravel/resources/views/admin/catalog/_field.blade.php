@php
    $type = $field['type'] ?? 'text';
    $name = $field['name'];
    $label = $field['label'];
    $required = !empty($field['required']);
@endphp

<label class="mb-1 block text-xs font-bold text-slate-700">{{ $label }}@if($required) * @endif</label>

@if($type === 'textarea')
    <textarea name="{{ $name }}" rows="3" @if($required) required @endif
              class="w-full rounded-xl border border-slate-200 p-2.5 text-xs focus:border-brand-green-500 focus:outline-none">{{ $value }}</textarea>
@elseif($type === 'select')
    <select name="{{ $name }}" @if($required) required @endif
            class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs focus:border-brand-green-500 focus:outline-none">
        @foreach(($field['options'] ?? []) as $optVal => $optLabel)
            <option value="{{ $optVal }}" @selected((string)$value === (string)$optVal)>{{ $optLabel }}</option>
        @endforeach
    </select>
@elseif($type === 'checkbox')
    <label class="flex items-center justify-between rounded-md border border-slate-200 p-3 text-xs">
        <span class="font-medium text-slate-700">{{ $label }}</span>
        <input type="checkbox" name="{{ $name }}" value="1" @checked($value) class="rounded text-brand-green-600">
    </label>
@elseif($type === 'hex')
    <div class="flex items-center gap-2">
        <input type="color" value="{{ $value ?: '#176B3A' }}"
               oninput="this.nextElementSibling.value = this.value"
               class="h-9 w-10 cursor-pointer rounded border border-slate-200 p-0.5">
        <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="#176B3A"
               class="flex-1 rounded-xl border border-slate-200 p-2.5 font-mono text-xs focus:border-brand-green-500 focus:outline-none">
    </div>
@elseif($type === 'number')
    <input type="number" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif
           class="w-full rounded-xl border border-slate-200 p-2.5 text-xs focus:border-brand-green-500 focus:outline-none">
@else
    <input type="text" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif
           class="w-full rounded-xl border border-slate-200 p-2.5 text-xs focus:border-brand-green-500 focus:outline-none">
@endif
