@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">

    <div class="text-center space-y-2">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Contact {{ $business['business_name'] }}</h1>
        <p class="text-xs text-slate-500">Have questions about an order or our service? We are always here to help.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Contact Cards -->
        <div class="space-y-4">
            <div class="p-5 rounded-2xl bg-white border border-brand-green-100 shadow-soft">
                <span class="text-xl">📞</span>
                <h3 class="text-xs font-bold text-slate-900 mt-2">Customer Helpline</h3>
                <p class="text-xs text-brand-green-700 font-semibold mt-1">
                    <a href="tel:{{ $business['phone_raw'] }}" class="hover:underline">{{ $business['primary_phone'] }}</a>
                </p>
                @if(!empty($business['secondary_phone']))
                    <p class="text-xs text-slate-600 mt-0.5">
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $business['secondary_phone']) }}" class="hover:underline">{{ $business['secondary_phone'] }}</a>
                    </p>
                @endif
                <p class="text-[11px] text-slate-400 mt-1">Available 9:00 AM - 10:00 PM</p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-brand-green-100 shadow-soft">
                <span class="text-xl">✉️</span>
                <h3 class="text-xs font-bold text-slate-900 mt-2">Email Inquiries</h3>
                <p class="text-xs text-brand-green-700 font-semibold mt-1">
                    <a href="mailto:{{ $business['support_email'] }}" class="hover:underline">{{ $business['support_email'] }}</a>
                </p>
                @if(!empty($business['sales_email']) && $business['sales_email'] !== $business['support_email'])
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Sales: <a href="mailto:{{ $business['sales_email'] }}" class="hover:underline">{{ $business['sales_email'] }}</a>
                    </p>
                @endif
                <p class="text-[11px] text-slate-400 mt-1">We reply within 24 business hours</p>
            </div>

            @if(!empty($business['whatsapp_number']))
                <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-soft">
                    <span class="text-xl">💬</span>
                    <h3 class="text-xs font-bold text-emerald-950 mt-2">WhatsApp Support</h3>
                    <p class="text-xs text-emerald-700 font-semibold mt-1">
                        <a href="{{ $business['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="hover:underline">{{ $business['whatsapp_number'] }}</a>
                    </p>
                    <a href="{{ $business['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-block mt-2 text-[11px] font-bold text-emerald-800 bg-white px-3 py-1 rounded-full border border-emerald-200 hover:bg-emerald-100 transition">
                        Chat on WhatsApp &rarr;
                    </a>
                </div>
            @endif

            <div class="p-5 rounded-2xl bg-white border border-brand-green-100 shadow-soft">
                <span class="text-xl">📍</span>
                <h3 class="text-xs font-bold text-slate-900 mt-2">Corporate Office</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $business['formatted_address'] }}</p>
            </div>
        </div>

        <!-- Form -->
        <div class="md:col-span-2 bg-white p-6 sm:p-8 rounded-3xl border border-brand-green-100 shadow-soft">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Send Us a Direct Message</h2>

            <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Your Full Name *</label>
                    <input type="text" name="name" required placeholder="Name" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number *</label>
                        <input type="text" name="phone" required placeholder="017XXXXXXXX" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                        <input type="email" name="email" placeholder="you@example.com" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Message *</label>
                    <textarea name="message" required rows="4" placeholder="How can we assist you?" class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="py-2.5 px-6 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                    Send Message &rarr;
                </button>
            </form>
        </div>

    </div>

</div>
@endsection
