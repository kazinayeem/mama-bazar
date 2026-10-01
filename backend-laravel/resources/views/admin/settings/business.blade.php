@extends('layouts.admin', ['headerTitle' => 'Business Information'])

@section('content')
<div class="admin-page" x-data="{
    activeTab: 'basic',
    saving: false,
    dirty: false
}">
    <x-admin.page-header title="Business Information" subtitle="Manage centralized store identity, helpline numbers, emails, addresses, and social links">
        <x-slot:actions>
            <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-1.5 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                General Settings
            </a>
            <a href="{{ url('/') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                View Storefront
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if(session('success'))
        <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800 shadow-sm">
            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-medium text-red-800 shadow-sm">
            <p class="font-bold mb-1">Please correct the following errors:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Container --}}
    <form action="{{ route('admin.settings.business.update') }}" method="POST" enctype="multipart/form-data" @submit="saving = true" class="space-y-6">
        @csrf

        {{-- Section Navigation Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 rounded-xl border border-[var(--admin-border)] bg-slate-100/70 p-1.5 text-xs font-semibold">
            <button type="button" @click="activeTab = 'basic'"
                    class="rounded-lg px-3.5 py-2 transition"
                    :class="activeTab === 'basic' ? 'bg-white text-brand-green-700 shadow-xs ring-1 ring-black/5 font-bold' : 'text-slate-600 hover:text-slate-900'">
                Basic Information
            </button>
            <button type="button" @click="activeTab = 'contact'"
                    class="rounded-lg px-3.5 py-2 transition"
                    :class="activeTab === 'contact' ? 'bg-white text-brand-green-700 shadow-xs ring-1 ring-black/5 font-bold' : 'text-slate-600 hover:text-slate-900'">
                Contact &amp; Helpline
            </button>
            <button type="button" @click="activeTab = 'address'"
                    class="rounded-lg px-3.5 py-2 transition"
                    :class="activeTab === 'address' ? 'bg-white text-brand-green-700 shadow-xs ring-1 ring-black/5 font-bold' : 'text-slate-600 hover:text-slate-900'">
                Business Address
            </button>
            <button type="button" @click="activeTab = 'social'"
                    class="rounded-lg px-3.5 py-2 transition"
                    :class="activeTab === 'social' ? 'bg-white text-brand-green-700 shadow-xs ring-1 ring-black/5 font-bold' : 'text-slate-600 hover:text-slate-900'">
                Online Presence
            </button>
            <button type="button" @click="activeTab = 'legal'"
                    class="rounded-lg px-3.5 py-2 transition"
                    :class="activeTab === 'legal' ? 'bg-white text-brand-green-700 shadow-xs ring-1 ring-black/5 font-bold' : 'text-slate-600 hover:text-slate-900'">
                Legal &amp; Footer
            </button>
        </div>

        {{-- TAB 1: Basic Information --}}
        <div x-show="activeTab === 'basic'" x-cloak class="admin-surface p-6 sm:p-8 space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Basic Business Identity</h2>
                <p class="text-xs text-slate-500">Official business name, website branding, logo, and favicon.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Business Name <span class="text-red-500">*</span></label>
                    <input type="text" name="business_name" required value="{{ old('business_name', $business['business_name'] ?? 'Mama Bazar') }}" class="admin-control w-full text-xs" placeholder="e.g. Mama Bazar">
                    <p class="mt-1 text-[11px] text-slate-400">Used across invoices, storefront header, PDFs, and legal texts.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Website Name</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $business['site_name'] ?? 'Mama Bazar') }}" class="admin-control w-full text-xs" placeholder="e.g. Mama Bazar Online Store">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Business Tagline</label>
                    <input type="text" name="tagline" value="{{ old('tagline', $business['tagline'] ?? 'Online Grocery & Lifestyle Essentials') }}" class="admin-control w-full text-xs" placeholder="e.g. Fresh Groceries & Daily Needs">
                    <p class="mt-1 text-[11px] text-slate-400">Appears under invoice header and storefront promotional materials.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Business Description</label>
                    <textarea name="business_description" rows="3" class="admin-control w-full text-xs" placeholder="Short description of your business...">{{ old('business_description', $business['business_description'] ?? '') }}</textarea>
                </div>

                {{-- Logo Upload --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-700">Business Logo</label>
                        @if(!empty($business['logo_url']))
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">Configured</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 bg-white p-2 flex items-center justify-center shadow-xs">
                            <img src="{{ $business['logo_url'] ?: '/brandlogo.png' }}" alt="Logo" class="max-h-full max-w-full object-contain">
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <input type="file" name="logo_file" accept="image/*" class="w-full text-xs admin-control p-1.5 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-brand-green-50 file:text-brand-green-700">
                            <input type="text" name="logo_url" value="{{ old('logo_url', $business['logo_url'] ?? '') }}" placeholder="Or paste image URL (e.g. /brandlogo.png)" class="admin-control w-full text-[11px]">
                        </div>
                    </div>
                </div>

                {{-- Favicon Upload --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-700">Favicon</label>
                        @if(!empty($business['favicon_url']))
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">Configured</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 bg-white p-2 flex items-center justify-center shadow-xs">
                            <img src="{{ $business['favicon_url'] ?: '/brandlogo.png' }}" alt="Favicon" class="h-8 w-8 object-contain">
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <input type="file" name="favicon_file" accept="image/*" class="w-full text-xs admin-control p-1.5 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-brand-green-50 file:text-brand-green-700">
                            <input type="text" name="favicon_url" value="{{ old('favicon_url', $business['favicon_url'] ?? '') }}" placeholder="Or paste favicon URL" class="admin-control w-full text-[11px]">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: Contact Information --}}
        <div x-show="activeTab === 'contact'" x-cloak class="admin-surface p-6 sm:p-8 space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Contact &amp; Helpline Channels</h2>
                <p class="text-xs text-slate-500">Numbers and emails displayed to customers across the storefront, order tracking, and invoices.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Primary Phone / Helpline <span class="text-red-500">*</span></label>
                    <input type="text" name="primary_phone" required value="{{ old('primary_phone', $business['primary_phone'] ?? '01700-000000') }}" class="admin-control w-full text-xs" placeholder="e.g. 01700-000000">
                    <p class="mt-1 text-[11px] text-slate-400">Header helpline, footer phone, and invoice contact phone.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $business['whatsapp_number'] ?? '') }}" class="admin-control w-full text-xs" placeholder="e.g. 01700000000">
                    <p class="mt-1 text-[11px] text-slate-400">Creates clickable direct WhatsApp chat links (`wa.me`).</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Secondary Phone</label>
                    <input type="text" name="secondary_phone" value="{{ old('secondary_phone', $business['secondary_phone'] ?? '') }}" class="admin-control w-full text-xs" placeholder="Optional backup phone">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Support Phone</label>
                    <input type="text" name="support_phone" value="{{ old('support_phone', $business['support_phone'] ?? '') }}" class="admin-control w-full text-xs" placeholder="Support desk number">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Primary Email <span class="text-red-500">*</span></label>
                    <input type="email" name="primary_email" required value="{{ old('primary_email', $business['primary_email'] ?? 'support@mamabazar.com') }}" class="admin-control w-full text-xs" placeholder="e.g. info@mamabazar.com">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Customer Support Email</label>
                    <input type="email" name="support_email" value="{{ old('support_email', $business['support_email'] ?? 'support@mamabazar.com') }}" class="admin-control w-full text-xs" placeholder="e.g. support@mamabazar.com">
                    <p class="mt-1 text-[11px] text-slate-400">Displayed in footer and contact inquiries section.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Sales / Vendor Email</label>
                    <input type="email" name="sales_email" value="{{ old('sales_email', $business['sales_email'] ?? '') }}" class="admin-control w-full text-xs" placeholder="e.g. sales@mamabazar.com">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Support Desk URL</label>
                    <input type="text" name="support_url" value="{{ old('support_url', $business['support_url'] ?? '/contact') }}" class="admin-control w-full text-xs" placeholder="/contact or helpdesk URL">
                </div>
            </div>
        </div>

        {{-- TAB 3: Business Address --}}
        <div x-show="activeTab === 'address'" x-cloak class="admin-surface p-6 sm:p-8 space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Physical Business Address</h2>
                <p class="text-xs text-slate-500">Warehouse and corporate office location printed on invoices, packing slips, and footer.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Address Line 1</label>
                    <input type="text" name="address_line1" value="{{ old('address_line1', $business['address_line1'] ?? '') }}" class="admin-control w-full text-xs" placeholder="e.g. House 12, Road 4">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Address Line 2 (Optional)</label>
                    <input type="text" name="address_line2" value="{{ old('address_line2', $business['address_line2'] ?? '') }}" class="admin-control w-full text-xs" placeholder="e.g. Sector 3, Uttara">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">City</label>
                    <input type="text" name="city" value="{{ old('city', $business['city'] ?? 'Dhaka') }}" class="admin-control w-full text-xs" placeholder="e.g. Dhaka">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">District / Division</label>
                    <input type="text" name="district" value="{{ old('district', $business['district'] ?? 'Dhaka') }}" class="admin-control w-full text-xs" placeholder="e.g. Dhaka">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Postal Code</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $business['postal_code'] ?? '1230') }}" class="admin-control w-full text-xs" placeholder="e.g. 1230">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Country</label>
                    <input type="text" name="country" value="{{ old('country', $business['country'] ?? 'Bangladesh') }}" class="admin-control w-full text-xs" placeholder="e.g. Bangladesh">
                </div>

                <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                    <p class="text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Synthesized Address Preview</p>
                    <p class="text-xs font-medium text-slate-900">{{ $business['formatted_address'] }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">This combined address will appear on customer invoices and in the website footer.</p>
                </div>
            </div>
        </div>

        {{-- TAB 4: Online Presence --}}
        <div x-show="activeTab === 'social'" x-cloak class="admin-surface p-6 sm:p-8 space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Website &amp; Social Media Links</h2>
                <p class="text-xs text-slate-500">Official website URL and public social profiles linked in the footer.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Official Website URL</label>
                    <input type="url" name="website_url" value="{{ old('website_url', $business['website_url'] ?? 'https://mamabazar.com') }}" class="admin-control w-full text-xs" placeholder="https://mamabazar.com">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Facebook Page URL</label>
                    <input type="url" name="facebook_url" value="{{ old('facebook_url', $business['facebook_url'] ?? '') }}" class="admin-control w-full text-xs" placeholder="https://facebook.com/...">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Instagram Profile URL</label>
                    <input type="url" name="instagram_url" value="{{ old('instagram_url', $business['instagram_url'] ?? '') }}" class="admin-control w-full text-xs" placeholder="https://instagram.com/...">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">YouTube Channel URL</label>
                    <input type="url" name="youtube_url" value="{{ old('youtube_url', $business['youtube_url'] ?? '') }}" class="admin-control w-full text-xs" placeholder="https://youtube.com/@...">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">LinkedIn Page URL</label>
                    <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $business['linkedin_url'] ?? '') }}" class="admin-control w-full text-xs" placeholder="https://linkedin.com/company/...">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">TikTok Profile URL</label>
                    <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $business['tiktok_url'] ?? '') }}" class="admin-control w-full text-xs" placeholder="https://tiktok.com/@...">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Twitter / X Profile URL</label>
                    <input type="url" name="twitter_url" value="{{ old('twitter_url', $business['twitter_url'] ?? '') }}" class="admin-control w-full text-xs" placeholder="https://x.com/...">
                </div>
            </div>
        </div>

        {{-- TAB 5: Legal & Footer --}}
        <div x-show="activeTab === 'legal'" x-cloak class="admin-surface p-6 sm:p-8 space-y-6">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Legal, Compliance &amp; Footer Information</h2>
                <p class="text-xs text-slate-500">Business registration codes, copyright text, and policy navigation links.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Business Registration / Trade License / BIN</label>
                    <input type="text" name="business_registration" value="{{ old('business_registration', $business['business_registration'] ?? '') }}" class="admin-control w-full text-xs" placeholder="e.g. BIN-123456789-0101">
                    <p class="mt-1 text-[11px] text-slate-400">Printed on official invoices and legal documents.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Copyright Text</label>
                    <input type="text" name="copyright_text" value="{{ old('copyright_text', $business['copyright_text'] ?? '© :year MamaBazar. All rights reserved.') }}" class="admin-control w-full text-xs">
                    <p class="mt-1 text-[11px] text-slate-400">Use <code>:year</code> to dynamically output the current year.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Footer Brand Description</label>
                    <textarea name="footer_description" rows="2" class="admin-control w-full text-xs">{{ old('footer_description', $business['footer_description'] ?? '') }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Return &amp; Refund Policy URL</label>
                    <input type="text" name="return_policy_url" value="{{ old('return_policy_url', $business['return_policy_url'] ?? '/pages/return-refund') }}" class="admin-control w-full text-xs">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Privacy Policy URL</label>
                    <input type="text" name="privacy_policy_url" value="{{ old('privacy_policy_url', $business['privacy_policy_url'] ?? '/pages/privacy-policy') }}" class="admin-control w-full text-xs">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Terms &amp; Conditions URL</label>
                    <input type="text" name="terms_url" value="{{ old('terms_url', $business['terms_url'] ?? '/pages/terms') }}" class="admin-control w-full text-xs">
                </div>

                {{-- Software attribution note --}}
                <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50/70 p-4 flex items-start gap-3">
                    <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-200">
                        BS
                    </div>
                    <div class="text-xs">
                        <p class="font-bold text-slate-800">Software Attribution Guarantee</p>
                        <p class="text-slate-500 mt-0.5">
                            Mama Bazar is powered by <a href="https://bornosoft.bd/" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-green-700 underline">Bornosoft</a>. The software credit in the footer is maintained separately and cannot be accidentally corrupted or removed by business setting edits.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Fixed / Sticky Save Actions --}}
        <div class="sticky bottom-4 z-20 flex items-center justify-between rounded-xl border border-[var(--admin-border)] bg-white/95 px-6 py-4 shadow-xl backdrop-blur-md">
            <div class="text-xs text-slate-500">
                <span>Changes will immediately update across the entire storefront, invoices, and PDFs.</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.settings.business') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Reset
                </a>
                <x-admin.button type="submit" size="sm" ::disabled="saving">
                    <span x-show="saving" class="mr-1.5 h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    <svg x-show="!saving" class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Business Information
                </x-admin.button>
            </div>
        </div>
    </form>
</div>
@endsection
