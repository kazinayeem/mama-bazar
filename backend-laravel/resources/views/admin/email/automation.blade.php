@extends('layouts.admin', ['headerTitle' => 'Email Automation'])

@section('content')
<div class="admin-page max-w-5xl">
    <x-admin.page-header title="Email Automation" subtitle="Choose which events send emails automatically. Every email is queued after the database commit, so checkout never waits on SMTP." />
    @include('admin.email.partials.tabs')

    @unless($settings['mail_enabled'])
        <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Outgoing email is disabled in SMTP Settings — these rules have no effect until it is enabled.</div>
    @endunless

    <form action="{{ route('admin.email.automation.update') }}" method="POST" class="space-y-4">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($groups as $group => $items)
                <div class="admin-surface p-5 space-y-3">
                    <h2 class="text-sm font-bold text-slate-900">{{ $group }}</h2>
                    @foreach($items as $item)
                        <label class="flex items-start gap-3 rounded-[6px] p-2 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="{{ $item['key'] }}" value="1" @checked($item['enabled']) class="mt-0.5 rounded border-slate-300 text-brand-green-600">
                            <span class="text-xs">
                                <span class="block font-semibold text-slate-800">{{ $item['label'] }}</span>
                                <span class="text-slate-500">{{ $item['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="admin-surface p-5 space-y-3">
            <h2 class="text-sm font-bold text-slate-900">Account policies</h2>
            <label class="flex items-start gap-3 text-xs cursor-pointer">
                <input type="checkbox" name="email_require_registration_email" value="1" @checked($settings['email_require_registration_email']) class="mt-0.5 rounded border-slate-300 text-brand-green-600">
                <span><span class="block font-semibold text-slate-800">Require an email address at registration</span><span class="text-slate-500">New accounts must provide an email (existing accounts are not affected).</span></span>
            </label>
            <label class="flex items-start gap-3 text-xs cursor-pointer">
                <input type="checkbox" name="email_verification_enforced" value="1" @checked($settings['email_verification_enforced']) class="mt-0.5 rounded border-slate-300 text-brand-green-600">
                <span><span class="block font-semibold text-slate-800">Require verified email to write reviews</span><span class="text-slate-500">Applies only to accounts created after email verification launched. Older accounts and guest checkout are never blocked.</span></span>
            </label>
            <div class="max-w-md">
                <label class="mb-1 block text-xs font-semibold text-slate-700">Internal notification inbox</label>
                <input type="email" name="email_admin_notification_address" value="{{ old('email_admin_notification_address', $settings['email_admin_notification_address']) }}" class="admin-control w-full" placeholder="Defaults to the support email in Business Information">
                <p class="mt-1 text-[11px] text-slate-400">Receives contact form alerts.</p>
            </div>
            <p class="text-[11px] text-slate-500">OTP: {{ config('email_system.otp.length') }} digits, valid {{ config('email_system.otp.expires_minutes') }} min, {{ config('email_system.otp.max_attempts') }} attempts, {{ config('email_system.otp.resend_cooldown_seconds') }}s resend cooldown, max {{ config('email_system.otp.max_per_hour') }} codes/hour. Review invitations are sent {{ config('email_system.review_invitation_delay_days') }} days after delivery. (Configured in <code>config/email_system.php</code>.)</p>
        </div>

        <div class="flex justify-end">
            <x-admin.button type="submit">Save automation rules</x-admin.button>
        </div>
    </form>
</div>
@endsection
