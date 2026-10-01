@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-4xl" x-data="{
    selectedAudience: '{{ old('audience_filter', 'consented_customers') }}',
    counts: {{ json_encode($counts) }},
    get recipientCount() {
        return this.counts[this.selectedAudience] || 0;
    }
}">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Create Email Campaign</h1>
            <p class="text-xs text-slate-500">Design, audience-target, and queue a promotional broadcast or announcement.</p>
        </div>
        <div>
            <a href="{{ route('admin.email.campaigns') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Back to Campaigns
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.email.campaigns.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Campaign Details Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Campaign Details &amp; Subject</h2>
                <p class="text-xs text-slate-500">Internal campaign identifier and public email header information.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Campaign Name (Internal)</label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Eid Mega Sale Announcement" class="admin-control w-full text-xs">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Email Subject Line</label>
                    <input type="text" name="subject" required value="{{ old('subject') }}" placeholder="e.g. Exclusive Eid Discounts & Free Home Delivery!" class="admin-control w-full text-xs">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Sender Name (Optional)</label>
                    <input type="text" name="sender_name" value="{{ old('sender_name') }}" placeholder="Mama Bazar" class="admin-control w-full text-xs">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Sender Email (Optional)</label>
                    <input type="email" name="sender_email" value="{{ old('sender_email') }}" placeholder="contact@mama-bazar.com" class="admin-control w-full text-xs">
                </div>
            </div>
        </div>

        {{-- Audience Targeting Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Audience Group</h2>
                <p class="text-xs text-slate-500">Marketing emails strictly respect customer consent and exclude unsubscribed users.</p>
            </div>

            <div class="space-y-3">
                @foreach($audiences as $key => $label)
                    <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-brand-green-500 cursor-pointer transition">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="audience_filter" value="{{ $key }}" x-model="selectedAudience" class="text-brand-green-600 focus:ring-brand-green-500">
                            <span class="text-xs font-semibold text-slate-800">{{ $label }}</span>
                        </div>
                        <span class="text-xs font-bold text-brand-green-700 font-mono">
                            {{ number_format($counts[$key] ?? 0) }} recipients
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                <span>Total Target Audience:</span>
                <strong class="text-slate-900 text-sm font-black" x-text="recipientCount + ' Recipients'"></strong>
            </div>
        </div>

        {{-- Email Content Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Email Message Body</h2>
                <p class="text-xs text-slate-500">Enter HTML content. The message is automatically wrapped inside the Mama Bazar branded header, footer, and safe unsubscribe link.</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">HTML Message Body</label>
                <textarea name="body_html" required rows="10" class="admin-control w-full font-mono text-xs leading-relaxed" placeholder="<h2>Special Announcement</h2><p>Dear customer, enjoy flat 20% off on all organic vegetables this weekend.</p>">{{ old('body_html') }}</textarea>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Plain Text Fallback (Optional)</label>
                <textarea name="body_plain" rows="3" class="admin-control w-full font-mono text-xs leading-relaxed" placeholder="Auto-generated if left empty...">{{ old('body_plain') }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <button type="submit" name="action" value="save_draft" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition shadow-xs">
                Save Draft
            </button>
            <button type="submit" name="action" value="send_now" onclick="return confirm('Are you sure you want to queue this campaign for immediate broadcast?');" class="rounded-xl bg-brand-green-600 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-brand-green-700 transition">
                Queue &amp; Broadcast Campaign &rarr;
            </button>
        </div>

    </form>

</div>
@endsection
