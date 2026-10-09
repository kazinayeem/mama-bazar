@php
    /** @var array{summary: array<string, mixed>, unlocked: bool, unlockExpiresAt: ?int, canConfigure: bool, canTest: bool, canEnableLive: bool, callbackUrls: array<string, string>} $gateway */
    $summary = $gateway['summary'];
    $sourceLabels = ['database' => 'saved in admin (encrypted)', 'environment' => 'from server environment', 'none' => 'not configured'];
    $readinessLabels = [
        'not_configured' => ['Not configured', 'muted'],
        'configured' => ['Configured · not tested', 'warning'],
        'sandbox_verified' => ['Sandbox verified', 'success'],
        'live_unverified' => ['Live · not verified', 'destructive'],
        'live_verified' => ['Live verified', 'success'],
    ];
    [$readinessText, $readinessVariant] = $readinessLabels[$summary['readiness']] ?? ['Unknown', 'muted'];
    $lastTest = $summary['lastTest'] ?? [];
    $unlockErrors = $errors->getBag('gatewayUnlock');
@endphp

<section
    id="sslcommerz-gateway"
    class="admin-surface overflow-hidden"
    x-data="sslcommerzGatewayPanel({
        unlockExpiresAt: @js($gateway['unlockExpiresAt']),
        currentMode: @js($summary['mode']),
        openPin: @js($unlockErrors->isNotEmpty()),
    })"
