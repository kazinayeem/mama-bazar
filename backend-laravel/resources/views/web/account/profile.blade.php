@extends('web.account.layout')

@section('account-title', 'Personal Profile')

@section('account-content')
<div class="space-y-6">

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-6">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Personal Profile</h2>
            <p class="text-xs text-slate-500">Update your primary contact information and delivery preferences.</p>
        </div>

        <form action="{{ route('account.profile.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Full Name --}}
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" id="name" name="name" required value="{{ old('name', $user->name) }}"
                           class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    @error('name')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Phone Number --}}
                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required value="{{ old('phone', $user->phone) }}"
                           class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    <p class="mt-1 text-[10px] text-slate-400">Used for courier delivery notifications and account sign-in.</p>
                    @error('phone')
                        <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Email Address Display Card --}}
            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-semibold text-slate-700">Account Email</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs font-medium text-slate-900">{{ $user->email ?: 'No email registered' }}</span>
                            @if($user->email)
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                        <svg class="h-3 w-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Verified
                                    </span>
                                @else
                                    <a href="{{ route('auth.verify-otp') }}" class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 border border-amber-200 hover:underline">
                                        Verify Email
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('account.email') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand-green-700 hover:underline">
                        <span>{{ $user->email ? 'Change Email with OTP' : 'Add Email' }} &rarr;</span>
                    </a>
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    For your security, email changes require password verification and an OTP sent to your new mailbox.
                </p>
            </div>

            {{-- Default Shipping Preferences --}}
            <div class="pt-4 border-t border-slate-100 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Default Shipping Preferences</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="shipping_area" class="block text-xs font-semibold text-slate-700 mb-1">Delivery Zone</label>
                        <select id="shipping_area" name="shipping_area"
                                class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                            <option value="inside_dhaka" @selected(old('shipping_area', $user->shipping_area) === 'inside_dhaka')>Inside Dhaka (Standard Delivery)</option>
                            <option value="outside_dhaka" @selected(old('shipping_area', $user->shipping_area) === 'outside_dhaka')>Outside Dhaka (Countrywide Delivery)</option>
                        </select>
                    </div>

                    <div>
                        <label for="shipping_address" class="block text-xs font-semibold text-slate-700 mb-1">Default Address Text</label>
                        <input type="text" id="shipping_address" name="shipping_address" value="{{ old('shipping_address', $user->shipping_address) }}" placeholder="e.g. House 12, Road 4, Sector 7, Uttara"
                               class="w-full rounded-xl border border-slate-200 p-2.5 text-xs text-slate-900 focus:border-brand-green-500 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('account.dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" class="rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
