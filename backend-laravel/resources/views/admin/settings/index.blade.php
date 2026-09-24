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

</div>
</div>
@endsection
