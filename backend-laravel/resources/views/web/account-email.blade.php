@extends('web.account.layout')

@section('account-title', 'Email Preferences')

@section('account-content')
<div class="space-y-6" x-data="{
    cooldown: {{ (int) $cooldown }},
    init() {
        const timer = setInterval(() => { this.cooldown > 0 ? this.cooldown-- : clearInterval(timer); }, 1000);
    }
}">
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-6">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Email & Notification Settings</h2>
            <p class="text-xs text-slate-500">Manage your primary email address and marketing subscriptions.</p>
        </div>

        {{-- Email Address Section --}}
        <section class="space-y-4 pt-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Account Email Address</h3>
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 p-4 text-xs border border-slate-100">
                <div class="space-y-0.5">
                    <span class="text-slate-400 font-medium">Current Email:</span>
                    <div class="font-bold text-slate-800">{{ $user->email ?: 'No email address on file' }}</div>
                </div>
                @if($user->email)
                    @if($user->email_verified_at)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 border border-emerald-200">
                            <svg class="h-3.5 w-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Verified
                        </span>
                    @else
                        <a href="{{ route('auth.verify-otp') }}" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 border border-amber-200 hover:bg-amber-100">
                            Verify Email Now
                        </a>
                    @endif
                @endif
            </div>

            {{-- Change email form --}}
            <form action="{{ route('account.email.change') }}" method="POST" class="space-y-3 pt-3 border-t border-slate-100 max-w-md">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $user->email ? 'New email address' : 'Add an email address' }}</label>
                    <input type="email" name="new_email" required value="{{ old('new_email', $pendingEmail) }}" autocomplete="email"
                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Current password *</label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                </div>
                <button type="submit" :disabled="cooldown > 0" class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 disabled:opacity-50 transition">
                    <span x-show="cooldown > 0" x-text="'Resend available in ' + cooldown + 's'"></span>
                    <span x-show="cooldown === 0" @if($cooldown > 0) style="display:none" @endif>Send verification code</span>
                </button>
            </form>

            @if($pendingEmail)
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 space-y-3 max-w-md">
                    <form action="{{ route('account.email.confirm') }}" method="POST" class="space-y-3">
                        @csrf
                        <p class="text-xs text-slate-700">Enter the {{ $codeLength }}-digit code sent to <strong>{{ $pendingMasked }}</strong>.</p>
                        <input type="text" name="code" required maxlength="{{ $codeLength }}" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                            class="w-full text-center text-xl font-black tracking-[0.4em] rounded-xl border border-slate-200 p-2.5 bg-white focus:border-brand-green-500 focus:outline-none">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold transition">Confirm new email</button>
                    </form>
                </div>
            @endif
        </section>

        {{-- Marketing preferences --}}
        <section class="space-y-3 pt-5 border-t border-slate-100">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Marketing & Promotional Emails</h3>
            <form action="{{ route('account.email.preferences') }}" method="POST" class="space-y-3 max-w-lg">
                @csrf
                <label class="flex items-start gap-2.5 text-xs text-slate-700 cursor-pointer">
                    <input type="hidden" name="marketing_opt_in" value="0">
                    <input type="checkbox" name="marketing_opt_in" value="1" @checked($subscribed) @disabled(! $user->email) class="mt-0.5 rounded text-brand-green-600 focus:ring-brand-green-500">
                    <span>Send me exclusive offers, discount vouchers, and new arrivals by email.</span>
                </label>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                    Transactional messages (order confirmations, invoices, shipment updates, and security alerts) are strictly required for your account and are never affected by this toggle.
                </p>
                <button type="submit" @disabled(! $user->email) class="px-4 py-2 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold disabled:opacity-50 transition">
                    Save Preference
                </button>
            </form>
        </section>
    </div>
</div>
@endsection
