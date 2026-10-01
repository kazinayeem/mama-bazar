@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto px-4 py-12 space-y-6" x-data="{
    cooldown: {{ (int) $cooldown }},
    init() {
        const timer = setInterval(() => { this.cooldown > 0 ? this.cooldown-- : clearInterval(timer); }, 1000);
    }
}">
    <div class="space-y-1">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Email Settings</h1>
        <p class="text-xs text-slate-500">Manage your email address and which emails you receive from us.</p>
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

    <section class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-3">
        <h2 class="text-sm font-bold text-slate-900">Email Address</h2>
        <div class="flex items-center justify-between text-xs">
            <span class="text-slate-700">{{ $user->email ?: 'No email address on file' }}</span>
            @if($user->email)
                @if($user->email_verified_at)
                    <span class="px-2 py-0.5 rounded-full bg-brand-green-50 text-brand-green-700 font-semibold">Verified</span>
                @else
                    <a href="{{ route('auth.verify-otp') }}" class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold hover:underline">Verify now</a>
                @endif
            @endif
        </div>

        <form action="{{ route('account.email.change') }}" method="POST" class="grid gap-3 pt-3 border-t border-slate-100">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $user->email ? 'New email address' : 'Add an email address' }}</label>
                <input type="email" name="new_email" required value="{{ old('new_email', $pendingEmail) }}" autocomplete="email"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Current password</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>
            <button type="submit" :disabled="cooldown > 0" class="justify-self-start px-4 py-2 rounded-full bg-slate-900 text-white text-xs font-bold disabled:opacity-50">
                <span x-show="cooldown > 0" x-text="'Resend available in ' + cooldown + 's'"></span>
                <span x-show="cooldown === 0" @if($cooldown > 0) style="display:none" @endif>Send verification code</span>
            </button>
        </form>

        @if($pendingEmail)
            <form action="{{ route('account.email.confirm') }}" method="POST" class="grid gap-3 pt-3 border-t border-slate-100">
                @csrf
                <p class="text-xs text-slate-600">Enter the {{ $codeLength }}-digit code sent to <strong>{{ $pendingMasked }}</strong>.</p>
                <input type="text" name="code" required maxlength="{{ $codeLength }}" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                    class="w-full text-center text-xl font-black tracking-[0.4em] rounded-2xl border border-slate-200 p-3 focus:border-brand-green-500 focus:outline-none">
                <button type="submit" class="justify-self-start px-4 py-2 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold">Confirm new email</button>
            </form>
        @endif
    </section>

    <section class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-3">
        <h2 class="text-sm font-bold text-slate-900">Marketing Emails</h2>
        <form action="{{ route('account.email.preferences') }}" method="POST" class="space-y-3">
            @csrf
            <label class="flex items-start gap-2 text-xs text-slate-600 cursor-pointer">
                <input type="hidden" name="marketing_opt_in" value="0">
                <input type="checkbox" name="marketing_opt_in" value="1" @checked($subscribed) @disabled(! $user->email) class="mt-0.5 rounded text-brand-green-600">
                <span>Send me offers, new arrivals and newsletters by email.</span>
            </label>
            <p class="text-[11px] text-slate-400">Order confirmations, invoices, delivery updates and security alerts are always sent, regardless of this setting.</p>
            <button type="submit" @disabled(! $user->email) class="px-4 py-2 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold disabled:opacity-50">Save preference</button>
        </form>
    </section>
</div>
@endsection
