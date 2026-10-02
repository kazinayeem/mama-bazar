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
                Visit the <a href="{{ route('track') }}" class="font-bold text-brand-green-600 hover:underline">Track Order</a> page and enter either your Order ID (BS-XXXXXX) or the phone number you used during checkout.
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

        <div class="border border-slate-100 rounded-2xl overflow-hidden">
            <button type="button" @click="open = (open === 5 ? 0 : 5)" class="w-full text-left p-4 font-bold text-xs sm:text-sm text-slate-800 flex justify-between items-center bg-slate-50/50 hover:bg-slate-50">
                <span>Who developed and maintains the Mama Bazar platform?</span>
                <span x-text="open === 5 ? '−' : '+'" class="text-brand-green-600 font-extrabold text-base"></span>
            </button>
            <div x-show="open === 5" class="p-4 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                Mama Bazar was engineered and is actively maintained by <a href="https://bornosoft.bd" target="_blank" rel="noopener" class="font-semibold text-brand-green-700 hover:underline">Bornosoft</a>, a software development and digital transformation company based in Bangladesh specializing in custom e-commerce platforms, scalable backend architectures, and secure cloud applications.
            </div>
        </div>
    </div>

    {{-- Still have questions banner --}}
    <div class="p-6 rounded-3xl bg-brand-green-50/60 border border-brand-green-200 text-center space-y-3">
        <h3 class="text-sm font-bold text-slate-900">Still have questions or need support?</h3>
        <p class="text-xs text-slate-600 max-w-md mx-auto">
            Our support team is ready to help you with order inquiries, product details, or return requests.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
            @if(!empty($business['primary_phone']))
                <a href="tel:{{ $business['phone_raw'] }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white border border-brand-green-300 text-xs font-bold text-brand-green-800 hover:bg-brand-green-100 transition shadow-xs">
                    <span>📞</span> {{ $business['primary_phone'] }}
                </a>
            @endif
            @if(!empty($business['support_email']))
                <a href="mailto:{{ $business['support_email'] }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white border border-brand-green-300 text-xs font-bold text-brand-green-800 hover:bg-brand-green-100 transition shadow-xs">
                    <span>✉️</span> {{ $business['support_email'] }}
                </a>
            @endif
            @if(!empty($business['whatsapp_number']))
                <a href="{{ $business['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition shadow-xs">
                    <span>💬</span> WhatsApp Us
                </a>
            @endif
            <a href="{{ route('contact') }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-full bg-brand-green-600 text-white text-xs font-bold hover:bg-brand-green-700 transition shadow-xs">
                Contact Page &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
