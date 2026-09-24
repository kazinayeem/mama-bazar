@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
    <div class="text-center space-y-3">
        <span class="badge-deal">About Mama Bazar</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Your Everyday Neighborhood Online Market</h1>
        <p class="text-sm text-slate-500 max-w-xl mx-auto leading-relaxed">
            Delivering trusted groceries, organic goods, lifestyle products, and household necessities directly to families all across Bangladesh.
        </p>
    </div>

    <div class="bg-white p-6 sm:p-10 rounded-3xl border border-brand-green-100 shadow-soft text-slate-700 text-sm leading-relaxed space-y-6">
        <h2 class="text-xl font-bold text-slate-900">Our Mission</h2>
        <p>
            At Mama Bazar, our primary objective is to make daily shopping effortless, transparent, and affordable. We source goods directly from verified suppliers, local farmers, and certified manufacturers to ensure you and your loved ones receive fresh, high-quality, authentic products every single day.
        </p>

        <h2 class="text-xl font-bold text-slate-900">Why Customers Trust Mama Bazar</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div class="p-4 rounded-2xl bg-brand-green-50/50 border border-brand-green-100 space-y-1">
                <h3 class="font-bold text-brand-green-800 text-xs">Quality Inspection</h3>
                <p class="text-xs text-slate-600">Every item is checked for expiration, packaging integrity, and quality before dispatch.</p>
            </div>
            <div class="p-4 rounded-2xl bg-brand-green-50/50 border border-brand-green-100 space-y-1">
                <h3 class="font-bold text-brand-green-800 text-xs">Transparent Pricing</h3>
                <p class="text-xs text-slate-600">No hidden fees or unexpected charges. The price you see at checkout is what you pay.</p>
            </div>
            <div class="p-4 rounded-2xl bg-brand-green-50/50 border border-brand-green-100 space-y-1">
                <h3 class="font-bold text-brand-green-800 text-xs">Fast Local Dispatch</h3>
                <p class="text-xs text-slate-600">Dedicated dispatch logistics ensure prompt delivery to your doorstep.</p>
            </div>
            <div class="p-4 rounded-2xl bg-brand-green-50/50 border border-brand-green-100 space-y-1">
                <h3 class="font-bold text-brand-green-800 text-xs">Customer Satisfaction</h3>
                <p class="text-xs text-slate-600">Dedicated support team ready to assist with returns, exchanges, or order questions.</p>
            </div>
        </div>
    </div>
</div>
@endsection
