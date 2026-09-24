@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">

    <div class="text-center space-y-2">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Contact Mama Bazar</h1>
        <p class="text-xs text-slate-500">Have questions about an order or our service? We are always here to help.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Contact Cards -->
        <div class="space-y-4">
            <div class="p-5 rounded-2xl bg-white border border-brand-green-100 shadow-soft">
                <span class="text-xl">📞</span>
                <h3 class="text-xs font-bold text-slate-900 mt-2">Customer Helpline</h3>
                <p class="text-xs text-brand-green-700 font-semibold mt-1">+880 1700-000000</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Available 9:00 AM - 10:00 PM</p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-brand-green-100 shadow-soft">
                <span class="text-xl">✉️</span>
                <h3 class="text-xs font-bold text-slate-900 mt-2">Email Inquiries</h3>
                <p class="text-xs text-brand-green-700 font-semibold mt-1">support@mamabazar.com</p>
                <p class="text-[11px] text-slate-400 mt-0.5">We reply within 24 business hours</p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-brand-green-100 shadow-soft">
                <span class="text-xl">📍</span>
                <h3 class="text-xs font-bold text-slate-900 mt-2">Corporate Office</h3>
                <p class="text-xs text-slate-600 mt-1">Dhaka, Bangladesh</p>
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
