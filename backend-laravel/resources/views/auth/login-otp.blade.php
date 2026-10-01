@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16" x-data="{
    cooldown: {{ (int) $cooldown }},
    init() {
        const timer = setInterval(() => { this.cooldown > 0 ? this.cooldown-- : clearInterval(timer); }, 1000);
    }
}">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">

        <div class="text-center space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Sign in with Email Code</h1>
            <p class="text-xs text-slate-500">We'll email a one-time {{ $codeLength }}-digit code to the verified address on your account.</p>
        </div>

        @foreach(['success' => 'bg-brand-green-50 border-brand-green-200 text-brand-green-800', 'error' => 'bg-red-50 border-red-200 text-red-700'] as $flash => $classes)
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

        <form action="{{ route('auth.login-otp.send') }}" method="POST" class="space-y-3">
            @csrf
            <label class="block text-xs font-semibold text-slate-700">Email Address</label>
            <div class="flex gap-2">
                <input type="email" name="email" required value="{{ old('email', $email) }}" placeholder="you@example.com" autocomplete="email"
                    class="flex-1 text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                <button type="submit" :disabled="cooldown > 0"
                    class="px-4 rounded-xl bg-slate-900 text-white text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="cooldown > 0" x-text="cooldown + 's'"></span>
                    <span x-show="cooldown === 0" @if($cooldown > 0) style="display:none" @endif>{{ $email ? 'Resend' : 'Send code' }}</span>
                </button>
            </div>
        </form>

        @if($email)
            <form action="{{ route('auth.login-otp.verify') }}" method="POST" class="space-y-4">
                @csrf
                <input type="text" name="code" required maxlength="{{ $codeLength }}" inputmode="numeric" pattern="[0-9]*"
                    autocomplete="one-time-code" autofocus placeholder="{{ str_repeat('•', $codeLength) }}"
                    class="w-full text-center text-2xl font-black tracking-[0.5em] rounded-2xl border border-slate-200 p-3.5 focus:border-brand-green-500 focus:outline-none bg-slate-50/50">
                <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                    Sign In
                </button>
            </form>
        @endif

        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            <a href="{{ route('login') }}" class="font-bold text-brand-green-600 hover:underline">&larr; Back to password sign in</a>
        </div>

    </div>
</div>
@endsection
