@extends('layouts.admin', ['headerTitle' => 'SMTP Settings'])

@section('content')
<div class="admin-page max-w-5xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-admin.page-header title="SMTP Settings" subtitle="Outgoing mail server, sender identity and connection diagnostics." />
    </div>

    @include('admin.email.partials.tabs')

    <div class="space-y-4 mt-4">
        @if($settings['mail_mailer'] === 'log')
            <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">The <strong>Log</strong> driver is active — emails are written to the application log instead of being sent. Switch to SMTP for production.</div>
        @endif
        @if(! str_starts_with((string) $appUrl, 'https://'))
            <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800"><code>APP_URL</code> is <code>{{ $appUrl }}</code>. Links and logos in emails use this address — set it to the public HTTPS storefront URL in production.</div>
        @endif

        <form action="{{ route('admin.email.settings.update') }}" method="POST" class="space-y-4" autocomplete="off">
            @csrf
            <div class="admin-surface p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Outgoing Mail Server (SMTP)</h2>
                        <p class="text-xs text-slate-500">IMAP/POP3 are not required — the store only sends email.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input type="hidden" name="mail_enabled" value="0">
                        <input type="checkbox" name="mail_enabled" value="1" @checked(old('mail_enabled', $settings['mail_enabled'])) class="rounded border-slate-300 text-brand-green-600">
                        Enable outgoing email
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Mail driver</label>
                        <select name="mail_mailer" class="admin-control w-full">
                            <option value="smtp" @selected(old('mail_mailer', $settings['mail_mailer']) === 'smtp')>SMTP (recommended)</option>
                            <option value="sendmail" @selected(old('mail_mailer', $settings['mail_mailer']) === 'sendmail')>Sendmail (server binary)</option>
                            <option value="log" @selected(old('mail_mailer', $settings['mail_mailer']) === 'log')>Log only (testing — nothing is sent)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">SMTP host</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}" class="admin-control w-full" placeholder="mail.example.com">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Port</label>
                        <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}" class="admin-control w-full" min="1" max="65535">
                        <p class="mt-1 text-[11px] text-slate-400">465 = implicit SSL/TLS · 587 = STARTTLS</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Encryption</label>
                        @php $enc = old('mail_encryption', \App\Services\EmailSettingService::normalizeEncryption($settings['mail_encryption'], (int) $settings['mail_port'])); @endphp
                        <select name="mail_encryption" class="admin-control w-full">
                            <option value="ssl" @selected($enc === 'ssl')>SSL/TLS (implicit, port 465)</option>
                            <option value="tls" @selected($enc === 'tls')>STARTTLS (port 587)</option>
                            <option value="none" @selected($enc === 'none')>None (not recommended)</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Username</label>
                        <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}" class="admin-control w-full" autocomplete="off">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Password</label>
                        <input type="password" name="mail_password" value="" class="admin-control w-full" autocomplete="new-password"
                            placeholder="{{ $settings['password_source'] === 'none' ? 'Enter SMTP password' : '•••••••• (leave blank to keep current)' }}">
                        <p class="mt-1 text-[11px] text-slate-500">
                            @switch($settings['password_source'])
                                @case('database') Saved (encrypted with APP_KEY). Never displayed. @break
                                @case('environment') Using the <code>MAIL_PASSWORD</code> server environment variable. @break
                                @default <span class="font-semibold text-red-600">Not configured.</span>
                            @endswitch
                        </p>
                        @if($settings['password_source'] === 'database')
                            <label class="mt-1 inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                                <input type="checkbox" name="clear_password" value="1" class="rounded border-slate-300"> Remove saved password
                            </label>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Timeout (seconds)</label>
                        <input type="number" name="mail_timeout" value="{{ old('mail_timeout', $settings['mail_timeout']) }}" class="admin-control w-full" min="5" max="120">
                    </div>
                </div>
            </div>

            <div class="admin-surface p-5 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-sm font-bold text-slate-900">Sender Identity</h2>
                    <p class="text-xs text-slate-500">The sender mailbox is separate from the public support email in Business Information — changing one never changes the other.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Sender name</label>
                        <input type="text" name="mail_from_name" required value="{{ old('mail_from_name', $settings['mail_from_name']) }}" class="admin-control w-full" maxlength="120">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Sender email</label>
                        <input type="email" name="mail_from_address" required value="{{ old('mail_from_address', $settings['mail_from_address']) }}" class="admin-control w-full">
                        <p class="mt-1 text-[11px] text-slate-400">Use the authenticated SMTP mailbox (or an alias it is allowed to send as).</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Reply-to address (optional)</label>
                        <input type="email" name="mail_reply_to" value="{{ old('mail_reply_to', $settings['mail_reply_to']) }}" class="admin-control w-full" placeholder="Defaults to the sender email">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-admin.button type="submit">Save settings</x-admin.button>
            </div>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="admin-surface p-5 space-y-3">
                <h2 class="text-sm font-bold text-slate-900">Connection status</h2>
                @php $status = $settings['mail_last_status'] ?? null; @endphp
                <div class="flex items-center gap-2 text-sm">
                    <span class="h-2.5 w-2.5 rounded-full {{ $status === 'connected' ? 'bg-emerald-500' : ($status === 'failed' ? 'bg-red-500' : 'bg-slate-300') }}"></span>
                    <span class="font-semibold">{{ $status === 'connected' ? 'Connected' : ($status === 'failed' ? 'Failed' : 'Not tested') }}</span>
                    @if(! empty($settings['mail_last_latency_ms']) && $status === 'connected')<span class="text-xs text-slate-400">{{ $settings['mail_last_latency_ms'] }} ms</span>@endif
                </div>
                <p class="text-xs text-slate-500">Last checked: {{ $settings['mail_last_tested_at'] ? \Carbon\Carbon::parse($settings['mail_last_tested_at'])->format('d M Y, h:i A') : 'never' }}</p>
                @if($status === 'failed' && ! empty($settings['mail_last_error']))
                    <p class="rounded bg-red-50 p-2 text-[11px] text-red-700 break-words">Last error: {{ $settings['mail_last_error'] }}</p>
                @endif
                <form action="{{ route('admin.email.settings.test-connection') }}" method="POST">
                    @csrf
                    <x-admin.button type="submit" variant="outline" size="sm">Test connection (no email sent)</x-admin.button>
                </form>
                <form action="{{ route('admin.email.settings.send-test') }}" method="POST" class="flex gap-2 pt-2 border-t border-slate-100">
                    @csrf
                    <div class="flex gap-2 w-full">
                        <input type="email" name="test_email" required value="{{ old('test_email', auth()->user()->email) }}" placeholder="you@example.com" class="admin-control flex-1">
                        <x-admin.button type="submit" size="sm">Send test email</x-admin.button>
                    </div>
                </form>
            </div>

            <div class="admin-surface p-5 space-y-3">
                <h2 class="text-sm font-bold text-slate-900">Sender verification</h2>
                <ul class="space-y-2 text-xs">
                    @foreach($senderChecks as $check)
                        <li class="flex gap-2">
                            <span class="{{ $check['ok'] ? 'text-emerald-600' : 'text-amber-600' }}">{{ $check['ok'] ? '✓' : '!' }}</span>
                            <span><strong class="text-slate-800">{{ $check['label'] }}</strong><br><span class="text-slate-500 break-words">{{ $check['detail'] }}</span></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="admin-surface p-5 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Deliverability checklist (DNS)</h2>
                    <p class="text-xs text-slate-500">Live lookups for the sender domain. Passing checks improve inbox placement but never guarantee it.</p>
                </div>
                <form action="{{ route('admin.email.settings.check-dns') }}" method="POST">
                    @csrf
                    <x-admin.button type="submit" variant="outline" size="sm">Check DNS records</x-admin.button>
                </form>
            </div>
            @if($dnsChecks && ! empty($dnsChecks['items']))
                <p class="text-[11px] text-slate-400">Checked {{ \Carbon\Carbon::parse($dnsChecks['checked_at'])->diffForHumans() }}</p>
                <ul class="space-y-2 text-xs">
                    @foreach($dnsChecks['items'] as $item)
                        <li class="flex gap-2">
                            <span class="{{ $item['status'] === 'pass' ? 'text-emerald-600' : ($item['status'] === 'missing' ? 'text-red-600' : 'text-amber-600') }}">{{ $item['status'] === 'pass' ? '✓' : '!' }}</span>
                            <span><strong class="text-slate-800">{{ $item['label'] }}</strong><br><span class="font-mono text-[11px] text-slate-500 break-all">{{ \Illuminate\Support\Str::limit($item['detail'], 220) }}</span></span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <ul class="list-disc space-y-1 pl-5 text-xs text-slate-600">
                <li><strong>SPF</strong> (TXT on the domain) must authorize the mail server, e.g. <code>v=spf1 +a +mx include:&lt;host&gt; ~all</code>.</li>
                <li><strong>DKIM</strong>: enable in cPanel › Email Deliverability and publish the generated <code>default._domainkey</code> record.</li>
                <li><strong>DMARC</strong>: start with <code>v=DMARC1; p=none; rua=mailto:&lt;reports mailbox&gt;</code>, tighten to quarantine once SPF/DKIM pass.</li>
                <li><strong>Reverse DNS (PTR)</strong> of the sending IP should match the mail host — ask the hosting provider.</li>
                <li><strong>TLS</strong> certificate must be valid for the SMTP host name.</li>
                <li>Monitor bounces in the sender mailbox; add hard-bouncing addresses to the suppression list.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