>
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-[var(--admin-border)] px-4 py-3.5 sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm" aria-hidden="true">🔒</span>
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-slate-900">SSLCOMMERZ Gateway</h2>
                <p class="text-xs text-slate-500">Card, mobile banking &amp; internet banking via SSLCOMMERZ hosted checkout. Powers the “Card / Online Gateway” method.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            <x-admin.badge :variant="$summary['enabled'] ? 'success' : 'secondary'">{{ $summary['enabled'] ? 'Enabled' : 'Disabled' }}</x-admin.badge>
            <x-admin.badge :variant="$summary['mode'] === 'live' ? 'orange' : 'secondary'">{{ $summary['mode'] === 'live' ? 'Live' : 'Sandbox' }}</x-admin.badge>
            <x-admin.badge :variant="$readinessVariant">{{ $readinessText }}</x-admin.badge>
            @if($gateway['unlocked'])
                <x-admin.badge variant="warning">Unlocked</x-admin.badge>
            @else
                <x-admin.badge variant="muted">Locked</x-admin.badge>
            @endif
        </div>
    </div>

    <div class="space-y-4 p-4 sm:p-5">
        @unless($summary['serverEnabled'])
            <div role="alert" class="rounded-[8px] border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                SSLCOMMERZ is switched off on the server (<code>SSLCOMMERZ_ENABLED=false</code>). It cannot be offered at checkout until that is changed.
            </div>
        @endunless

        @if($summary['enabled'] && ! $summary['checkoutReady'])
            <div role="alert" class="rounded-[8px] border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Enabled but hidden from checkout:
                @if($summary['maintenanceMode'])
                    the method is in maintenance mode.
                @elseif($summary['readiness'] === 'not_configured')
                    credentials are missing.
                @elseif($summary['mode'] === 'live' && ! $summary['verified'])
                    Live credentials have not passed Test Configuration.
                @else
                    the gateway is not ready.
                @endif
            </div>
        @endif

        <dl class="grid gap-3 text-xs sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-[8px] border border-[var(--admin-border)] p-3">
                <dt class="font-semibold text-slate-500">Status</dt>
                <dd class="mt-1 font-semibold text-slate-900">
                    {{ $summary['checkoutReady'] ? 'Active at checkout' : ($summary['enabled'] ? 'Enabled · not shown' : 'Disabled') }}
                </dd>
            </div>
            <div class="rounded-[8px] border border-[var(--admin-border)] p-3">
                <dt class="font-semibold text-slate-500">Mode</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $summary['mode'] === 'live' ? 'Live (real payments)' : 'Sandbox (test payments)' }}</dd>
            </div>
            <div class="rounded-[8px] border border-[var(--admin-border)] p-3">
                <dt class="font-semibold text-slate-500">Currency</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $summary['currency'] }}</dd>
            </div>
            <div class="rounded-[8px] border border-[var(--admin-border)] p-3">
                <dt class="font-semibold text-slate-500">Store ID</dt>
                <dd class="mt-1 font-mono font-semibold text-slate-900" data-testid="gateway-store-id">{{ $summary['storeIdMasked'] ?? 'Not configured' }}</dd>
                <dd class="text-[11px] text-slate-400">{{ $sourceLabels[$summary['storeIdSource']] ?? '' }}</dd>
            </div>
            <div class="rounded-[8px] border border-[var(--admin-border)] p-3">
                <dt class="font-semibold text-slate-500">Store Password</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $summary['passwordConfigured'] ? '•••••••• Configured' : 'Not configured' }}</dd>
                <dd class="text-[11px] text-slate-400">{{ $sourceLabels[$summary['passwordSource']] ?? '' }}</dd>
            </div>
            <div class="rounded-[8px] border border-[var(--admin-border)] p-3">
                <dt class="font-semibold text-slate-500">Connection Status</dt>
                @if(! empty($lastTest))
                    <dd class="mt-1 font-semibold {{ ! empty($lastTest['ok']) ? 'text-emerald-700' : 'text-red-700' }}">
                        {{ ! empty($lastTest['ok']) ? 'Passed' : 'Failed' }} · {{ ucfirst($lastTest['mode'] ?? '') }}
                    </dd>
                    <dd class="text-[11px] text-slate-500">{{ $lastTest['message'] ?? '' }}</dd>
                    @if(! empty($lastTest['at']))
                        <dd class="text-[11px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($lastTest['at'])->diffForHumans() }}</dd>
                    @endif
                    @if(! empty($lastTest['ok']) && ! $summary['verified'])
                        <dd class="text-[11px] text-amber-700">Settings changed since this test — run it again.</dd>
                    @endif
                @else
                    <dd class="mt-1 font-semibold text-slate-500">Not tested yet</dd>
                @endif
            </div>
        </dl>

        <div class="flex flex-wrap items-center gap-2">
            @if($gateway['canTest'])
                <form method="POST" action="{{ route('admin.payment-gateway.test') }}" @submit="testing = true">
                    @csrf
                    <x-admin.button type="submit" variant="outline" size="sm" x-bind:disabled="testing">
                        <span x-text="testing ? 'Testing…' : 'Test Configuration'">Test Configuration</span>
                    </x-admin.button>
                </form>
            @endif

            @if($gateway['canConfigure'])
                @if($gateway['unlocked'])
                    <form method="POST" action="{{ route('admin.payment-gateway.lock') }}">
                        @csrf
                        <x-admin.button type="submit" variant="outline" size="sm">Lock Configuration</x-admin.button>
                    </form>
                    <span class="text-[11px] text-slate-500" x-show="remaining > 0">Locks automatically in <span x-text="remainingLabel"></span></span>
                    <span class="text-[11px] text-red-600" x-show="remaining <= 0" x-cloak>Unlock expired — reload and unlock again.</span>
                @else
                    <x-admin.button type="button" size="sm" @click="pinModalOpen = true">Unlock Configuration</x-admin.button>
                @endif
            @endif
        </div>

        @if($gateway['canConfigure'] && $gateway['unlocked'])
            <form method="POST" action="{{ route('admin.payment-gateway.update') }}" class="space-y-4 rounded-[8px] border border-amber-200 bg-amber-50/40 p-4" autocomplete="off" @submit="confirmSubmit($event)">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="flex items-center justify-between gap-3 rounded-[8px] border border-[var(--admin-border)] bg-white p-3">
                        <span>
                            <span class="block text-sm font-medium text-slate-900">Enable SSLCOMMERZ</span>
                            <span class="block text-[11px] text-slate-400">Only takes effect once credentials are configured{{ $summary['mode'] === 'live' ? ' and verified' : '' }}.</span>
                        </span>
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-green-600" @checked(old('enabled', $summary['enabled']))>
                    </label>

                    <div class="space-y-1.5">
                        <label for="gateway-currency" class="block text-xs font-semibold text-slate-700">Currency</label>
                        <select id="gateway-currency" name="currency" class="admin-control w-full">
                            @foreach(\App\Support\SslcommerzSettings::CURRENCIES as $currency)
                                <option value="{{ $currency }}" @selected($summary['currency'] === $currency)>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>

                    <fieldset class="space-y-1.5 sm:col-span-2">
                        <legend class="text-xs font-semibold text-slate-700">Mode</legend>
                        <div class="flex flex-wrap gap-2">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-[8px] border border-[var(--admin-border)] bg-white px-3 py-2 text-xs font-semibold">
                                <input type="radio" name="mode" value="sandbox" x-model="mode"> Sandbox
                            </label>
                            <label class="inline-flex items-center gap-2 rounded-[8px] border border-[var(--admin-border)] bg-white px-3 py-2 text-xs font-semibold {{ $gateway['canEnableLive'] ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                                <input type="radio" name="mode" value="live" x-model="mode" @disabled(! $gateway['canEnableLive'])> Live
                            </label>
                        </div>
                        @unless($gateway['canEnableLive'])
                            <p class="text-[11px] text-slate-500">Live mode requires a Super Admin or the “Activate Live Payments” permission.</p>
                        @endunless
                        <div x-show="mode === 'live' && currentMode === 'sandbox'" x-cloak role="alert" class="space-y-2 rounded-[8px] border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                            <p class="font-bold">Switching to Live charges real customers.</p>
                            <p>Use your live Store ID and Store Password, then run Test Configuration in Live mode. The gateway stays hidden from checkout until that test passes.</p>
                            <label class="inline-flex items-center gap-2 font-semibold">
                                <input type="checkbox" name="confirm_live" value="1" x-model="confirmLive"> I understand and want to switch to Live mode
                            </label>
                        </div>
                    </fieldset>

                    <div class="space-y-1.5">
                        <label for="gateway-store-id" class="block text-xs font-semibold text-slate-700">Store ID</label>
                        <input id="gateway-store-id" type="text" name="store_id" value="" maxlength="100" autocomplete="off" spellcheck="false"
                            placeholder="{{ $summary['storeIdMasked'] ? 'Leave blank to keep '.$summary['storeIdMasked'] : 'e.g. mamab65f1c2a1b2c3' }}"
                            class="admin-control w-full font-mono">
                        @if($summary['storeIdSource'] === 'database')
                            <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-500">
                                <input type="checkbox" name="clear_store_id" value="1"> Remove saved Store ID{{ config('services.sslcommerz.store_id') ? ' (falls back to server environment)' : '' }}
                            </label>
                        @endif
                    </div>

                    <div class="space-y-1.5">
                        <label for="gateway-store-password" class="block text-xs font-semibold text-slate-700">Store Password</label>
                        <div class="relative">
                            <input id="gateway-store-password" :type="showPassword ? 'text' : 'password'" type="password" name="store_password" value="" maxlength="255"
                                autocomplete="new-password" spellcheck="false" x-model="newPassword"
                                placeholder="{{ $summary['passwordConfigured'] ? 'Leave blank to keep the current password' : 'Enter Store Password' }}"
                                class="admin-control w-full pr-16 font-mono">
                            <button type="button" x-show="newPassword.length > 0" x-cloak @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-2 my-auto h-7 rounded-[6px] px-2 text-[11px] font-semibold text-slate-500 hover:bg-slate-100"
                                x-text="showPassword ? 'Hide' : 'Show'" :aria-label="showPassword ? 'Hide new password' : 'Show new password'"></button>
                        </div>
                        <p class="text-[11px] text-slate-400">The saved password is never displayed. Typing here replaces it.</p>
                        @if($summary['passwordSource'] === 'database')
                            <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-500">
                                <input type="checkbox" name="clear_store_password" value="1"> Remove saved Store Password{{ config('services.sslcommerz.store_password') ? ' (falls back to server environment)' : '' }}
                            </label>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-amber-200 pt-3">
                    <p class="mr-auto text-[11px] text-slate-500">Saving locks the configuration again. Changing credentials or mode requires a new test.</p>
                    <x-admin.button type="submit" size="sm">Save Configuration</x-admin.button>
                </div>
            </form>
        @endif

        <details class="rounded-[8px] border border-[var(--admin-border)] p-3 text-xs text-slate-600">
            <summary class="cursor-pointer font-semibold text-slate-700">Callback URLs &amp; setup notes</summary>
            <div class="mt-2 space-y-2">
                <p>SSLCOMMERZ sends customers and IPN notifications to these HTTPS endpoints. Register the IPN URL in the SSLCOMMERZ merchant panel.</p>
                <ul class="space-y-1 font-mono text-[11px]">
                    @foreach($gateway['callbackUrls'] as $label => $url)
                        <li class="break-all"><span class="font-sans font-semibold uppercase text-slate-500">{{ $label }}:</span> {{ $url }}</li>
                    @endforeach
                </ul>
                @unless(str_starts_with($gateway['callbackUrls']['ipn'], 'https://'))
                    <p class="font-semibold text-red-600">These URLs are not HTTPS. Live payments require the store to be served over HTTPS (check APP_URL).</p>
                @endunless
                <p>Orders are only marked paid after SSLCOMMERZ's validation API confirms the transaction ID, amount and currency.</p>
            </div>
        </details>
    </div>

    @if($gateway['canConfigure'] && ! $gateway['unlocked'])
        <x-admin.modal name="pinModalOpen" title="Unlock Payment Configuration" subtitle="Enter the payment settings PIN to edit SSLCOMMERZ credentials." maxWidth="sm">
            <form id="gateway-unlock-form" method="POST" action="{{ route('admin.payment-gateway.unlock') }}" class="space-y-3" autocomplete="off">
                @csrf
                <label for="gateway-unlock-pin" class="block text-xs font-semibold text-slate-700">PIN</label>
                <input id="gateway-unlock-pin" x-ref="pinInput" type="password" name="pin" inputmode="numeric" autocomplete="off" maxlength="32" required
                    class="admin-control w-full text-center font-mono tracking-[0.5em]">
                @if($unlockErrors->isNotEmpty())
                    <p role="alert" class="text-xs font-semibold text-red-600">{{ $unlockErrors->first('pin') }}</p>
                @endif
                <p class="text-[11px] text-slate-400">Unlocking only allows editing this gateway's configuration for a few minutes. Attempts are logged.</p>
            </form>
            <x-slot:footer>
                <x-admin.button type="button" variant="outline" size="sm" @click="pinModalOpen = false">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm" form="gateway-unlock-form">Unlock</x-admin.button>
            </x-slot:footer>
        </x-admin.modal>
    @endif
