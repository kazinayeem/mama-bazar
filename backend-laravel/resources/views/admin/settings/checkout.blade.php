@extends('layouts.admin', ['headerTitle' => 'Checkout Settings'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Checkout Configuration" subtitle="Store-wide checkout rules · minimum order, defaults, COD note" />
    <div class="max-w-2xl space-y-4">
        <form action="{{ route('admin.checkout-settings.update') }}" method="POST" class="admin-surface space-y-3 p-4 sm:p-5">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Minimum Order Amount (৳)</label>
                    <input type="number" step="0.01" min="0" name="min_order_amount" value="{{ old('min_order_amount', $checkout['min_order_amount'] ?? 0) }}" class="admin-control w-full text-xs">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Global Free-Shipping Threshold (৳)</label>
                    <input type="number" step="0.01" min="0" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $checkout['free_shipping_threshold'] ?? '') }}" placeholder="Optional" class="admin-control w-full text-xs">
                    <p class="mt-1 text-[11px] text-slate-400">Optional fallback when a method has no per-method threshold.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Default District</label>
                    <input type="text" name="default_district" value="{{ old('default_district', $checkout['default_district'] ?? 'Dhaka') }}" class="admin-control w-full text-xs">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">COD Note (shown at checkout)</label>
                    <input type="text" name="cod_note" value="{{ old('cod_note', $checkout['cod_note'] ?? '') }}" placeholder="e.g. Pay in cash on delivery" class="admin-control w-full text-xs">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Related pages</label>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="{{ route('admin.shipping.index') }}" class="rounded-[6px] border border-[var(--admin-border)] px-2.5 py-1.5 font-semibold text-brand-green-700 hover:bg-brand-green-50">Shipping & Delivery</a>
                    <a href="{{ route('admin.payment-methods.index') }}" class="rounded-[6px] border border-[var(--admin-border)] px-2.5 py-1.5 font-semibold text-brand-green-700 hover:bg-brand-green-50">Payment Methods</a>
                    <a href="{{ route('admin.checkout-notices.index') }}" class="rounded-[6px] border border-[var(--admin-border)] px-2.5 py-1.5 font-semibold text-brand-green-700 hover:bg-brand-green-50">Delivery Notices</a>
                    <a href="{{ route('admin.coupons.index') }}" class="rounded-[6px] border border-[var(--admin-border)] px-2.5 py-1.5 font-semibold text-brand-green-700 hover:bg-brand-green-50">Coupons</a>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-[var(--admin-border)] pt-3">
                <x-admin.button type="submit" size="sm">Save Checkout Settings</x-admin.button>
            </div>
        </form>
    </div>
</div>
@endsection
