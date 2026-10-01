@php
    $type = $field['type'] ?? 'text';
    $name = $field['name'];
    $label = $field['label'];
    $required = !empty($field['required']);
    $modelPrefix = $modelPrefix ?? null;
    $hasModel = !empty($modelPrefix);
    $modelRef = $hasModel ? "{$modelPrefix}['{$name}']" : null;
    $value = $value ?? null;
@endphp

@if($type === 'checkbox')
    <label class="flex h-10 items-center justify-between rounded-lg border border-[var(--admin-border)] px-3 text-xs bg-white cursor-pointer hover:bg-slate-50/70 transition">
        <span class="font-medium text-slate-700">{{ $label }}</span>
        @if($hasModel)
            <input type="checkbox" name="{{ $name }}" value="1" x-model="{{ $modelRef }}" class="rounded text-brand-green-600 focus:ring-brand-green-500 h-4 w-4">
        @else
            <input type="checkbox" name="{{ $name }}" value="1" @checked($value) class="rounded text-brand-green-600 focus:ring-brand-green-500 h-4 w-4">
        @endif
    </label>
@else
    <label class="mb-1 block text-xs font-semibold text-slate-700">{{ $label }}@if($required) <span class="text-red-500">*</span> @endif</label>

    @if($type === 'textarea')
        @if($hasModel)
            <textarea name="{{ $name }}" rows="3" x-model="{{ $modelRef }}" @if($required) required @endif
                      class="w-full rounded-lg border border-[var(--admin-border)] px-3 py-2 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-500/20"></textarea>
        @else
            <textarea name="{{ $name }}" rows="3" @if($required) required @endif
                      class="w-full rounded-lg border border-[var(--admin-border)] px-3 py-2 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-500/20">{{ $value }}</textarea>
        @endif

    @elseif($type === 'select')
        @if($hasModel)
            <select name="{{ $name }}" x-model="{{ $modelRef }}" @if($required) required @endif
                    class="admin-control w-full text-xs bg-white">
                @foreach(($field['options'] ?? []) as $optVal => $optLabel)
                    <option value="{{ $optVal }}">{{ $optLabel }}</option>
                @endforeach
            </select>
        @else
            <select name="{{ $name }}" @if($required) required @endif
                    class="admin-control w-full text-xs bg-white">
                @foreach(($field['options'] ?? []) as $optVal => $optLabel)
                    <option value="{{ $optVal }}" @selected((string)$value === (string)$optVal)>{{ $optLabel }}</option>
                @endforeach
            </select>
        @endif

    @elseif($type === 'hex')
        <div class="flex items-center gap-2">
            @if($hasModel)
                <input type="color" x-model="{{ $modelRef }}"
                       class="h-9 w-9 cursor-pointer rounded-lg border border-[var(--admin-border)] p-0.5 bg-white shrink-0">
                <input type="text" name="{{ $name }}" x-model="{{ $modelRef }}" placeholder="#176B3A"
                       class="admin-control flex-1 font-mono uppercase text-xs">
                <div class="h-9 w-9 rounded-lg border border-slate-200 shrink-0 shadow-xs flex items-center justify-center text-[10px]"
                     :style="{ backgroundColor: {{ $modelRef }} || '#ffffff' }"></div>
            @else
                <input type="color" value="{{ $value ?: '#176B3A' }}"
                       oninput="this.nextElementSibling.value = this.value; this.nextElementSibling.nextElementSibling.style.backgroundColor = this.value"
                       class="h-9 w-9 cursor-pointer rounded-lg border border-[var(--admin-border)] p-0.5 bg-white shrink-0">
                <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="#176B3A"
                       oninput="this.previousElementSibling.value = this.value; this.nextElementSibling.style.backgroundColor = this.value"
                       class="admin-control flex-1 font-mono uppercase text-xs">
                <div class="h-9 w-9 rounded-lg border border-slate-200 shrink-0 shadow-xs"
                     style="background-color: {{ $value ?: '#176B3A' }}"></div>
            @endif
        </div>

    @elseif($type === 'number')
        @if($hasModel)
            <input type="number" name="{{ $name }}" x-model="{{ $modelRef }}" @if($required) required @endif
                   class="admin-control w-full text-xs">
        @else
            <input type="number" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif
                   class="admin-control w-full text-xs">
        @endif

    @elseif(in_array($name, ['logo', 'image', 'banner'], true))
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                @if($hasModel)
                    <input type="text" name="{{ $name }}" x-model="{{ $modelRef }}" placeholder="https://..." @if($required) required @endif
                           class="admin-control flex-1 text-xs">
                    <template x-if="{{ $modelRef }} && ({{ $modelRef }}.startsWith('http') || {{ $modelRef }}.startsWith('/'))">
                        <div class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center p-0.5">
                            <img :src="{{ $modelRef }}" class="h-full w-full object-contain" x-on:error="$el.style.display='none'">
                        </div>
                    </template>
                @else
                    <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="https://..." @if($required) required @endif
                           class="admin-control flex-1 text-xs">
                    @if($value)
                        <div class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center p-0.5">
                            <img src="{{ $value }}" class="h-full w-full object-contain">
                        </div>
                    @endif
                @endif
            </div>
        </div>

    @else
        @if($hasModel)
            <input type="text" name="{{ $name }}" x-model="{{ $modelRef }}" @if($required) required @endif
                   class="admin-control w-full text-xs">
        @else
            <input type="text" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif
                   class="admin-control w-full text-xs">
        @endif
    @endif
@endif
