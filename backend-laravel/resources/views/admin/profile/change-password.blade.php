@extends('layouts.admin', ['headerTitle' => 'Change Password'])

@section('content')
<div class="admin-page max-w-2xl mx-auto space-y-6">

    <div class="flex items-center justify-between pb-4 border-b border-[var(--admin-border)]">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Change Admin Password</h1>
            <p class="text-xs text-slate-500">Update your administrative account password to keep your store secure.</p>
        </div>
        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-1.5 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Settings
        </a>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-800 shadow-xs" role="alert">
            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-medium text-red-800 shadow-xs" role="alert">
            <svg class="h-4 w-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-medium text-red-800 shadow-xs space-y-1" role="alert">
            <div class="font-bold flex items-center gap-1.5">
                <svg class="h-4 w-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Please resolve the following errors:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 pl-5 text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="admin-surface p-6 sm:p-8 space-y-6 bg-white rounded-xl border border-[var(--admin-border)] shadow-xs"
         x-data="{
             submitting: false,
             showCurrent: false,
             showNew: false,
             showConfirm: false,
             newPassword: '',
             confirmPassword: ''
         }">

        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-green-50 text-brand-green-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->email ?? auth()->user()->phone }} · <span class="capitalize">{{ auth()->user()->role ?? 'Admin' }}</span></p>
            </div>
        </div>

        <form action="{{ route('admin.profile.password.update') }}" method="POST" @submit="submitting = true" class="space-y-5" autocomplete="off">
            @csrf

            {{-- 1. Current Password --}}
            <div>
                <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Current Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input :type="showCurrent ? 'text' : 'password'"
                           id="current_password"
                           name="current_password"
                           required
                           autofocus
                           autocomplete="current-password"
                           placeholder="Enter your current password"
                           class="admin-control w-full pr-10 text-xs @error('current_password') !border-red-400 focus:!ring-red-100 @enderror">
                    <button type="button"
                            @click="showCurrent = !showCurrent"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition"
                            aria-label="Toggle password visibility">
                        <svg x-show="!showCurrent" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showCurrent" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- 2. New Password --}}
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                    New Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input :type="showNew ? 'text' : 'password'"
                           id="password"
                           name="password"
                           x-model="newPassword"
                           required
                           autocomplete="new-password"
                           placeholder="At least 8 characters"
                           class="admin-control w-full pr-10 text-xs @error('password') !border-red-400 focus:!ring-red-100 @enderror">
                    <button type="button"
                            @click="showNew = !showNew"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition"
                            aria-label="Toggle new password visibility">
                        <svg x-show="!showNew" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showNew" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                @enderror
                <div class="mt-1.5 flex items-center gap-2 text-[11px] text-slate-500">
                    <span :class="newPassword.length >= 8 ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                        <span x-text="newPassword.length >= 8 ? '✓' : '○'"></span> Minimum 8 characters
                    </span>
                </div>
            </div>

            {{-- 3. Confirm New Password --}}
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Confirm New Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'"
                           id="password_confirmation"
                           name="password_confirmation"
                           x-model="confirmPassword"
                           required
                           autocomplete="new-password"
                           placeholder="Re-enter your new password"
                           class="admin-control w-full pr-10 text-xs @error('password_confirmation') !border-red-400 focus:!ring-red-100 @enderror">
                    <button type="button"
                            @click="showConfirm = !showConfirm"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition"
                            aria-label="Toggle confirm password visibility">
                        <svg x-show="!showConfirm" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirm" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                @enderror
                <template x-if="confirmPassword.length > 0">
                    <p class="mt-1 text-[11px]" :class="newPassword === confirmPassword ? 'text-emerald-600 font-semibold' : 'text-red-500 font-medium'">
                        <span x-text="newPassword === confirmPassword ? '✓ Passwords match' : '✕ Passwords do not match'"></span>
                    </p>
                </template>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3 text-xs text-slate-600 space-y-1">
                <p class="font-bold text-slate-800">Security Best Practices:</p>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-500">
                    <li>Use a unique password not shared with any personal account.</li>
                    <li>Avoid common words or predictable sequences.</li>
                    <li>Changing your password will maintain your current session and secure your administrative account.</li>
                </ul>
            </div>

            {{-- Submit Action --}}
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                    Cancel
                </a>
                <button type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-[8px] bg-brand-green-600 hover:bg-brand-green-700 text-white font-bold text-xs shadow-xs transition active:scale-[0.99] disabled:opacity-50 cursor-pointer">
                    <template x-if="submitting">
                        <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </template>
                    <span x-text="submitting ? 'Updating Password...' : 'Change Password'"></span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
