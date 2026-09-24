@php
    $settings = $settings ?? [];
@endphp

@if(!empty($settings['enabled']))
<section class="bg-brand-green-50 py-6" x-data="{ email: '', loading: false, message: '' }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-green-800 via-brand-green-600 to-brand-green-500 px-6 py-10 shadow-lift sm:px-12 lg:py-12">
            <div class="relative grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white backdrop-blur">Newsletter</span>
                    <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ $settings['title'] ?? 'Never miss a deal' }}</h2>
                    <p class="mt-3 max-w-md text-[15px] leading-7 text-white/80">{{ $settings['subtitle'] ?? 'Subscribe for exclusive deals, early access to new arrivals and smart buying tips.' }}</p>
                </div>
                <form
                    class="flex flex-col gap-3 rounded-[20px] border border-white/20 bg-white/10 p-3 backdrop-blur-md sm:flex-row"
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
                    <input
                        type="email"
                        required
                        x-model="email"
                        placeholder="Enter your email address"
                        class="w-full rounded-full bg-white/95 px-5 py-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-orange-400"
                    >
                    <button
                        type="submit"
                        :disabled="loading"
                        class="shrink-0 rounded-full bg-brand-orange-500 px-7 py-3.5 text-sm font-bold text-white shadow-soft transition hover:bg-brand-orange-600 active:scale-95 disabled:opacity-60"
                        x-text="loading ? 'Subscribing…' : (@js($settings['buttonText'] ?? 'Subscribe'))"
                    ></button>
                </form>
                <p x-show="message" x-text="message" class="text-sm font-semibold text-white lg:col-span-2"></p>
            </div>
        </div>
    </div>
</section>
@endif
