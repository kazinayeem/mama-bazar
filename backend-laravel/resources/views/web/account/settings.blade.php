@extends('web.account.layout')

@section('account-title', 'Security & Password')

@section('account-content')
<div class="space-y-6">

    {{-- Security & Password Card --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-6">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Account Security & Password</h2>
            <p class="text-xs text-slate-500">Ensure your account is protected with a secure and unique password.</p>
        </div>

        <form action="{{ route('account.settings.password') }}" method="POST" class="space-y-4 max-w-lg">
            @csrf

            {{-- Current Password --}}
            <div>
                <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1">Current Password *</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                       class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                @error('current_password')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- New Password --}}
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">New Password *</label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                       class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                <p class="mt-1 text-[10px] text-slate-400">Must be at least 8 characters long.</p>
                @error('password')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password *</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                       class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
            </div>

            <div class="pt-2">
                <button type="submit" class="rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    {{-- Security Details & Login Session Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Login Overview --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3 text-xs">
            <h3 class="font-bold text-slate-900 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Login Activity Overview</span>
            </h3>

            <div class="space-y-2 pt-1">
                <div class="flex items-center justify-between text-slate-600">
                    <span>Last Login Timestamp:</span>
                    <span class="font-bold text-slate-800">{{ $user->last_login_at?->format('M d, Y · h:i A') ?? 'Current session' }}</span>
                </div>
                @if($user->last_login_ip)
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Last IP Address:</span>
                        <span class="font-mono text-slate-700">{{ $user->last_login_ip }}</span>
                    </div>
                @endif
                @if($user->last_login_location)
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Approximate Location:</span>
                        <span class="text-slate-800 font-medium">{{ $user->last_login_location }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between text-slate-600">
                    <span>Account Created:</span>
                    <span class="text-slate-800 font-medium">{{ $user->created_at?->format('M d, Y') ?? 'Recently' }}</span>
                </div>
            </div>
        </div>

        {{-- Communications & Email Settings Shortcut --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3 text-xs flex flex-col justify-between">
            <div class="space-y-2">
                <h3 class="font-bold text-slate-900 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                    <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Email & Newsletter Preferences</span>
                </h3>
                <p class="text-slate-500 leading-relaxed">
                    Manage marketing emails, special offers, and newsletter subscriptions without affecting critical order notifications.
                </p>
            </div>

            <div class="pt-2">
                <a href="{{ route('account.email') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-brand-green-200 bg-brand-green-50 px-3.5 py-2 text-xs font-bold text-brand-green-800 hover:bg-brand-green-100 transition">
                    <span>Manage Email Settings</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
