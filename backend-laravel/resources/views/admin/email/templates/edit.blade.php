@extends('layouts.admin', ['headerTitle' => 'Edit Email Template'])

@section('content')
@php $tok = fn (string $name) => str_repeat('{', 2).$name.str_repeat('}', 2); @endphp
<div class="admin-page" x-data="{
    insert(token) {
        const el = this.$refs[this.target] || this.$refs.body_html;
        const start = el.selectionStart ?? el.value.length;
        el.value = el.value.slice(0, start) + token + el.value.slice(el.selectionEnd ?? start);
        el.focus();
        el.selectionStart = el.selectionEnd = start + token.length;
    },
    target: 'body_html',
    refreshPreview() {
        const form = this.$refs.editor;
        const data = new FormData(form);
        data.delete('_method');
        fetch(@js(route('admin.email.templates.preview', $template->id)), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: data,
        }).then(async (res) => {
            const html = await res.text();
            if (! res.ok) { this.previewError = 'Fix the validation errors (unknown placeholder or missing required variable) to preview.'; return; }
            this.previewError = '';
            this.$refs.preview.srcdoc = html;
        });
    },
    previewError: '',
}">
    <x-admin.page-header :title="$template->name" :subtitle="'Template key: '.$template->key">
        <x-slot:actions>
            <x-admin.button :href="route('admin.email.templates.index')" variant="outline" size="sm">← All templates</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    @include('admin.email.partials.tabs')

    <div class="grid gap-4 xl:grid-cols-2">
        <form x-ref="editor" action="{{ route('admin.email.templates.update', $template->id) }}" method="POST" class="admin-surface p-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Subject</label>
                <input type="text" name="subject" x-ref="subject" @focus="target = 'subject'" required maxlength="255" value="{{ old('subject', $template->subject) }}" class="admin-control w-full">
                <p class="mt-1 text-[11px] text-slate-400">OTP codes and reset links are not allowed in subjects.</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">HTML body</label>
                <textarea name="body_html" x-ref="body_html" @focus="target = 'body_html'" rows="18" required class="admin-control w-full font-mono text-xs leading-relaxed">{{ old('body_html', $template->body_html) }}</textarea>
                <p class="mt-1 text-[11px] text-slate-400">The header, footer, business details and unsubscribe link are added automatically. Scripts, forms, iframes and event handlers are stripped on save.</p>
                @if($required)
                    <p class="mt-1 text-[11px] font-semibold text-amber-700">Required: {{ collect($required)->map($tok)->implode(', ') }}</p>
                @endif
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Plain-text version (optional)</label>
                <textarea name="body_plain" x-ref="body_plain" @focus="target = 'body_plain'" rows="5" class="admin-control w-full font-mono text-xs">{{ old('body_plain', $template->body_plain) }}</textarea>
                <p class="mt-1 text-[11px] text-slate-400">Leave empty to generate it from the HTML.</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                @if($alwaysActive)
                    <span class="text-xs text-slate-500">Security email — always sent.</span>
                @else
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active)) class="rounded border-slate-300 text-brand-green-600">
                        Active (inactive templates are not sent)
                    </label>
                @endif
                <div class="flex gap-2">
                    <x-admin.button type="button" variant="outline" size="sm" @click="refreshPreview()">Update preview</x-admin.button>
                    <x-admin.button type="submit" size="sm">Save template</x-admin.button>
                </div>
            </div>
        </form>

        <div class="space-y-4">
            <div class="admin-surface overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
                    <h2 class="text-xs font-bold text-slate-700">Preview (sample data)</h2>
                    <a href="{{ route('admin.email.templates.preview', $template->id) }}" target="_blank" rel="noopener" class="text-[11px] font-semibold text-brand-green-700 hover:underline">Open saved version ↗</a>
                </div>
                <p x-show="previewError" x-text="previewError" class="bg-red-50 px-4 py-2 text-[11px] text-red-700"></p>
                <iframe x-ref="preview" sandbox="" title="Email preview" class="h-[540px] w-full bg-slate-50" src="{{ route('admin.email.templates.preview', $template->id) }}"></iframe>
            </div>

            <div class="admin-surface p-4 space-y-3">
                <form action="{{ route('admin.email.templates.test', $template->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="email" name="test_email" required value="{{ auth()->user()->email }}" placeholder="you@example.com" class="admin-control flex-1">
                    <x-admin.button type="submit" variant="outline" size="sm">Send test (saved version)</x-admin.button>
                </form>
                @if($hasDefault)
                    <form action="{{ route('admin.email.templates.restore', $template->id) }}" method="POST" onsubmit="return confirm('Replace this template with the built-in default? Your edits will be lost.')">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Restore built-in default</button>
                    </form>
                @endif
            </div>

            <div class="admin-surface p-4">
                <h2 class="mb-2 text-xs font-bold text-slate-700">Placeholders — click to insert</h2>
                <div class="max-h-80 space-y-3 overflow-y-auto pr-1">
                    @foreach($placeholderGroups as $group => $items)
                        <div>
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $group }}</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($items as $name => $label)
                                    <button type="button" title="{{ $label }}" @click="insert({{ \Illuminate\Support\Js::from($tok($name)) }})"
                                        class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[10px] text-slate-700 hover:border-brand-green-300 hover:bg-brand-green-50">{{ $tok($name) }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
