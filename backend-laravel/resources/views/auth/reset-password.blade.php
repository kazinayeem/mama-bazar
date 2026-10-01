@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">

        <div class="text-center space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Set New Password</h1>
            <p class="text-xs text-slate-500">Enter the 6-digit code sent to your email along with your new password.</p>
        </div>

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

        <form action="{{ route('auth.reset-password.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" required value="{{ old('email', request('email', session('reset_email'))) }}" placeholder="you@example.com"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">6-Digit Reset Code</label>
                <input type="text" name="code" required maxlength="6" inputmode="numeric" placeholder="123456"
                    class="w-full text-center text-lg font-bold tracking-widest rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none bg-slate-50/50">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required placeholder="Confirm new password"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-md transition">
                Update Password &rarr;
            </button>
        </form>

    </div>
</div>
@endsection
