@extends('layouts.admin', ['headerTitle' => 'Policies & Messages'])

@section('content')
<div
    class="admin-page"
    x-data="{
        createOpen: false,
        editOpen: false,
        editPolicy: null,
        editUrl: '',
        openEdit(p) {
            this.editPolicy = p;
            this.editUrl = '{{ route('admin.policies.update', ':id') }}'.replace(':id', p.id);
            this.editOpen = true;
        }
    }"
>
    <x-admin.page-header title="Policies & Messages" subtitle="Legal pages and store policy content">
        <x-slot:actions>
            <x-admin.button type="button" size="sm" @click="createOpen = true">
                <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Policy
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($errors->any())
        <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Create Policy Modal --}}
    <x-admin.modal name="createOpen" title="Create Policy Page" maxWidth="3xl">
        <form action="{{ route('admin.policies.store') }}" method="POST" class="grid gap-3.5 sm:grid-cols-2">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Title <span class="text-red-500">*</span></label>
                <input name="title" required class="admin-control w-full text-xs" placeholder="e.g. Return & Refund Policy">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Slug <span class="text-red-500">*</span></label>
                <input name="slug" required class="admin-control w-full text-xs font-mono" placeholder="return-refund">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Content (HTML)</label>
                <textarea name="content" rows="8" class="admin-control w-full text-xs font-mono" placeholder="<p>Policy content...</p>"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Status</label>
                <select name="status" class="admin-control w-full text-xs bg-white">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 sm:col-span-2 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="createOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Create Policy</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Edit Policy Modal --}}
    <x-admin.modal name="editOpen" x-title="'Edit Policy: ' + (editPolicy ? editPolicy.title : '')" maxWidth="3xl">
        <form :action="editUrl" method="POST" class="grid gap-3.5 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Title <span class="text-red-500">*</span></label>
                <input name="title" required :value="editPolicy?.title" class="admin-control w-full text-xs">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Slug <span class="text-red-500">*</span></label>
                <input name="slug" required :value="editPolicy?.slug" class="admin-control w-full text-xs font-mono">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Content (HTML)</label>
                <textarea name="content" rows="10" class="admin-control w-full text-xs font-mono" :value="editPolicy?.content || ''"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Status</label>
                <select name="status" class="admin-control w-full text-xs bg-white" :value="editPolicy?.status || 'published'">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 sm:col-span-2 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="editOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    {{-- Policies List --}}
    <div class="admin-table-wrap">
        @if($policies->isEmpty())
            <x-admin.empty-state title="No policy pages yet" description="Create legal policies, FAQs, or terms of service." />
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug / URL</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($policies as $policy)
                            <tr>
                                <td class="font-bold text-slate-900">{{ $policy->title }}</td>
                                <td><code class="rounded bg-slate-50 px-1.5 py-0.5 font-mono text-[11px] text-slate-600">/{{ $policy->slug }}</code></td>
                                <td>
                                    <x-admin.badge :variant="$policy->status === 'published' ? 'default' : 'muted'">
                                        {{ ucfirst($policy->status) }}
                                    </x-admin.badge>
                                </td>
                                <td class="text-xs text-slate-500">{{ optional($policy->updated_at)->format('M d, Y') ?? '—' }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="openEdit(@js($policy))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs text-slate-600 hover:bg-slate-100">Edit</button>
                                        <form action="{{ route('admin.policies.destroy', $policy->id) }}" method="POST" onsubmit="return confirm('Delete this policy page?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.button type="submit" variant="ghost" size="sm" class="text-red-600 hover:bg-red-50">Delete</x-admin.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="md:hidden divide-y divide-slate-100">
                @foreach($policies as $policy)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-sm text-slate-900">{{ $policy->title }}</p>
                                <p class="text-xs font-mono text-slate-400 mt-0.5">/{{ $policy->slug }}</p>
                            </div>
                            <x-admin.badge :variant="$policy->status === 'published' ? 'default' : 'muted'">
                                {{ ucfirst($policy->status) }}
                            </x-admin.badge>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" @click="openEdit(@js($policy))" class="inline-flex items-center justify-center gap-1.5 font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--admin-ring)] disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap h-8 rounded-[6px] px-2.5 text-xs border border-[var(--admin-border)] bg-white text-slate-700 hover:bg-[var(--admin-muted)] flex-1">Edit</button>
                            <form action="{{ route('admin.policies.destroy', $policy->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Delete this policy page?');">
                                @csrf
                                @method('DELETE')
                                <x-admin.button type="submit" variant="destructive" size="sm" class="w-full">Delete</x-admin.button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
