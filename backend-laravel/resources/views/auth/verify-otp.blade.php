@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16" x-data="{
    cooldown: {{ (int) $cooldown }},
    init() {
        const timer = setInterval(() => { this.cooldown > 0 ? this.cooldown-- : clearInterval(timer); }, 1000);
    }
}">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">

        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-full bg-brand-green-50 text-brand-green-700 flex items-center justify-center text-2xl border border-brand-green-200">
                ✉️
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Verify Your Email</h1>
            <p class="text-xs text-slate-500">
                Enter the {{ $codeLength }}-digit code we sent to<br>
                <strong class="text-slate-800">{{ $maskedEmail }}</strong>
            </p>
        </div>

        @foreach(['success' => 'bg-brand-green-50 border-brand-green-200 text-brand-green-800', 'info' => 'bg-sky-50 border-sky-200 text-sky-800', 'error' => 'bg-red-50 border-red-200 text-red-700'] as $flash => $classes)
            @if(session($flash))
                <div class="p-3 rounded-xl border text-xs {{ $classes }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        @if($errors->any())
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('auth.verify-otp.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="otp-code" class="block text-center text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Verification Code
                </label>
                <input id="otp-code" type="text" name="code" required maxlength="{{ $codeLength }}" inputmode="numeric"
                    pattern="[0-9]*" autocomplete="one-time-code" autofocus placeholder="{{ str_repeat('•', $codeLength) }}"
                    class="w-full text-center text-2xl font-black tracking-[0.5em] rounded-2xl border border-slate-200 p-3.5 focus:border-brand-green-500 focus:outline-none bg-slate-50/50">
                <p class="text-[11px] text-center text-slate-400 mt-2">Codes expire after {{ $expiresMinutes }} minutes. Check your spam folder if you can't find it.</p>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Verify Email Address &rarr;
            </button>
        </form>

        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            <p>Didn't receive the code?</p>
            <form action="{{ route('auth.resend-otp') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" :disabled="cooldown > 0"
                    class="font-bold text-brand-green-600 hover:underline disabled:opacity-50 disabled:no-underline disabled:cursor-not-allowed">
                    <span x-show="cooldown > 0" x-text="'Resend code in ' + cooldown + 's'"></span>
                    <span x-show="cooldown === 0" @if($cooldown > 0) style="display:none" @endif>Resend code</span>
                </button>
            </form>
            @if($canSkip)
                <div class="mt-3">
                    <a href="{{ route('home') }}" class="text-[11px] text-slate-400 hover:text-slate-600">Skip for now &rarr;</a>
                </div>
            @else
                <p class="mt-3 text-[11px] text-slate-400">You can keep shopping, but reviews require a verified email.</p>
            @endif
        </div>

    </div>
</div>
@endsection
