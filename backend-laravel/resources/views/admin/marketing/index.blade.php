@extends('layouts.admin', ['headerTitle' => 'Marketing'])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Marketing</h1>
        <p class="text-sm text-slate-500">Pixels, tags, and campaign integrations</p>
    </div>
</div>

<div class="mt-4 grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border bg-white p-5 shadow-soft h-fit">
        <h3 class="mb-4 text-sm font-bold">Add Integration</h3>
        <form action="{{ route('admin.marketing.store') }}" method="POST" class="space-y-3">
            @csrf
            <div><label class="mb-1 block text-xs font-bold">Name *</label><input name="name" required class="w-full rounded-xl border p-2.5 text-xs"></div>
            <div>
                <label class="mb-1 block text-xs font-bold">Type *</label>
                <select name="type" required class="w-full rounded-xl border bg-white p-2.5 text-xs">
                    <option value="facebook_pixel">Facebook Pixel</option>
                    <option value="google_tag">Google Tag / GA</option>
                    <option value="tiktok_pixel">TikTok Pixel</option>
                    <option value="custom">Custom Script</option>
                </select>
            </div>
            <div><label class="mb-1 block text-xs font-bold">Pixel / Tag ID</label><input name="pixel_id" class="w-full rounded-xl border p-2.5 text-xs font-mono"></div>
            <div><label class="mb-1 block text-xs font-bold">Script Code</label><textarea name="script_code" rows="4" class="w-full rounded-xl border p-2.5 text-xs font-mono"></textarea></div>
            <div><label class="mb-1 block text-xs font-bold">Access Token</label><input name="access_token" class="w-full rounded-xl border p-2.5 text-xs"></div>
            <div><label class="mb-1 block text-xs font-bold">Test Event Code</label><input name="test_event_code" class="w-full rounded-xl border p-2.5 text-xs"></div>
            <div>
                <label class="mb-1 block text-xs font-bold">Status</label>
                <select name="status" class="w-full rounded-xl border bg-white p-2.5 text-xs">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button class="w-full rounded-full bg-brand-green-500 py-2.5 text-sm font-medium text-white">Save Integration</button>
        </form>
    </div>

    <div class="lg:col-span-2 overflow-hidden rounded-xl border bg-white shadow-soft">
        <div class="border-b px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-700">Active Integrations ({{ $integrations->count() }})</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Pixel ID</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y">
                @forelse($integrations as $item)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-xs uppercase text-slate-500">{{ $item->type }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $item->pixel_id ?: '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item->status==='active' ? 'bg-brand-green-50 text-brand-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $item->status }}</span></td>
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
@endsection
