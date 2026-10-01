@extends('layouts.admin', ['headerTitle' => 'Review #' . $review->id])

@section('content')
<div class="max-w-3xl mx-auto space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
        <div>
            <span class="text-xs text-slate-400 font-semibold uppercase">Review Details</span>
            <h2 class="text-xl font-black text-slate-900">{{ $review->title ?: 'Untitled review' }}</h2>
            <p class="text-xs text-slate-500">{{ $review->created_at?->format('M d, Y h:i A') }} · {{ $review->product?->title }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-admin.badge :variant="$review->status === 'approved' ? 'success' : ($review->status === 'rejected' ? 'destructive' : 'warning')">{{ $review->status }}</x-admin.badge>
            <a href="{{ route('admin.reviews.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; All Reviews</a>
        </div>
    </div>

    <div class="admin-surface p-5 space-y-3 text-sm">
        <div class="flex items-center gap-2">
            <span class="text-lg font-black text-amber-500">{{ str_repeat('★', $review->rating) }}</span>
            <span class="text-xs text-slate-400">{{ str_repeat('☆', max(0, 5 - $review->rating)) }} · {{ $review->rating }}/5</span>
            @if($review->is_verified_purchase)<span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Verified purchase</span>@endif
            @if($review->is_featured)<span class="text-[11px] font-bold text-brand-orange-600 bg-brand-orange-50 px-2 py-0.5 rounded-full">Featured</span>@endif
        </div>
        <p class="text-slate-800 leading-6">{{ $review->comment }}</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100">
            <p><span class="text-slate-400 font-semibold">Customer:</span> {{ $review->customer_name }}@if($review->user) ({{ $review->user->name }})@endif</p>
            <p><span class="text-slate-400 font-semibold">Product:</span> {{ $review->product?->title ?: '—' }}</p>
            @if($review->approver)<p><span class="text-slate-400 font-semibold">Moderated by:</span> {{ $review->approver->name }} · {{ $review->approved_at?->format('M d, Y') }}</p>@endif
        </div>
        @if($review->admin_note)
            <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 text-xs"><span class="font-bold text-slate-600">Admin note:</span> {{ $review->admin_note }}</div>
        @endif
    </div>

    <div class="admin-surface p-5">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Moderate Review</h3>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('admin.reviews.status', $review->id) }}" method="POST" class="inline-flex items-center gap-2">
                @csrf
                <input type="hidden" name="status" value="approved">
                <x-admin.button type="submit" size="sm">Approve</x-admin.button>
            </form>
            <form action="{{ route('admin.reviews.status', $review->id) }}" method="POST" class="inline-flex items-center gap-2">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <x-admin.button type="submit" size="sm" variant="outline">Reject</x-admin.button>
            </form>
            <form action="{{ route('admin.reviews.featured', $review->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="featured" value="{{ $review->is_featured ? '0' : '1' }}">
                <x-admin.button type="submit" size="sm" variant="outline">{{ $review->is_featured ? 'Unfeature' : 'Feature' }}</x-admin.button>
            </form>
            <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this review permanently?');">
                @csrf @method('DELETE')
                <x-admin.button type="submit" size="sm" variant="destructive">Delete</x-admin.button>
            </form>
        </div>
        <form action="{{ route('admin.reviews.status', $review->id) }}" method="POST" class="mt-3 flex flex-wrap items-end gap-2">
            @csrf
            <input type="hidden" name="status" value="{{ $review->status }}">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Internal admin note</label>
                <input type="text" name="admin_note" value="{{ old('admin_note', $review->admin_note) }}" placeholder="Optional note (kept with review)" class="w-full text-xs rounded-xl border border-slate-200 p-2.5">
            </div>
            <x-admin.button type="submit" size="sm" variant="outline">Save Note</x-admin.button>
        </form>
    </div>

    <div class="admin-surface p-5">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Edit Review Content</h3>
        <form action="{{ route('admin.reviews.update', $review->id) }}" method="POST" class="space-y-3">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Rating (1–5) *</label>
                    <select name="rating" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 bg-white">
                        @for($r = 1; $r <= 5; $r++)<option value="{{ $r }}" @selected(old('rating', $review->rating) == $r)>{{ $r }} ★</option>@endfor
                    </select>
                    @error('rating')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title', $review->title) }}" maxlength="200" class="w-full text-xs rounded-xl border border-slate-200 p-2.5">
                    @error('title')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Comment *</label>
                <textarea name="comment" rows="4" maxlength="5000" class="w-full text-xs rounded-xl border border-slate-200 p-2.5">{{ old('comment', $review->comment) }}</textarea>
                @error('comment')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
            </div>
            <x-admin.button type="submit" size="sm">Save Changes</x-admin.button>
        </form>
    </div>
</div>
@endsection
