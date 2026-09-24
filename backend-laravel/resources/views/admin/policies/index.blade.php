@extends('layouts.admin', ['headerTitle' => 'Policies & Messages'])

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="admin-page-title">Policies & Messages</h1>
        <p class="text-sm text-slate-500">Legal pages and store policy content</p>
    </div>
    <button type="button" onclick="document.getElementById('policy-create').classList.toggle('hidden')"
            class="inline-flex h-10 items-center justify-center rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white hover:bg-brand-green-600">Add Policy</button>
</div>

<div id="policy-create" class="mt-4 hidden admin-surface p-4">
    <form action="{{ route('admin.policies.store') }}" method="POST" class="grid gap-3 sm:grid-cols-2">
        @csrf
        <div><label class="mb-1 block text-xs font-bold">Title *</label><input name="title" required class="admin-control w-full text-sm"></div>
        <div><label class="mb-1 block text-xs font-bold">Slug *</label><input name="slug" required class="admin-control w-full text-sm font-mono" placeholder="return-refund"></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold">Content (HTML)</label><textarea name="content" rows="8" class="admin-control w-full text-sm font-mono"></textarea></div>
        <div>
            <label class="mb-1 block text-xs font-bold">Status</label>
            <select name="status" class="admin-control w-full text-sm">
                <option value="published">Published</option>
                <option value="draft">Draft</option>
            </select>
        </div>
        <div class="flex items-end justify-end"><button class="inline-flex h-10 items-center justify-center rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white">Create</button></div>
    </form>
</div>

<div class="mt-4 space-y-4">
    @forelse($policies as $policy)
        <details class="admin-table-wrap" {{ $loop->first ? 'open' : '' }}>
            <summary class="flex cursor-pointer items-center justify-between px-5 py-4 hover:bg-slate-50">
                <div>
                    <p class="font-bold text-slate-900">{{ $policy->title }}</p>
                    <p class="text-xs font-mono text-slate-400">/{{ $policy->slug }} · {{ $policy->status }}</p>
                </div>
                <span class="text-xs font-semibold text-brand-green-700">Edit</span>
            </summary>
            <form action="{{ route('admin.policies.update', $policy->id) }}" method="POST" class="border-t p-5 grid gap-3 sm:grid-cols-2">
                @csrf @method('PUT')
                <div><label class="mb-1 block text-xs font-bold">Title</label><input name="title" value="{{ $policy->title }}" class="admin-control w-full text-sm"></div>
                <div><label class="mb-1 block text-xs font-bold">Slug</label><input name="slug" value="{{ $policy->slug }}" class="admin-control w-full text-sm font-mono"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold">Content</label><textarea name="content" rows="10" class="admin-control w-full text-sm font-mono">{{ $policy->content }}</textarea></div>
                <div>
                    <select name="status" class="admin-control w-full text-sm">
                        <option value="published" @selected($policy->status==='published')>Published</option>
                        <option value="draft" @selected($policy->status==='draft')>Draft</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button formaction="{{ route('admin.policies.destroy', $policy->id) }}" formmethod="POST" onclick="event.preventDefault(); if(confirm('Delete?')) { this.closest('form').querySelector('[name=_method]')?.remove(); const f=this.closest('form'); const m=document.createElement('input'); m.type='hidden'; m.name='_method'; m.value='DELETE'; f.appendChild(m); f.action=this.getAttribute('formaction'); f.submit(); }"
                            class="rounded-full border border-red-200 px-4 py-2 text-xs font-semibold text-red-600">Delete</button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white">Save</button>
                </div>
            </form>
        </details>
    @empty
        <div class="rounded-xl border bg-white p-12 text-center text-sm text-slate-500">No policy pages yet</div>
    @endforelse
</div>
@endsection
