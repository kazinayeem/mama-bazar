@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-5xl">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Edit Template: {{ $template->name }}</h1>
            <p class="text-xs text-slate-500">Key: <code class="font-mono text-brand-green-700">{{ $template->key }}</code> &middot; Category: <span class="capitalize">{{ $template->category }}</span></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.email.templates.preview', $template->id) }}" target="_blank" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Live Preview &nearr;
            </a>
            <a href="{{ route('admin.email.templates') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Editor Form --}}
        <div class="lg:col-span-2 space-y-6">
            <form action="{{ route('admin.email.templates.update', $template->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900">Email Subject &amp; Content</h2>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                            Active
                        </label>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Email Subject Line</label>
                        <input type="text" name="subject" required value="{{ old('subject', $template->subject) }}" class="admin-control w-full text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">HTML Body</label>
                        <textarea name="body_html" required rows="14" class="admin-control w-full font-mono text-xs leading-relaxed">{{ old('body_html', $template->body_html) }}</textarea>
                        <p class="mt-1 text-[11px] text-slate-400">The template content is automatically wrapped inside the universal Mama Bazar header and footer.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Plain-Text Fallback (Optional)</label>
                        <textarea name="body_plain" rows="4" class="admin-control w-full font-mono text-xs leading-relaxed" placeholder="Auto-generated from HTML if left blank...">{{ old('body_plain', $template->body_plain) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit" class="rounded-xl bg-brand-green-600 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-brand-green-700 transition">
                        Save Template Changes
                    </button>
                </div>
            </form>

            {{-- Send Test Preview Email --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-3">
                <h3 class="text-sm font-bold text-slate-900">Send Test Email for this Template</h3>
                <p class="text-xs text-slate-500">Deliver a real preview of this template with sample data to your inbox.</p>
                <form action="{{ route('admin.email.templates.send-test', $template->id) }}" method="POST" class="flex gap-2 pt-1">
                    @csrf
                    <input type="email" name="test_email" required value="{{ auth()->user()->email ?: 'contact@mama-bazar.com' }}" placeholder="tester@example.com" class="admin-control flex-1 text-xs">
                    <button type="submit" class="py-2 px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition">
                        Send Preview
                    </button>
                </form>
            </div>
        </div>

        {{-- Dynamic Placeholders Sidebar --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Dynamic Placeholders</h3>
                <p class="text-[11px] text-slate-500">Click to copy into your subject line or HTML content:</p>

                <div class="space-y-2 max-h-[500px] overflow-y-auto pr-1">
                    @foreach($placeholders as $tag => $desc)
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-xs flex flex-col gap-0.5">
                            <button type="button" onclick="navigator.clipboard?.writeText('{{ $tag }}'); alert('Copied {{ $tag }}');" class="font-mono text-[11px] font-bold text-brand-green-700 text-left hover:underline">
                                {{ $tag }}
                            </button>
                            <span class="text-[10px] text-slate-400">{{ $desc }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
