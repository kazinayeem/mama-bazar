@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-4xl">

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">SMTP Email Configuration</h1>
            <p class="text-xs text-slate-500">Configure outbound SMTP credentials, sender identity, and test mail server connectivity.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.email.dashboard') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs flex items-center gap-2">
            <span>✓</span> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <span>⚠️</span> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Main Settings Form --}}
    <form action="{{ route('admin.email.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        {{-- SMTP Server Details Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Outgoing Mail Server (SMTP)</h2>
                    <p class="text-xs text-slate-500">Connect to your domain's mail server or transactional email provider.</p>
                </div>
                <div class="flex items-center gap-2">
                    <label for="mail_enabled" class="text-xs font-bold text-slate-700">Enable Outgoing Emails</label>
                    <input type="checkbox" name="mail_enabled" id="mail_enabled" value="1" {{ old('mail_enabled', $settings['mail_enabled']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Mail Driver</label>
                    <select name="mail_mailer" class="admin-control w-full text-xs">
                        <option value="smtp" {{ old('mail_mailer', $settings['mail_mailer']) === 'smtp' ? 'selected' : '' }}>SMTP (Recommended)</option>
                        <option value="log" {{ old('mail_mailer', $settings['mail_mailer']) === 'log' ? 'selected' : '' }}>Log (Debug / Local Development)</option>
                        <option value="sendmail" {{ old('mail_mailer', $settings['mail_mailer']) === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">SMTP Host</label>
                    <input type="text" name="mail_host" required value="{{ old('mail_host', $settings['mail_host']) }}" class="admin-control w-full text-xs" placeholder="mail.mama-bazar.com">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">SMTP Port</label>
                    <input type="number" name="mail_port" required value="{{ old('mail_port', $settings['mail_port']) }}" class="admin-control w-full text-xs" placeholder="465">
                    <p class="mt-1 text-[11px] text-slate-400">Common: 465 (SSL/TLS) or 587 (STARTTLS)</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Encryption Protocol</label>
                    <select name="mail_encryption" class="admin-control w-full text-xs">
                        <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption']) === 'ssl' ? 'selected' : '' }}>SSL / TLS (Port 465)</option>
                        <option value="tls" {{ old('mail_encryption', $settings['mail_encryption']) === 'tls' ? 'selected' : '' }}>STARTTLS (Port 587)</option>
                        <option value="none" {{ empty($settings['mail_encryption']) ? 'selected' : '' }}>None (Unencrypted)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">SMTP Username</label>
                    <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}" class="admin-control w-full text-xs" placeholder="contact@mama-bazar.com">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">SMTP Password</label>
                    <input type="password" name="mail_password" autocomplete="new-password" class="admin-control w-full text-xs" placeholder="•••••••••••••••• (Leave blank to keep existing)">
                    <p class="mt-1 text-[11px] text-slate-400">Encrypted securely at rest. Never shown in plain text.</p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Connection Timeout (seconds)</label>
                    <input type="number" name="mail_timeout" value="{{ old('mail_timeout', $settings['mail_timeout']) }}" class="admin-control w-full text-xs" min="5" max="120">
                </div>
            </div>
        </div>

        {{-- Sender Identity Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Default Sender &amp; Reply-To Identity</h2>
                <p class="text-xs text-slate-500">Official email address and brand name displayed in customer inboxes.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">From Name</label>
                    <input type="text" name="mail_from_name" required value="{{ old('mail_from_name', $settings['mail_from_name']) }}" class="admin-control w-full text-xs">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">From Email Address</label>
                    <input type="email" name="mail_from_address" required value="{{ old('mail_from_address', $settings['mail_from_address']) }}" class="admin-control w-full text-xs">
                    <p class="mt-1 text-[11px] text-slate-400">Should match a verified mailbox on your domain to prevent spam flags.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Reply-To Email Address</label>
                    <input type="email" name="mail_reply_to" value="{{ old('mail_reply_to', $settings['mail_reply_to']) }}" class="admin-control w-full text-xs" placeholder="support@mamabazar.com">
                    <p class="mt-1 text-[11px] text-slate-400">Customer replies will be directed here.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button type="submit" class="rounded-xl bg-brand-green-600 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-brand-green-700 transition">
                Save SMTP Settings
            </button>
        </div>
    </form>

    {{-- Testing & Diagnostics Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-5">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Test SMTP &amp; Send Verification Email</h2>
            <p class="text-xs text-slate-500">Test server handshakes and send a real verification email to any test inbox.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 pt-2">
            {{-- Probe connection --}}
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-900">Probe Server Connection</h3>
                    <p class="text-xs text-slate-500 mt-1">Tests TCP handshake and ESMTP authentication without sending an email.</p>
                </div>
                <form action="{{ route('admin.email.settings.test-connection') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full py-2 px-4 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-xs font-bold text-slate-700 shadow-xs transition">
                        Run Connection Probe &rarr;
                    </button>
                </form>
            </div>

            {{-- Send real test email --}}
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-900">Send Test Email Message</h3>
                    <p class="text-xs text-slate-500 mt-1">Sends a rendered test email with branding and HTML templates.</p>
                </div>
                <form action="{{ route('admin.email.settings.send-test') }}" method="POST" class="mt-3 flex gap-2">
                    @csrf
                    <input type="email" name="test_email" required value="{{ auth()->user()->email ?: 'contact@mama-bazar.com' }}" placeholder="test@example.com" class="admin-control flex-1 text-xs">
                    <button type="submit" class="py-2 px-3 rounded-lg bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold transition">
                        Send Test
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Deliverability & DNS Checklist --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-3">
        <h2 class="text-sm font-bold text-slate-900">Domain Deliverability Checklist (SPF, DKIM, DMARC)</h2>
        <p class="text-xs text-slate-500 leading-relaxed">
            To ensure high inbox placement across Gmail, Yahoo, and Outlook, configure the following TXT DNS records with your domain registrar:
        </p>
        <div class="space-y-2 text-xs text-slate-600 pt-1">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 font-mono text-[11px]">
                <strong class="text-slate-800">SPF (TXT @):</strong> v=spf1 +a +mx +ip4:SERVER_IP ~all
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 font-mono text-[11px]">
                <strong class="text-slate-800">DKIM (TXT default._domainkey):</strong> Generated via cPanel Email Deliverability interface.
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 font-mono text-[11px]">
                <strong class="text-slate-800">DMARC (TXT _dmarc):</strong> v=DMARC1; p=quarantine; sp=quarantine; rua=mailto:dmarc@mama-bazar.com
            </div>
        </div>
    </div>

</div>
@endsection
