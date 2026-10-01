@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">
        
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create Account</h1>
            <p class="text-xs text-slate-500">Sign up for easier checkout, address book, and order tracking.</p>
        </div>

        @if($errors->any())
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                <input type="text" name="name" required value="{{ old('name') }}" placeholder="Your Name" autocomplete="name"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone" required value="{{ old('phone') }}" placeholder="017XXXXXXXX" autocomplete="tel"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Email Address @unless($emailRequired ?? true)(Optional)@endunless
                </label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email"
                    @if($emailRequired ?? true) required @endif
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                <p class="text-[11px] text-slate-400 mt-1">We'll send a verification code and your order updates here.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters" autocomplete="new-password"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <label class="flex items-start gap-2 text-[11px] text-slate-600 cursor-pointer">
                <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in')) class="mt-0.5 rounded text-brand-green-600">
                <span>Send me offers, new arrivals and newsletters by email. You can unsubscribe at any time. Order and account emails are always sent.</span>
            </label>

            <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Create Account
            </button>
        </form>

        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Already have an account? 
            <a href="{{ route('login') }}" class="font-bold text-brand-green-600 hover:underline">Sign in</a>
        </div>

    </div>
</div>
@endsection
