@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">

        <div class="text-center space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Choose a New Password</h1>
            @unless($invalid)
                <p class="text-xs text-slate-500">Enter a new password for your account. This link can only be used once.</p>
            @endunless
        </div>

        @if($invalid)
            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs" role="alert">
                This password reset link is invalid or has expired.
            </div>
            <a href="{{ route('auth.forgot-password') }}" class="block w-full text-center py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Request a new link
            </a>
        @else
            @if($errors->any())
                <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('auth.reset-password.submit') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
                    <input type="password" name="password" required minlength="6" placeholder="Minimum 6 characters" autocomplete="new-password"
                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" required minlength="6" placeholder="Repeat password" autocomplete="new-password"
                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                    Update Password
                </button>
            </form>
        @endif

    </div>
</div>
@endsection
