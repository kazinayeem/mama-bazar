@php
    $items = $items ?? [];
    $window = $flashSaleWindow ?? [];
    $endsAt = $window['endsAt'] ?? null;
@endphp

@if(count($items) > 0)
<section class="bg-brand-green-50 py-6 lg:py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3 lg:mb-6">
            <div>
                @if(!empty($section['eyebrow']))
                    <span class="mb-2.5 inline-flex items-center rounded-full border border-brand-orange-200 bg-brand-orange-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-brand-orange-600">{{ $section['eyebrow'] }}</span>
                @endif
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $section['title'] ?? 'Flash Deals' }}</h2>
                @if(!empty($section['subtitle']))
                    <p class="mt-1.5 text-sm text-slate-500">{{ $section['subtitle'] }}</p>
                @endif
            </div>
            @if($endsAt)
                <div
                    class="rounded-2xl border border-brand-orange-200 bg-white px-4 py-2 text-xs font-bold text-brand-orange-600"
                    x-data="{
                        ends: new Date({{ json_encode($endsAt) }}).getTime(),
                        label: '',
                        tick() {
                            const d = Math.max(0, this.ends - Date.now());
                            const h = Math.floor(d / 3600000);
                            const m = Math.floor((d % 3600000) / 60000);
                            const s = Math.floor((d % 60000) / 1000);
                            this.label = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                        }
                    }"
                    x-init="tick(); setInterval(() => tick(), 1000)"
                >
                    Ends in <span x-text="label">--:--:--</span>
                </div>
            @endif
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            @foreach($items as $product)
                <x-homepage.product-tile :product="$product" />
            @endforeach
        </div>
    </div>
</section>
@endif
