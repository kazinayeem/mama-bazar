@extends('layouts.admin', ['headerTitle' => 'Store Settings'])

@section('content')
<div class="admin-page">
<div class="max-w-2xl mx-auto space-y-6">

    <div class="pb-4 border-b border-slate-200">
        <h2 class="text-lg font-bold text-slate-900">General Store Configuration</h2>
        <p class="text-xs text-slate-500">Configure global shop information, contact details, and tax rules.</p>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" class="bg-white p-6 sm:p-8 admin-surface space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Store Name</label>
            <input type="text" name="store_name" value="{{ $settings['store_name'] ?? 'Mama Bazar' }}" 
                class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Helpline Phone Number</label>
            <input type="text" name="primary_phone" value="{{ $settings['primary_phone'] ?? '01700-000000' }}" 
                class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Support Email Address</label>
            <input type="email" name="email" value="{{ $settings['email'] ?? 'support@mamabazar.com' }}" 
                class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Office / Warehouse Address</label>
            <textarea name="contact_address" rows="2" class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none">{{ $settings['contact_address'] ?? 'Dhaka, Bangladesh' }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">VAT / Tax Rate (%)</label>
            <input type="number" step="0.1" name="tax_rate" value="{{ $settings['tax_rate'] ?? '0' }}" 
                class="w-full text-xs admin-control focus:border-brand-green-500 focus:outline-none">
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-[8px] bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs  transition">
                Save Store Settings
            </button>
        </div>
    </form>

    <div class="rounded-xl border border-slate-200 bg-white p-5 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-900">Admin Account Security</p>
                <p class="text-[11px] text-slate-500">Update your administrator account password and credentials.</p>
            </div>
        </div>
        <a href="{{ route('admin.profile.password') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[6px] border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
            Change Password &rarr;
        </a>
    </div>

</div>
</div>
@endsection
