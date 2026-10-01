@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">
        
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Customer Login</h1>
            <p class="text-xs text-slate-500">Sign in to your Mama Bazar account to view past orders and manage profile.</p>
        </div>

        @foreach(['success' => 'bg-brand-green-50 border-brand-green-200 text-brand-green-800', 'info' => 'bg-sky-50 border-sky-200 text-sky-800', 'error' => 'bg-red-50 border-red-200 text-red-700'] as $flash => $classes)
            @if(session($flash))
                <div class="p-3 rounded-xl border text-xs {{ $classes }}">{{ session($flash) }}</div>
            @endif
        @endforeach

        @if($errors->any())
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                <input type="text" name="phone" required value="{{ old('phone') }}" placeholder="017XXXXXXXX" autocomplete="tel"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="rounded text-brand-green-600">
                    <span>Remember me</span>
                </label>
                <a href="{{ route('auth.forgot-password') }}" class="font-semibold text-brand-green-600 hover:underline">Forgot password?</a>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Sign In
            </button>
        </form>

        @if($loginOtpEnabled ?? false)
            <a href="{{ route('auth.login-otp') }}" class="block w-full text-center py-2.5 px-4 rounded-full border border-slate-200 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition">
                Sign in with an email code instead
            </a>
        @endif

        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Don't have an account yet? 
            <a href="{{ route('register') }}" class="font-bold text-brand-orange-600 hover:underline">Register now</a>
        </div>

    </div>
</div>
@endsection
