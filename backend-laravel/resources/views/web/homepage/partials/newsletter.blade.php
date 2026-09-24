@php
    $settings = $settings ?? [];
@endphp

@if(!empty($settings['enabled']))
<section class="store-section" x-data="{ email: '', loading: false, message: '' }">
    <div class="store-container">
        <div class="overflow-hidden rounded-[12px] bg-gradient-to-br from-brand-green-700 via-brand-green-600 to-brand-green-500 px-5 py-8 sm:px-8 sm:py-9 lg:px-10">
            <div class="grid items-center gap-6 lg:grid-cols-[1.1fr_1fr] lg:gap-10">
                <div>
                    <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em] text-white/90">Newsletter</span>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $settings['title'] ?? 'Never miss a deal' }}</h2>
                    <p class="mt-2 max-w-md text-sm leading-6 text-white/80">{{ $settings['subtitle'] ?? 'Get updates about new products and special offers.' }}</p>
                </div>
                <form
                    class="flex flex-col gap-2 sm:flex-row sm:items-stretch"
                    @submit.prevent="
                        loading = true; message = '';
                        fetch(@js(route('newsletter.subscribe')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': @js(csrf_token()),
                            },
                            body: JSON.stringify({ email, source: 'homepage' }),
                        })
                        .then(r => r.json())
                        .then(j => {
                            message = j.data?.alreadySubscribed ? 'You are already subscribed!' : 'Subscribed! Thanks for joining.';
                            email = '';
                        })
                        .catch(() => { message = 'Subscription failed. Please try again.'; })
                        .finally(() => { loading = false; });
                    "
                >
                    <label class="sr-only" for="newsletter-email">Email address</label>
                    <input
                        id="newsletter-email"
                        type="email"
                        required
                        x-model="email"
                        placeholder="Enter your email"
                        class="h-11 w-full rounded-[8px] border-0 bg-white px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-orange-400"
                    >
                    <button
                        type="submit"
                        :disabled="loading"
                        class="store-btn store-btn-accent h-11 shrink-0 px-6 disabled:opacity-60"
                        x-text="loading ? 'Subscribing…' : (@js($settings['buttonText'] ?? 'Subscribe'))"
                    ></button>
                </form>
                <p x-show="message" x-text="message" class="text-sm font-semibold text-white lg:col-span-2" x-cloak></p>
            </div>
        </div>
    </div>
</section>
@endif
