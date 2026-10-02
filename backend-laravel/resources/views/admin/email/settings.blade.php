@extends('layouts.admin', ['headerTitle' => 'SMTP Settings'])

@section('content')
<div class="admin-page max-w-5xl" x-data="smtpSettingsLock()">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-admin.page-header title="SMTP Settings" subtitle="Outgoing mail server, sender identity and connection diagnostics." />
        <div x-show="unlocked" x-cloak>
            <x-admin.button type="button" variant="outline" size="sm" @click="lock()" class="gap-1.5 text-slate-700">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Lock Settings
            </x-admin.button>
        </div>
    </div>

    @include('admin.email.partials.tabs')

    {{-- Locked State Screen --}}
    <div x-show="!unlocked" x-cloak class="admin-surface mt-4 p-8 sm:p-12 text-center rounded-[12px] border border-slate-200 shadow-sm bg-white">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-700 ring-8 ring-slate-50">
            <svg class="h-8 w-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="mt-4 text-lg font-bold text-slate-900">SMTP Settings Locked</h2>
        <p class="mx-auto mt-2 max-w-md text-xs sm:text-sm text-slate-500">
            Access to outgoing mail server credentials and connection testing is restricted for security. Please unlock using your admin security PIN.
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <x-admin.button type="button" @click="openModal()" class="gap-2 px-5 py-2.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                Unlock SMTP Settings
            </x-admin.button>
        </div>
        <p class="mt-5 text-xs text-slate-400">
            Need access? Please contact <a href="mailto:contact@bornosoft.bd?subject=SMTP%20Settings%20Access%20Request" class="font-medium text-brand-green-600 hover:underline">contact@bornosoft.bd</a> to unlock SMTP Settings.
        </p>
    </div>

    {{-- Unlocked Settings Content --}}
    <div x-show="unlocked" x-cloak class="space-y-4">
        @if($settings['mail_mailer'] === 'log')
            <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">The <strong>Log</strong> driver is active — emails are written to the application log instead of being sent. Switch to SMTP for production.</div>
        @endif
        @if(! str_starts_with((string) $appUrl, 'https://'))
            <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800"><code>APP_URL</code> is <code>{{ $appUrl }}</code>. Links and logos in emails use this address — set it to the public HTTPS storefront URL in production.</div>
        @endif

        <form action="{{ route('admin.email.settings.update') }}" method="POST" class="space-y-4" autocomplete="off">
            @csrf
            <fieldset :disabled="!unlocked" class="space-y-4">
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
            </fieldset>
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
                    <fieldset :disabled="!unlocked">
                        <x-admin.button type="submit" variant="outline" size="sm">Test connection (no email sent)</x-admin.button>
                    </fieldset>
                </form>
                <form action="{{ route('admin.email.settings.send-test') }}" method="POST" class="flex gap-2 pt-2 border-t border-slate-100">
                    @csrf
                    <fieldset :disabled="!unlocked" class="flex gap-2 w-full">
                        <input type="email" name="test_email" required value="{{ old('test_email', auth()->user()->email) }}" placeholder="you@example.com" class="admin-control flex-1">
                        <x-admin.button type="submit" size="sm">Send test email</x-admin.button>
                    </fieldset>
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
                    <fieldset :disabled="!unlocked">
                        <x-admin.button type="submit" variant="outline" size="sm">Check DNS records</x-admin.button>
                    </fieldset>
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

    {{-- Unlock Modal --}}
    <x-admin.modal name="modalOpen" maxWidth="md">
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-slate-900">Unlock SMTP Settings</h2>
                    <p class="text-xs text-slate-500">Enter your security PIN to access SMTP configuration.</p>
                </div>
            </div>
        </x-slot>

        <form @submit.prevent="submitPin()" class="space-y-4" autocomplete="off">
            <div>
                <label for="smtp-pin-input" class="mb-1 block text-xs font-semibold text-slate-700">Security PIN</label>
                <div class="relative">
                    <input
                        id="smtp-pin-input"
                        x-ref="pinInput"
                        type="password"
                        x-model="pin"
                        placeholder="Enter PIN"
                        class="admin-control w-full pr-10 text-sm tracking-widest font-mono"
                        :class="error ? '!border-red-400 focus:!ring-red-100' : ''"
                        maxlength="32"
                        @keydown.enter.prevent="submitPin()"
                    >
                    <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                </div>
                <template x-if="error">
                    <p class="mt-1.5 flex items-center gap-1 text-xs text-red-600 font-medium" x-text="error"></p>
                </template>
            </div>

            <div class="rounded-[8px] bg-slate-50 p-3 text-xs text-slate-600 border border-slate-100 space-y-2">
                <p>
                    Need access? Please contact <a href="mailto:contact@bornosoft.bd?subject=Unlock%20SMTP%20Settings%20Request" class="font-semibold text-brand-green-600 hover:underline">contact@bornosoft.bd</a> to unlock SMTP Settings.
                </p>
                <div>
                    <a href="mailto:contact@bornosoft.bd?subject=Unlock%20SMTP%20Settings%20Request" class="inline-flex items-center gap-1.5 rounded-[6px] border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-xs hover:border-brand-green-500 hover:text-brand-green-700 transition">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Contact Support</span>
                    </a>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <x-admin.button type="button" variant="outline" size="sm" @click="closeModal()">
                    Cancel
                </x-admin.button>
                <x-admin.button type="submit" size="sm" x-bind:disabled="loading" class="gap-1.5">
                    <template x-if="loading">
                        <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </template>
                    <span x-text="loading ? 'Unlocking...' : 'Unlock'"></span>
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>

<script>
    function smtpSettingsLock() {
        return {
            unlocked: false,
            modalOpen: false,
            pin: '',
            error: '',
            loading: false,
            openModal() {
                this.pin = '';
                this.error = '';
                this.modalOpen = true;
                this.$nextTick(() => {
                    this.$refs.pinInput?.focus();
                });
            },
            closeModal() {
                this.modalOpen = false;
                this.pin = '';
                this.error = '';
            },
            async submitPin() {
                if (!this.pin || !this.pin.trim()) {
                    this.error = 'Please enter your security PIN.';
                    this.$refs.pinInput?.focus();
                    return;
                }
                this.loading = true;
                this.error = '';
                try {
                    const res = await fetch('{{ route('admin.email.settings.unlock') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ pin: this.pin.trim() })
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        this.unlocked = true;
                        this.modalOpen = false;
                        this.pin = '';
                        this.error = '';
                        window.dispatchEvent(new CustomEvent('admin-toast', {
                            detail: { message: data.message || 'SMTP settings unlocked successfully.', type: 'success' }
                        }));
                    } else {
                        this.error = data.message || 'Incorrect PIN. Access denied.';
                        this.$nextTick(() => this.$refs.pinInput?.focus());
                    }
                } catch (e) {
                    this.error = 'Network error verifying PIN. Please try again.';
                } finally {
                    this.loading = false;
                }
            },
            async lock() {
                try {
                    await fetch('{{ route('admin.email.settings.lock') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    });
                } catch (e) {}
                this.unlocked = false;
                this.modalOpen = false;
                this.pin = '';
                this.error = '';
                window.dispatchEvent(new CustomEvent('admin-toast', {
                    detail: { message: 'SMTP settings locked.', type: 'success' }
                }));
            }
        };
    }
</script>
@endsection
