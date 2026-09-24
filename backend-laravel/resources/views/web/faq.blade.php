@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
    <div class="text-center space-y-2">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Frequently Asked Questions</h1>
        <p class="text-xs text-slate-500">Quick answers to common questions about ordering, shipping, and payments.</p>
    </div>

    <div class="bg-white p-6 sm:p-10 rounded-3xl border border-brand-green-100 shadow-soft space-y-4" x-data="{ open: 1 }">
        <div class="border border-slate-100 rounded-2xl overflow-hidden">
            <button type="button" @click="open = (open === 1 ? 0 : 1)" class="w-full text-left p-4 font-bold text-xs sm:text-sm text-slate-800 flex justify-between items-center bg-slate-50/50 hover:bg-slate-50">
                <span>How do I place an order?</span>
                <span x-text="open === 1 ? '−' : '+'" class="text-brand-green-600 font-extrabold text-base"></span>
            </button>
            <div x-show="open === 1" class="p-4 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                Browse our categories or search for desired products. Click "Add to Cart", then proceed to the Checkout page to enter your delivery address and choose your payment method.
            </div>
        </div>

        <div class="border border-slate-100 rounded-2xl overflow-hidden">
            <button type="button" @click="open = (open === 2 ? 0 : 2)" class="w-full text-left p-4 font-bold text-xs sm:text-sm text-slate-800 flex justify-between items-center bg-slate-50/50 hover:bg-slate-50">
                <span>Is Cash on Delivery available?</span>
                <span x-text="open === 2 ? '−' : '+'" class="text-brand-green-600 font-extrabold text-base"></span>
            </button>
            <div x-show="open === 2" class="p-4 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                Yes! We offer Cash on Delivery (COD) across all service areas in Bangladesh. You inspect your package upon delivery and pay the courier directly.
            </div>
        </div>

        <div class="border border-slate-100 rounded-2xl overflow-hidden">
            <button type="button" @click="open = (open === 3 ? 0 : 3)" class="w-full text-left p-4 font-bold text-xs sm:text-sm text-slate-800 flex justify-between items-center bg-slate-50/50 hover:bg-slate-50">
                <span>How can I track my order?</span>
                <span x-text="open === 3 ? '−' : '+'" class="text-brand-green-600 font-extrabold text-base"></span>
            </button>
            <div x-show="open === 3" class="p-4 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                Visit the <a href="{{ route('track') }}" class="font-bold text-brand-green-600 hover:underline">Track Order</a> page and enter either your Order ID (GHB-XXXXXX) or the phone number you used during checkout.
            </div>
        </div>

        <div class="border border-slate-100 rounded-2xl overflow-hidden">
            <button type="button" @click="open = (open === 4 ? 0 : 4)" class="w-full text-left p-4 font-bold text-xs sm:text-sm text-slate-800 flex justify-between items-center bg-slate-50/50 hover:bg-slate-50">
                <span>What is the return and refund policy?</span>
                <span x-text="open === 4 ? '−' : '+'" class="text-brand-green-600 font-extrabold text-base"></span>
            </button>
            <div x-show="open === 4" class="p-4 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                If an item is damaged or defective upon arrival, notify us within 7 days for a hassle-free replacement or full refund.
            </div>
        </div>
    </div>
</div>
@endsection