</section>

@push('scripts')
<script>
function sslcommerzGatewayPanel({ unlockExpiresAt, currentMode, openPin }) {
    return {
        pinModalOpen: openPin,
        testing: false,
        mode: currentMode,
        currentMode,
        confirmLive: false,
        showPassword: false,
        newPassword: '',
        remaining: 0,

        get remainingLabel() {
            const minutes = Math.floor(this.remaining / 60);
            const seconds = String(this.remaining % 60).padStart(2, '0');
            return `${minutes}:${seconds}`;
        },

        init() {
            this.$watch('pinModalOpen', (open) => {
                if (open) this.$nextTick(() => this.$refs.pinInput?.focus());
            });
            if (openPin) this.$nextTick(() => this.$refs.pinInput?.focus());
            if (unlockExpiresAt) {
                const tick = () => { this.remaining = Math.max(0, unlockExpiresAt - Math.floor(Date.now() / 1000)); };
                tick();
                const timer = setInterval(() => { tick(); if (this.remaining <= 0) clearInterval(timer); }, 1000);
            }
            this.$watch('newPassword', (value) => { if (!value) this.showPassword = false; });
        },

        confirmSubmit(event) {
            if (this.mode === 'live' && this.currentMode === 'sandbox' && !this.confirmLive) {
                event.preventDefault();
                window.dispatchEvent(new CustomEvent('admin-toast', { detail: { message: 'Confirm the switch to Live mode first.', type: 'error' } }));
                return false;
            }
            this.showPassword = false;
            return true;
        },
    };
}
</script>
@endpush
