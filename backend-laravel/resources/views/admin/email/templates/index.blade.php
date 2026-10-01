@extends('layouts.admin', ['headerTitle' => 'Email Templates'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Email Templates" subtitle="Subjects and content for every automated email. Business details (name, phone, address, logo) come from Business Information automatically." />
    @include('admin.email.partials.tabs')

    @foreach($templates as $category => $items)
        <div class="admin-table-wrap">
            <div class="px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">{{ $categories[$category] ?? ucfirst($category) }}</h2>
            </div>
            <table class="admin-table">
                <thead><tr><th>Template</th><th>Subject</th><th>Status</th><th>Updated</th><th class="text-right"></th></tr></thead>
                <tbody>
                    @foreach($items as $template)
                        <tr>
                            <td>
                                <span class="block font-semibold text-slate-900">{{ $template->name }}</span>
                                <span class="font-mono text-[11px] text-slate-400">{{ $template->key }}</span>
                            </td>
                            <td class="max-w-sm truncate text-xs text-slate-600">{{ $template->subject }}</td>
                            <td>
                                @if(in_array($template->key, \App\Services\EmailTemplateService::ALWAYS_ACTIVE, true))
                                    <x-admin.badge variant="success">Always on</x-admin.badge>
                                @else
                                    <x-admin.badge :variant="$template->is_active ? 'success' : 'secondary'">{{ $template->is_active ? 'Active' : 'Inactive' }}</x-admin.badge>
                                @endif
                            </td>
                            <td class="text-xs text-slate-500 whitespace-nowrap">{{ $template->updated_at?->format('d M Y') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.email.templates.preview', $template->id) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-slate-600 hover:underline">Preview</a>
                                <a href="{{ route('admin.email.templates.edit', $template->id) }}" class="ml-3 text-xs font-semibold text-brand-green-700 hover:underline">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
