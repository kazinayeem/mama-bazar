@extends('layouts.admin', ['headerTitle' => 'SMTP Settings'])

@section('content')
@php
    $currentPort = (int) old('mail_port', $settings['mail_port'] ?? 465);
    $currentEnc = old('mail_encryption', $settings['mail_encryption'] ?? 'ssl');
    $currentHost = old('mail_host', $settings['mail_host'] ?? 'mail.mama-bazar.com');
    $currentMode = old('mail_config_mode', $settings['mail_config_mode'] ?? 'auto');
    $currentProvider = old('mail_provider', $settings['mail_provider'] ?? 'cpanel');
    $currentTimeout = (int) old('mail_timeout', $settings['mail_timeout'] ?? 15);
    $is456 = ($currentPort === 456);
    $isNonstandard = ! in_array($currentPort, \App\Services\EmailSettingService::STANDARD_PORTS, true);
    $status = $settings['mail_last_status'] ?? null;
    $statusCategory = $settings['mail_last_error_category'] ?? null;
@endphp

<div class="admin-page max-w-5xl" x-data="smtpSettings({
    mode: '{{ $currentMode }}',
    provider: '{{ $currentProvider }}',
    host: '{{ addslashes($currentHost) }}',
    port: {{ $currentPort }},
    encryption: '{{ $currentEnc }}',
    timeout: {{ $currentTimeout }},
    csrfToken: '{{ csrf_token() }}',
    testUrl: '{{ route('admin.email.settings.test-connection') }}',
    probeUrl: '{{ route('admin.email.settings.probe-ports') }}'
})">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-admin.page-header title="SMTP Settings" subtitle="Smart outgoing mail server configuration, automated port selection and connection diagnostics." />
    </div>

    @include('admin.email.partials.tabs')

    <div class="space-y-4 mt-4">
        @if($settings['mail_mailer'] === 'log')
            <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                The <strong>Log</strong> driver is active — outgoing emails are written to the application log without opening network connections. Switch to SMTP for live sending.
            </div>
        @endif
        @if(! str_starts_with((string) $appUrl, 'https://'))
            <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                <code>APP_URL</code> is <code>{{ $appUrl }}</code>. Links and logos in emails use this address — set it to the public HTTPS storefront URL in production.
            </div>
        @endif

        {{-- Configuration Alert Banner for Port 456 Typo --}}
        @if($is456)
            <div class="rounded-[8px] border border-amber-300 bg-amber-50 p-4 text-xs text-amber-900 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 font-bold text-amber-950">
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Non-Standard Port Detected: Port 456</span>
                        </div>
                        <p class="text-amber-800">
                            Port <strong>456</strong> was saved in your settings. Port 456 is not a standard SMTP submission port and is actively refused by <code>{{ $currentHost }}</code>. This is typically a transposition typo for standard <strong>Port 465 (SSL/TLS)</strong> or <strong>Port 587 (STARTTLS)</strong>.
                        </p>
                    </div>
                    <button type="button" @click="applyPort(465, 'ssl')" class="inline-flex items-center gap-1.5 rounded-[6px] bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                        Apply Recommended Port 465 (SSL/TLS)
                    </button>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.email.settings.update') }}" method="POST" class="space-y-4" autocomplete="off" id="smtpSettingsForm">
            @csrf
            <input type="hidden" name="mail_config_mode" :value="mode">
            <input type="hidden" name="mail_provider" :value="provider">

            <div class="admin-surface p-5 space-y-5">
                {{-- Header with Outgoing Email Toggle --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Outgoing Mail Server (SMTP)</h2>
                        <p class="text-xs text-slate-500">Configure SMTP credentials and delivery parameters. IMAP/POP3 are not required.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="hidden" name="mail_enabled" value="0">
                        <input type="checkbox" name="mail_enabled" value="1" @checked(old('mail_enabled', $settings['mail_enabled'])) class="rounded border-slate-300 text-brand-green-600">
                        <span>Enable outgoing email</span>
                    </label>
                </div>

                {{-- Mode Switcher (Automatic vs Manual) --}}
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Configuration Mode</label>
                    <div class="grid grid-cols-2 gap-2 sm:max-w-md">
                        <button type="button" @click="setMode('auto')"
                                :class="mode === 'auto' ? 'border-brand-green-600 bg-brand-green-50 text-brand-green-800 ring-1 ring-brand-green-600 font-semibold' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                                class="flex items-center justify-center gap-2 rounded-[8px] border p-2.5 text-xs transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Automatic Configuration</span>
                            <span class="rounded bg-brand-green-200 px-1 py-0.5 text-[9px] font-bold text-brand-green-900 uppercase">Default</span>
                        </button>
                        <button type="button" @click="setMode('manual')"
                                :class="mode === 'manual' ? 'border-brand-green-600 bg-brand-green-50 text-brand-green-800 ring-1 ring-brand-green-600 font-semibold' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                                class="flex items-center justify-center gap-2 rounded-[8px] border p-2.5 text-xs transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Manual Configuration</span>
                        </button>
                    </div>
                </div>

                {{-- Provider Presets Selector --}}
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Mail Provider Preset</label>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div @click="selectProvider('cpanel')"
                             :class="provider === 'cpanel' ? 'border-brand-green-600 bg-brand-green-50/50 ring-1 ring-brand-green-600' : 'border-slate-200 bg-white hover:border-slate-300'"
                             class="cursor-pointer rounded-[8px] border p-3.5 transition">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">cPanel / Custom Domain</span>
                                <span x-show="provider === 'cpanel'" class="h-2 w-2 rounded-full bg-brand-green-600"></span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Standard outgoing server for cPanel, DirectAdmin, or Linux hosting.</p>
                        </div>

                        <div @click="selectProvider('gmail')"
                             :class="provider === 'gmail' ? 'border-brand-green-600 bg-brand-green-50/50 ring-1 ring-brand-green-600' : 'border-slate-200 bg-white hover:border-slate-300'"
                             class="cursor-pointer rounded-[8px] border p-3.5 transition">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">Gmail / Google Workspace</span>
                                <span x-show="provider === 'gmail'" class="h-2 w-2 rounded-full bg-brand-green-600"></span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Google SMTP relay using <code>smtp.gmail.com</code> with App Password.</p>
                        </div>

                        <div @click="selectProvider('other')"
                             :class="provider === 'other' ? 'border-brand-green-600 bg-brand-green-50/50 ring-1 ring-brand-green-600' : 'border-slate-200 bg-white hover:border-slate-300'"
                             class="cursor-pointer rounded-[8px] border p-3.5 transition">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">Other SMTP Provider</span>
                                <span x-show="provider === 'other'" class="h-2 w-2 rounded-full bg-brand-green-600"></span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Custom transactional SMTP (SendGrid, SES, Mailgun, Postmark, etc.).</p>
                        </div>
                    </div>
                </div>

                {{-- Provider Guidance Notes --}}
                <div x-show="provider === 'gmail'" class="rounded-[8px] border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900 space-y-1">
                    <p class="font-semibold">Important for Google & Gmail accounts:</p>
                    <p>Google requires a <strong>16-character App Password</strong> generated from <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener" class="underline font-bold">myaccount.google.com/apppasswords</a> (requires 2-Step Verification). Your standard Gmail account password will fail authentication with error <code>535</code>.</p>
                </div>
                <div x-show="provider === 'cpanel'" class="rounded-[8px] border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                    <p>Matches outgoing server configuration in cPanel &rsaquo; <strong>Email Accounts &rsaquo; Connect Devices</strong>. Typically uses <code>mail.yourdomain.com</code> on <strong>Port 465 (SSL/TLS)</strong> with your full email address as the username.</p>
                </div>

                {{-- MODE A: AUTOMATIC CONFIGURATION VIEW --}}
                <div x-show="mode === 'auto'" class="space-y-4 rounded-[8px] border border-emerald-100 bg-emerald-50/30 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-100 pb-3">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Automatic Settings Recommendation</h3>
                            <p class="text-[11px] text-slate-500">Port, encryption, and timeout are automatically harmonized. Server ports can be probed live.</p>
                        </div>
                        <button type="button" @click="probeServerPorts()" :disabled="probing"
                                class="inline-flex items-center gap-1.5 rounded-[6px] border border-emerald-300 bg-white px-2.5 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-50 disabled:opacity-50 transition">
                            <svg x-show="probing" class="h-3.5 w-3.5 animate-spin text-emerald-700" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="probing ? 'Probing ports...' : 'Auto-Probe Server Ports'"></span>
                        </button>
                    </div>

                    {{-- Live Probe Feedback --}}
                    <div x-show="probeResult" class="rounded-[6px] border border-slate-200 bg-white p-3 text-xs space-y-2">
                        <div class="flex items-center justify-between font-semibold">
                            <span class="text-slate-800">Port Connectivity Probe for <code x-text="host"></code>:</span>
                            <span x-show="probeResult?.recommended" class="text-emerald-700 text-[11px]" x-text="'Recommended: ' + probeResult?.recommended?.label"></span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="p in probeResult?.ports" :key="p.port">
                                <span :class="p.status === 'open' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-red-100 text-red-800 border-red-200'"
                                      class="inline-flex items-center gap-1 rounded border px-2 py-0.5 text-[11px] font-mono">
                                    <span x-text="p.status === 'open' ? '✓' : '✗'"></span>
                                    <span x-text="'Port ' + p.port + ' (' + p.encryption.toUpperCase() + '): ' + p.status + (p.latency_ms ? ' (' + p.latency_ms + 'ms)' : '')"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">SMTP Host</label>
                            <input type="text" name="mail_host" x-model="host" class="admin-control w-full" placeholder="mail.yourdomain.com">
                            <p class="mt-1 text-[11px] text-slate-500">Auto-selected host for this provider.</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Supported Secure Port Selection</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="applyPort(465, 'ssl')"
                                        :class="port === 465 && encryption === 'ssl' ? 'border-brand-green-600 bg-brand-green-50 text-brand-green-900 ring-1 ring-brand-green-600 font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                        class="rounded-[6px] border p-2 text-left text-xs transition">
                                    <div class="font-bold">Port 465 (SSL/TLS)</div>
                                    <div class="text-[10px] text-slate-500">Implicit Encryption (cPanel standard)</div>
                                </button>
                                <button type="button" @click="applyPort(587, 'tls')"
                                        :class="port === 587 && encryption === 'tls' ? 'border-brand-green-600 bg-brand-green-50 text-brand-green-900 ring-1 ring-brand-green-600 font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                        class="rounded-[6px] border p-2 text-left text-xs transition">
                                    <div class="font-bold">Port 587 (STARTTLS)</div>
                                    <div class="text-[10px] text-slate-500">Explicit Upgrade (Gmail / SES)</div>
                                </button>
                            </div>
                            <input type="hidden" name="mail_port" :value="port">
                            <input type="hidden" name="mail_encryption" :value="encryption">
                        </div>
                    </div>
                </div>

                {{-- MODE B: MANUAL CONFIGURATION VIEW --}}
                <div x-show="mode === 'manual'" class="space-y-4 rounded-[8px] border border-slate-200 bg-slate-50/50 p-4">
                    <div class="border-b border-slate-200 pb-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Manual SMTP Parameters</h3>
                        <p class="text-[11px] text-slate-500">Full control over host, port, encryption, and timeout with smart validation.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Mail Driver</label>
                            <select name="mail_mailer" class="admin-control w-full">
                                <option value="smtp" @selected(old('mail_mailer', $settings['mail_mailer']) === 'smtp')>SMTP (recommended)</option>
                                <option value="sendmail" @selected(old('mail_mailer', $settings['mail_mailer']) === 'sendmail')>Sendmail (server MTA binary)</option>
                                <option value="log" @selected(old('mail_mailer', $settings['mail_mailer']) === 'log')>Log only (testing — nothing is sent)</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">SMTP Host</label>
                            <input type="text" name="mail_host" x-model="host" class="admin-control w-full" placeholder="mail.example.com">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">SMTP Port</label>
                            <input type="number" name="mail_port" x-model.number="port" @input="onPortChange()" class="admin-control w-full" min="1" max="65535">
                            <p class="mt-1 text-[11px] text-slate-400">465 = implicit SSL/TLS · 587 = STARTTLS · 25 / 2525 = legacy/relay</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Encryption</label>
                            <select name="mail_encryption" x-model="encryption" @change="onEncryptionChange()" class="admin-control w-full">
                                <option value="ssl">SSL/TLS (implicit TLS, recommended port 465)</option>
                                <option value="tls">STARTTLS (explicit upgrade, recommended port 587)</option>
                                <option value="none">None (unencrypted, port 25 only - not recommended)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Manual Mode Smart Port Warning / Typo Notice --}}
                    <div x-show="port === 456" class="rounded border border-amber-300 bg-amber-50 p-2.5 text-xs text-amber-800">
                        <span class="font-bold">⚠️ Port 456 is non-standard:</span> Most SMTP mail servers use <strong>465</strong> (SSL/TLS) or <strong>587</strong> (STARTTLS). Port 456 is likely a typo for 465.
                        <button type="button" @click="applyPort(465, 'ssl')" class="ml-2 font-bold underline text-amber-950">Switch to Port 465</button>
                    </div>

                    <div x-show="port !== 456 && isNonstandardPort()" class="rounded border border-blue-200 bg-blue-50 p-2.5 text-xs text-blue-800">
                        <span class="font-bold">ℹ️ Non-Standard Port Notice:</span> Port <span x-text="port"></span> is not among standard SMTP submission ports (465, 587, 25, 2525). It will be saved as entered, but verify that your mail server listens on this port.
                    </div>

                    {{-- Combination Incompatibility Warning --}}
                    <div x-show="port === 465 && encryption !== 'ssl'" class="rounded border border-red-300 bg-red-50 p-2.5 text-xs text-red-800">
                        <span class="font-bold">❌ Incompatible Combination:</span> Port 465 requires <strong>SSL/TLS (implicit TLS)</strong>. Using <span x-text="encryption.toUpperCase()"></span> on port 465 will cause TLS handshake errors.
                        <button type="button" @click="encryption = 'ssl'" class="ml-2 font-bold underline text-red-950">Set to SSL/TLS</button>
                    </div>
                    <div x-show="port === 587 && encryption === 'ssl'" class="rounded border border-red-300 bg-red-50 p-2.5 text-xs text-red-800">
                        <span class="font-bold">❌ Incompatible Combination:</span> Port 587 requires <strong>STARTTLS</strong>. Implicit SSL/TLS connections on port 587 will fail handshake negotiation.
                        <button type="button" @click="encryption = 'tls'" class="ml-2 font-bold underline text-red-950">Set to STARTTLS</button>
                    </div>
                </div>

                {{-- Credentials & Timeout Section (Always Accessible, Never Overwritten) --}}
                <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-slate-100">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Username</label>
                        <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}" class="admin-control w-full" autocomplete="off" placeholder="user@mama-bazar.com">
                        <p class="mt-1 text-[11px] text-slate-400">For cPanel and Google, this must be the full email address.</p>
                    </div>

                    <div x-data="{ showPassword: false }">
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Password</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   name="mail_password"
                                   value=""
                                   class="admin-control w-full pr-10"
                                   autocomplete="new-password"
                                   placeholder="{{ $settings['password_source'] === 'none' ? 'Enter SMTP password' : '•••••••• (leave blank to keep current)' }}">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Hide SMTP password' : 'Show SMTP password'"
                                    :title="showPassword ? 'Hide SMTP password' : 'Show SMTP password'"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600 focus:outline-none focus:text-slate-700 transition">
                                <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">
                            @switch($settings['password_source'])
                                @case('database') Saved securely in database (encrypted with APP_KEY). Never displayed. @break
                                @case('environment') Configured via <code>MAIL_PASSWORD</code> environment variable. @break
                                @default <span class="font-semibold text-red-600">Password not configured.</span>
                            @endswitch
                        </p>
                        @if($settings['password_source'] === 'database')
                            <label class="mt-1 inline-flex items-center gap-1.5 text-[11px] text-slate-600 cursor-pointer">
                                <input type="checkbox" name="clear_password" value="1" class="rounded border-slate-300"> Remove saved password
                            </label>
                        @endif
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Connection Timeout (seconds)</label>
                        <input type="number" name="mail_timeout" x-model.number="timeout" class="admin-control w-full" min="5" max="120">
                        <p class="mt-1 text-[11px] text-slate-400">Recommended: 10–15 seconds to avoid prolonged blocking on network issues.</p>
                    </div>
                </div>
            </div>

            {{-- Sender Identity Surface --}}
            <div class="admin-surface p-5 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-sm font-bold text-slate-900">Sender Identity</h2>
                    <p class="text-xs text-slate-500">Store outgoing email headers. Separate from the public business contact address.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Sender Name</label>
                        <input type="text" name="mail_from_name" required value="{{ old('mail_from_name', $settings['mail_from_name']) }}" class="admin-control w-full" maxlength="120">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Sender Email</label>
                        <input type="email" name="mail_from_address" required value="{{ old('mail_from_address', $settings['mail_from_address']) }}" class="admin-control w-full">
                        <p class="mt-1 text-[11px] text-slate-400">Use the authenticated SMTP mailbox domain to ensure SPF/DKIM alignment.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Reply-to Address (optional)</label>
                        <input type="email" name="mail_reply_to" value="{{ old('mail_reply_to', $settings['mail_reply_to']) }}" class="admin-control w-full" placeholder="Defaults to sender email">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-400">Settings will be stored securely with APP_KEY encryption.</span>
                <x-admin.button type="submit">Save settings</x-admin.button>
            </div>
        </form>

        {{-- Diagnostics & Testing Grid --}}
        <div class="grid gap-4 md:grid-cols-2">
            {{-- Connection Testing & Status Panel --}}
            <div class="admin-surface p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Connection Status & Probe</h2>
                        <p class="text-xs text-slate-500">Live TCP handshake and authentication check.</p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold">
                        <span class="h-2.5 w-2.5 rounded-full {{ $status === 'connected' ? 'bg-emerald-500 animate-pulse' : ($status === 'failed' ? 'bg-red-500' : 'bg-slate-300') }}"></span>
                        <span class="{{ $status === 'connected' ? 'text-emerald-700' : ($status === 'failed' ? 'text-red-700' : 'text-slate-600') }}">
                            {{ $status === 'connected' ? 'Connected' : ($status === 'failed' ? 'Connection Failed' : 'Not tested') }}
                        </span>
                        @if(! empty($settings['mail_last_latency_ms']) && $status === 'connected')
                            <span class="text-slate-400 font-mono text-[11px]">({{ $settings['mail_last_latency_ms'] }} ms)</span>
                        @endif
                    </div>
                </div>

                {{-- Status Details --}}
                <div class="rounded-[8px] bg-slate-50 p-3 text-xs space-y-2 border border-slate-100">
                    <div class="flex flex-wrap items-center justify-between text-slate-600">
                        <span>Active Saved Target:</span>
                        <span class="font-mono font-semibold text-slate-800">
                            {{ $settings['mail_host'] }}:{{ $settings['mail_port'] }} ({{ strtoupper((string)$settings['mail_encryption']) }})
                            @if((int)$settings['mail_port'] === 456)
                                <span class="text-amber-600 text-[10px] uppercase font-bold">[Non-standard]</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between text-slate-600">
                        <span>Last Tested:</span>
                        <span class="font-semibold text-slate-800">
                            {{ $settings['mail_last_tested_at'] ? \Carbon\Carbon::parse($settings['mail_last_tested_at'])->format('d M Y, h:i A') : 'Never' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between text-slate-600">
                        <span>Last Successful Connection:</span>
                        <span class="font-semibold {{ ! empty($settings['mail_last_success_at']) ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ ! empty($settings['mail_last_success_at']) ? \Carbon\Carbon::parse($settings['mail_last_success_at'])->format('d M Y, h:i A') : 'Never verified' }}
                        </span>
                    </div>
                </div>

                {{-- Display Last Error if Failed --}}
                @if($status === 'failed' && ! empty($settings['mail_last_error']))
                    <div class="rounded-[8px] border border-red-200 bg-red-50 p-3 text-xs text-red-800 space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-red-950">
                            <span class="rounded bg-red-200 px-1.5 py-0.5 text-[10px] font-mono uppercase">
                                {{ \App\Services\EmailSettingService::categoryLabel((string)$statusCategory) }}
                            </span>
                            <span>Diagnostic Trace</span>
                        </div>
                        <p class="font-mono text-[11px] text-red-900 break-words whitespace-pre-line">{{ $settings['mail_last_error'] }}</p>
                    </div>
                @endif

                {{-- Live AJAX Connection Test Feedback --}}
                <div x-show="testResult" class="rounded-[8px] p-3 text-xs space-y-1"
                     :class="testResult?.success ? 'border border-emerald-200 bg-emerald-50 text-emerald-900' : 'border border-red-200 bg-red-50 text-red-900'">
                    <div class="flex items-center gap-2 font-bold">
                        <span class="rounded px-1.5 py-0.5 text-[10px] font-mono uppercase"
                              :class="testResult?.success ? 'bg-emerald-200 text-emerald-950' : 'bg-red-200 text-red-950'"
                              x-text="testResult?.category_label || (testResult?.success ? 'Success' : 'Error')"></span>
                        <span x-text="testResult?.success ? 'Connection Verified' : 'Connection Failed'"></span>
                    </div>
                    <p class="font-mono text-[11px]" x-text="testResult?.message"></p>
                    <p x-show="testResult?.diagnostic" class="text-[11px] opacity-90" x-text="'Hint: ' + testResult?.diagnostic"></p>
                </div>

                {{-- Test Connection Trigger --}}
                <div>
                    <button type="button" @click="runTestConnection()" :disabled="testing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-[8px] border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50 disabled:opacity-50 transition">
                        <svg x-show="testing" class="h-3.5 w-3.5 animate-spin text-slate-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="testing ? 'Probing SMTP connection...' : 'Test SMTP Connection (No email sent)'"></span>
                    </button>
                    <p class="mt-1 text-[11px] text-slate-400 text-center">Verifies TCP handshake, TLS negotiation, and authentication without sending messages.</p>
                </div>

                {{-- Live Email Delivery Test (Separate Action) --}}
                <div class="border-t border-slate-100 pt-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-900">Send Live Test Email</h3>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase">Inbox Delivery Verification</span>
                    </div>
                    <form action="{{ route('admin.email.settings.send-test') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="email" name="test_email" required value="{{ old('test_email', auth()->user()->email) }}" placeholder="inbox@example.com" class="admin-control flex-1 text-xs">
                        <x-admin.button type="submit" size="sm">Send test email</x-admin.button>
                    </form>
                    <p class="text-[11px] text-slate-400">Sends a real message to verify end-to-end transport delivery and inbox receipt.</p>
                </div>
            </div>

            {{-- Sender Verification Checklist --}}
            <div class="admin-surface p-5 space-y-3">
                <h2 class="text-sm font-bold text-slate-900">Sender Verification</h2>
                <ul class="space-y-2 text-xs">
                    @foreach($senderChecks as $check)
                        <li class="flex gap-2">
                            <span class="{{ $check['ok'] ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold' }}">{{ $check['ok'] ? '✓' : '!' }}</span>
                            <span><strong class="text-slate-800">{{ $check['label'] }}</strong><br><span class="text-slate-500 break-words">{{ $check['detail'] }}</span></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- DNS Deliverability Surface --}}
        <div class="admin-surface p-5 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Deliverability Checklist (DNS)</h2>
                    <p class="text-xs text-slate-500">Live DNS records for the sender domain. Passing checks improve inbox placement.</p>
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
                            <span class="{{ $item['status'] === 'pass' ? 'text-emerald-600 font-bold' : ($item['status'] === 'missing' ? 'text-red-600 font-bold' : 'text-amber-600 font-bold') }}">{{ $item['status'] === 'pass' ? '✓' : '!' }}</span>
                            <span><strong class="text-slate-800">{{ $item['label'] }}</strong><br><span class="font-mono text-[11px] text-slate-500 break-all">{{ \Illuminate\Support\Str::limit($item['detail'], 220) }}</span></span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <ul class="list-disc space-y-1 pl-5 text-xs text-slate-600">
                <li><strong>Port 465 (SSL/TLS)</strong>: Implicit TLS handshake immediately upon connection. Standard for cPanel mail accounts.</li>
                <li><strong>Port 587 (STARTTLS)</strong>: Cleartext greeting upgraded to TLS via <code>STARTTLS</code> command. Standard for Google / SES.</li>
                <li><strong>SPF</strong> (TXT): Authorizes server IP/host to send email for your domain (e.g. <code>v=spf1 +a +mx include:... ~all</code>).</li>
                <li><strong>DKIM</strong>: Cryptographic signatures published under <code>default._domainkey</code> in DNS.</li>
                <li><strong>DMARC</strong>: Policy specifying recipient server actions on SPF/DKIM failures.</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('smtpSettings', (initial) => ({
        mode: initial.mode || 'auto',
        provider: initial.provider || 'cpanel',
        host: initial.host || '',
        port: Number(initial.port) || 465,
        encryption: initial.encryption || 'ssl',
        timeout: Number(initial.timeout) || 15,
        csrfToken: initial.csrfToken || '',
        testUrl: initial.testUrl,
        probeUrl: initial.probeUrl,
        testing: false,
        probing: false,
        testResult: null,
        probeResult: null,

        setMode(newMode) {
            this.mode = newMode;
            if (newMode === 'auto') {
                this.applyProviderDefaults();
            }
        },

        selectProvider(p) {
            this.provider = p;
            if (this.mode === 'auto') {
                this.applyProviderDefaults();
            }
        },

        applyProviderDefaults() {
            if (this.provider === 'gmail') {
                this.host = 'smtp.gmail.com';
                this.port = 587;
                this.encryption = 'tls';
                this.timeout = 15;
            } else if (this.provider === 'cpanel') {
                if (!this.host || this.host === 'smtp.gmail.com') {
                    this.host = 'mail.mama-bazar.com';
                }
                this.port = 465;
                this.encryption = 'ssl';
                this.timeout = 15;
            } else {
                this.port = 587;
                this.encryption = 'tls';
                this.timeout = 15;
            }
        },

        applyPort(newPort, newEnc) {
            this.port = Number(newPort);
            this.encryption = newEnc;
        },

        onPortChange() {
            const p = Number(this.port);
            if (p === 465) {
                this.encryption = 'ssl';
            } else if (p === 587 || p === 2525) {
                this.encryption = 'tls';
            } else if (p === 25) {
                this.encryption = 'none';
            }
        },

        onEncryptionChange() {
            if (this.encryption === 'ssl' && this.port !== 465) {
                this.port = 465;
            } else if (this.encryption === 'tls' && this.port !== 587 && this.port !== 2525) {
                this.port = 587;
            } else if (this.encryption === 'none' && (this.port === 465 || this.port === 587)) {
                this.port = 25;
            }
        },

        isNonstandardPort() {
            return ![465, 587, 25, 2525].includes(Number(this.port));
        },

        async runTestConnection() {
            if (this.testing) return;
            this.testing = true;
            this.testResult = null;

            try {
                const response = await fetch(this.testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        mail_host: this.host,
                        mail_port: this.port,
                        mail_encryption: this.encryption,
                        mail_timeout: this.timeout
                    })
                });

                const data = await response.json();
                this.testResult = data;
            } catch (err) {
                this.testResult = {
                    success: false,
                    category: 'unknown',
                    category_label: 'Network error',
                    message: 'Could not contact backend test endpoint: ' + (err.message || 'Unknown network error.')
                };
            } finally {
                this.testing = false;
            }
        },

        async probeServerPorts() {
            if (this.probing) return;
            this.probing = true;
            this.probeResult = null;

            try {
                const response = await fetch(this.probeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        mail_host: this.host
                    })
                });

                const data = await response.json();
                this.probeResult = data;

                if (data.success && data.recommended) {
                    this.applyPort(data.recommended.port, data.recommended.encryption);
                }
            } catch (err) {
                this.probeResult = {
                    success: false,
                    error: err.message || 'Probe request failed'
                };
            } finally {
                this.probing = false;
            }
        }
    }));
});
</script>
@endsection
