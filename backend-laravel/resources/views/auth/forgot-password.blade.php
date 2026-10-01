@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">

        <div class="text-center space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Forgot Password</h1>
            <p class="text-xs text-slate-500">Enter your registered email address to receive a 6-digit password reset code.</p>
        </div>

        @if(session('success'))
            <div class="p-3 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('auth.forgot-password.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" required value="{{ old('email') }}" placeholder="you@example.com"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Send Reset Code &rarr;
            </button>
        </form>

        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Remember your password?
            <a href="{{ route('login') }}" class="font-bold text-brand-green-600 hover:underline">Sign in</a>
        </div>

    </div>
</div>
@endsection
