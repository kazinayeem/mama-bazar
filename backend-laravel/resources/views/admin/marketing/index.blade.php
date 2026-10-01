@extends('layouts.admin', ['headerTitle' => 'Marketing'])

@section('content')
<div class="admin-page" x-data="{
    editModalOpen: false,
    editingItem: {},
    openEdit(item) {
        this.editingItem = { ...item };
        this.editModalOpen = true;
    }
}">
    <x-admin.page-header title="Marketing" subtitle="Pixels, tags, and campaign integrations" />

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Edit Integration Modal -->
    <x-admin.modal name="editModalOpen" x-title="'Edit Integration: ' + (editingItem ? editingItem.name : '')" subtitle="Update tracking ID, credentials, and script tags">
        <form :action="'{{ url('admin/marketing') }}/' + (editingItem && editingItem.id ? editingItem.id : '')" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Name *</label>
                    <input name="name" x-model="editingItem.name" required class="admin-control w-full text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Type *</label>
                    <select name="type" x-model="editingItem.type" required class="admin-control w-full text-sm">
                        <option value="facebook_pixel">Facebook Pixel</option>
                        <option value="facebook_conversion_api">Facebook Conversions API (server)</option>
                        <option value="google_tag">Google Tag / GA</option>
                        <option value="google_tag_manager">Google Tag Manager</option>
                        <option value="google_analytics">Google Analytics 4</option>
                        <option value="tiktok_pixel">TikTok Pixel</option>
                        <option value="custom">Custom Script</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Pixel / Tag ID</label>
                    <input name="pixel_id" x-model="editingItem.pixel_id" class="admin-control w-full text-sm font-mono">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Script Code</label>
                    <textarea name="script_code" x-model="editingItem.script_code" rows="4" class="w-full rounded-[6px] border border-[var(--admin-border)] px-3 py-2.5 font-mono text-xs"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Access Token</label>
                        <input name="access_token" x-model="editingItem.access_token" class="admin-control w-full text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-700">Test Event Code</label>
                        <input name="test_event_code" x-model="editingItem.test_event_code" class="admin-control w-full text-sm">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Status</label>
                    <select name="status" x-model="editingItem.status" class="admin-control w-full text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <x-admin.button type="button" variant="outline" size="sm" @click="editModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="admin-surface p-4 h-fit">
            <h3 class="mb-4 text-sm font-bold">Add Integration</h3>
            <form action="{{ route('admin.marketing.store') }}" method="POST" class="space-y-3">
                @csrf
                <div><label class="mb-1 block text-xs font-bold">Name *</label><input name="name" required class="admin-control w-full text-sm" placeholder="e.g. Meta Pixel Main"></div>
                <div>
                    <label class="mb-1 block text-xs font-bold">Type *</label>
                    <select name="type" required class="admin-control w-full text-sm">
                        <option value="facebook_pixel">Facebook Pixel</option>
                        <option value="facebook_conversion_api">Facebook Conversions API (server)</option>
                        <option value="google_tag">Google Tag / GA</option>
                        <option value="google_tag_manager">Google Tag Manager</option>
                        <option value="google_analytics">Google Analytics 4</option>
                        <option value="tiktok_pixel">TikTok Pixel</option>
                        <option value="custom">Custom Script</option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">For server Conversions API: set Pixel ID + Access Token on a <code>facebook_conversion_api</code> row.</p>
                </div>
                <div><label class="mb-1 block text-xs font-bold">Pixel / Tag ID</label><input name="pixel_id" class="admin-control w-full text-sm font-mono" placeholder="e.g. 1234567890"></div>
                <div><label class="mb-1 block text-xs font-bold">Script Code</label><textarea name="script_code" rows="3" class="w-full rounded-[6px] border border-[var(--admin-border)] px-3 py-2 font-mono text-xs"></textarea></div>
                <div><label class="mb-1 block text-xs font-bold">Access Token</label><input name="access_token" class="admin-control w-full text-sm"></div>
                <div><label class="mb-1 block text-xs font-bold">Test Event Code</label><input name="test_event_code" class="admin-control w-full text-sm"></div>
                <div>
                    <label class="mb-1 block text-xs font-bold">Status</label>
                    <select name="status" class="admin-control w-full text-sm">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button class="inline-flex h-10 w-full items-center justify-center rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white hover:bg-brand-green-600">Save Integration</button>
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
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openEdit(@js($item))" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.marketing.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="rounded px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
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
