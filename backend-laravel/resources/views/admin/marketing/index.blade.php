@extends('layouts.admin', ['headerTitle' => 'Marketing'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Marketing" subtitle="Pixels, tags, and campaign integrations" />
    <div class="grid gap-4 lg:grid-cols-3">
    <div class="admin-surface p-4 h-fit">
        <h3 class="mb-4 text-sm font-bold">Add Integration</h3>
        <form action="{{ route('admin.marketing.store') }}" method="POST" class="space-y-3">
            @csrf
            <div><label class="mb-1 block text-xs font-bold">Name *</label><input name="name" required class="admin-control w-full text-sm"></div>
            <div>
                <label class="mb-1 block text-xs font-bold">Type *</label>
                <select name="type" required class="admin-control w-full text-sm">
                    <option value="facebook_pixel">Facebook Pixel</option>
                    <option value="google_tag">Google Tag / GA</option>
                    <option value="tiktok_pixel">TikTok Pixel</option>
                    <option value="custom">Custom Script</option>
                </select>
            </div>
            <div><label class="mb-1 block text-xs font-bold">Pixel / Tag ID</label><input name="pixel_id" class="admin-control w-full text-sm font-mono"></div>
            <div><label class="mb-1 block text-xs font-bold">Script Code</label><textarea name="script_code" rows="4" class="w-full rounded-[6px] border border-[var(--admin-border)] px-3 py-2.5 font-mono text-sm"></textarea></div>
            <div><label class="mb-1 block text-xs font-bold">Access Token</label><input name="access_token" class="admin-control w-full text-sm"></div>
            <div><label class="mb-1 block text-xs font-bold">Test Event Code</label><input name="test_event_code" class="admin-control w-full text-sm"></div>
            <div>
                <label class="mb-1 block text-xs font-bold">Status</label>
                <select name="status" class="admin-control w-full text-sm">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button class="inline-flex h-10 w-full items-center justify-center rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white">Save Integration</button>
        </form>
    </div>

    <div class="lg:col-span-2 admin-table-wrap">
        <div class="border-b px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-700">Active Integrations ({{ $integrations->count() }})</div>
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Type</th><th>Pixel ID</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody class="divide-y">
                @forelse($integrations as $item)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-xs uppercase text-slate-500">{{ $item->type }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $item->pixel_id ?: '—' }}</td>
                        <td class="px-4 py-3"><span class="inline-flex items-center rounded-[6px] border px-1.5 py-0.5 text-[10px] font-bold {{ $item->status==='active' ? 'bg-brand-green-50 text-brand-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $item->status }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('admin.marketing.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-red-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">No marketing integrations yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
