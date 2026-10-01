@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16" x-data="{
    cooldown: {{ session('resend_cooldown', 60) }},
    timer: null,
    init() {
        if (this.cooldown > 0) {
            this.timer = setInterval(() => {
                if (this.cooldown > 0) {
                    this.cooldown--;
                } else {
                    clearInterval(this.timer);
                }
            }, 1000);
        }
    }
}">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">

        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-full bg-brand-green-50 text-brand-green-700 flex items-center justify-center text-2xl border border-brand-green-200">
                ✉️
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Verify Your Email</h1>
            <p class="text-xs text-slate-500">
                We sent a 6-digit verification code to<br>
                <strong class="text-slate-800">{{ session('pending_verification_email', auth()->user()?->email) }}</strong>
            </p>
        </div>

        @if(session('success'))
            <div class="p-3 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('auth.verify-otp.submit') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ session('pending_verification_email', auth()->user()?->email) }}">

            <div>
                <label class="block text-center text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Enter 6-Digit Code
                </label>
                <input type="text" name="code" required maxlength="6" inputmode="numeric" autofocus
                    placeholder="• • • • • •"
                    class="w-full text-center text-2xl font-black tracking-[0.5em] rounded-2xl border border-slate-200 p-3.5 focus:border-brand-green-500 focus:outline-none bg-slate-50/50">
                <p class="text-[11px] text-center text-slate-400 mt-2">Code is valid for 5 minutes.</p>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Verify Email Address &rarr;
            </button>
        </form>

        {{-- Resend OTP Section with Cooldown --}}
        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            <p>Didn't receive the email code?</p>
            <form action="{{ route('auth.resend-otp') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="email" value="{{ session('pending_verification_email', auth()->user()?->email) }}">
                <button type="submit" :disabled="cooldown > 0"
                    class="font-bold text-brand-green-600 hover:underline disabled:opacity-50 disabled:no-underline disabled:cursor-not-allowed">
                    <span x-show="cooldown > 0" x-text="'Resend code in ' + cooldown + 's'"></span>
                    <span x-show="cooldown === 0">Resend Code Now</span>
                </button>
            </form>
            <div class="mt-3">
                <a href="{{ route('home') }}" class="text-[11px] text-slate-400 hover:text-slate-600">Skip for now &rarr;</a>
            </div>
        </div>

    </div>
</div>
@endsection
