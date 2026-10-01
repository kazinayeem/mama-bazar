@extends('layouts.admin', ['headerTitle' => $campaign->exists ? 'Edit Campaign' : 'New Campaign'])

@section('content')
@php
    $content = (array) ($campaign->content_json ?? []);
    $params = (array) ($campaign->audience_params ?? []);
    $audience = old('audience_filter', $campaign->audience_filter);
@endphp
<div class="admin-page max-w-5xl" x-data="{
    audience: @js($audience),
    count: null,
    loading: false,
    refreshCount() {
        this.loading = true;
        const form = this.$refs.form;
        const data = new FormData();
        data.append('_token', form.querySelector('[name=_token]').value);
        data.append('audience_filter', this.audience);
        data.append('days', form.querySelector('[name=days]').value || '');
        data.append('emails', form.querySelector('[name=emails]').value || '');
        form.querySelectorAll('[name=\'product_ids[]\'] option:checked').forEach(o => data.append('product_ids[]', o.value));
        fetch(@js(route('admin.email.campaigns.audience-count')), { method: 'POST', headers: { 'Accept': 'application/json' }, body: data })
            .then(r => r.ok ? r.json() : { count: null })
            .then(d => { this.count = d.count; this.loading = false; })
            .catch(() => { this.loading = false; });
    },
}" x-init="refreshCount()">
    <x-admin.page-header :title="$campaign->exists ? 'Edit: '.$campaign->name : 'New Campaign'" subtitle="Saving creates a draft. Nothing is sent until you review and confirm on the next screen.">
        <x-slot:actions>
            <x-admin.button :href="$campaign->exists ? route('admin.email.campaigns.show', $campaign->id) : route('admin.email.campaigns.index')" variant="outline" size="sm">Cancel</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    @include('admin.email.partials.tabs')

    <form x-ref="form" action="{{ $campaign->exists ? route('admin.email.campaigns.update', $campaign->id) : route('admin.email.campaigns.store') }}" method="POST" class="space-y-4">
        @csrf
        @if($campaign->exists) @method('PUT') @endif

        <div class="admin-surface p-5 space-y-4">
            <h2 class="text-sm font-bold text-slate-900">1. Basics</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Internal name</label>
                    <input type="text" name="name" required maxlength="191" value="{{ old('name', $campaign->name) }}" class="admin-control w-full" placeholder="e.g. Eid offer 2026">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Template</label>
                    <select name="template_key" class="admin-control w-full">
                        @foreach($templates as $key => $label)
                            <option value="{{ $key }}" @selected(old('template_key', $campaign->template_key) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Email subject</label>
                    <input type="text" name="subject" required maxlength="191" value="{{ old('subject', $campaign->subject) }}" class="admin-control w-full">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Preview text (optional)</label>
                    <input type="text" name="preheader" maxlength="191" value="{{ old('preheader', $content['preheader'] ?? '') }}" class="admin-control w-full" placeholder="Shown after the subject in most inboxes">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Sender name (optional)</label>
                    <input type="text" name="sender_name" maxlength="120" value="{{ old('sender_name', $campaign->sender_name) }}" class="admin-control w-full" placeholder="{{ $defaultSender }}">
                    <p class="mt-1 text-[11px] text-slate-400">The sender address is always the configured SMTP mailbox.</p>
                </div>
            </div>
        </div>

        <div class="admin-surface p-5 space-y-4">
            <h2 class="text-sm font-bold text-slate-900">2. Content</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Headline</label>
                    <input type="text" name="announcement_title" maxlength="191" value="{{ old('announcement_title', $content['announcement_title'] ?? '') }}" class="admin-control w-full">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Message</label>
                    <textarea name="announcement_body" rows="8" class="admin-control w-full text-sm">{{ old('announcement_body', $content['announcement_body'] ?? '') }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-400">Basic HTML allowed (&lt;p&gt;, &lt;strong&gt;, &lt;a&gt;, &lt;ul&gt;, &lt;img&gt;…). Scripts, forms and styles that load external code are removed.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Button label</label>
                    <input type="text" name="cta_text" maxlength="60" value="{{ old('cta_text', $content['cta_text'] ?? 'Shop Now') }}" class="admin-control w-full">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Button link</label>
                    <input type="text" name="cta_url" maxlength="500" value="{{ old('cta_url', $content['cta_url'] ?? '') }}" class="admin-control w-full" placeholder="https://… or /shop">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Hero image URL (optional)</label>
                    <input type="text" name="hero_image_url" maxlength="500" value="{{ old('hero_image_url', $content['hero_image_url'] ?? '') }}" class="admin-control w-full" placeholder="https://… (copy from the Media Library)">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Coupon code (optional)</label>
                    <input type="text" name="coupon_code" maxlength="50" value="{{ old('coupon_code', $content['coupon_code'] ?? '') }}" class="admin-control w-full">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Offer valid until (optional)</label>
                    <input type="text" name="offer_expires" maxlength="60" value="{{ old('offer_expires', $content['offer_expires'] ?? '') }}" class="admin-control w-full" placeholder="e.g. 15 October 2026">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Featured products (up to 6)</label>
                    @php $featured = array_map('intval', old('featured_product_ids', $content['product_ids'] ?? [])); @endphp
                    <select name="featured_product_ids[]" multiple size="6" class="admin-control h-auto w-full">
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(in_array($product->id, $featured, true))>{{ $product->title }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">Hold Ctrl/⌘ to select several. Shown as product cards in templates that include them.</p>
                </div>
            </div>
        </div>

        <div class="admin-surface p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-bold text-slate-900">3. Audience</h2>
                <span class="rounded-full bg-brand-green-50 px-3 py-1 text-xs font-semibold text-brand-green-800">
                    <span x-show="loading">Counting…</span>
                    <span x-show="! loading && count !== null" x-text="count + ' eligible recipients'"></span>
                </span>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach($audiences as $key => $meta)
                    <label class="flex cursor-pointer items-start gap-2 rounded-[6px] border p-3 text-xs" :class="audience === @js($key) ? 'border-brand-green-400 bg-brand-green-50' : 'border-slate-200'">
                        <input type="radio" name="audience_filter" value="{{ $key }}" x-model="audience" @change="refreshCount()" class="mt-0.5 text-brand-green-600">
                        <span><span class="block font-semibold text-slate-800">{{ $meta['label'] }}</span><span class="text-slate-500">{{ $meta['description'] }}</span></span>
                    </label>
                @endforeach
            </div>

            <div x-show="audience === 'recent_registered' || audience === 'inactive_customers'" class="max-w-xs">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Number of days</label>
                <input type="number" name="days" min="1" max="3650" value="{{ old('days', $params['days'] ?? '') }}" @change="refreshCount()" class="admin-control w-full" placeholder="30 / 60">
            </div>
            <div x-show="audience === 'product_buyers'">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Customers who bought any of</label>
                @php $buyerIds = array_map('intval', old('product_ids', $params['product_ids'] ?? [])); @endphp
                <select name="product_ids[]" multiple size="6" @change="refreshCount()" class="admin-control h-auto w-full">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(in_array($product->id, $buyerIds, true))>{{ $product->title }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="audience === 'custom'">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Email addresses (one per line or comma-separated)</label>
                <textarea name="emails" rows="5" @change="refreshCount()" class="admin-control w-full font-mono text-xs">{{ old('emails', $params['emails'] ?? '') }}</textarea>
                <p class="mt-1 text-[11px] text-slate-400">Only addresses that have opted in to marketing (account, newsletter or checkout consent) will receive the campaign.</p>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <x-admin.button type="submit">Save draft &amp; continue</x-admin.button>
        </div>
    </form>
</div>
@endsection
