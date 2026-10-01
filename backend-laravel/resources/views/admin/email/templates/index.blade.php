@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">System &amp; Marketing Email Templates</h1>
            <p class="text-xs text-slate-500">Manage 16 core automated transactional and campaign templates with dynamic placeholders.</p>
        </div>
        <div>
            <a href="{{ route('admin.email.dashboard') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    {{-- Templates Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-3.5">Template Name</th>
                        <th class="p-3.5">Category</th>
                        <th class="p-3.5">Default Subject Line</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($templates as $template)
                        <tr class="hover:bg-slate-50/60">
                            <td class="p-3.5 font-bold text-slate-900">
                                <div>{{ $template->name }}</div>
                                <span class="font-mono text-[10px] text-slate-400">{{ $template->key }}</span>
                            </td>
                            <td class="p-3.5">
                                <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase
                                    {{ $template->category === 'auth' ? 'bg-indigo-50 text-indigo-700' :
                                       ($template->category === 'order' ? 'bg-emerald-50 text-emerald-700' :
                                       ($template->category === 'marketing' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700')) }}">
                                    {{ $template->category }}
                                </span>
                            </td>
                            <td class="p-3.5 text-slate-700 max-w-sm truncate">{{ $template->subject }}</td>
                            <td class="p-3.5 text-center">
                                @if($template->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">Disabled</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-right space-x-1">
                                <a href="{{ route('admin.email.templates.preview', $template->id) }}" target="_blank" class="rounded px-2.5 py-1 text-slate-600 border border-slate-200 hover:bg-slate-100 font-semibold text-[11px]">
                                    Preview
                                </a>
                                <a href="{{ route('admin.email.templates.edit', $template->id) }}" class="rounded bg-brand-green-600 px-2.5 py-1 text-white hover:bg-brand-green-700 font-bold text-[11px]">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
