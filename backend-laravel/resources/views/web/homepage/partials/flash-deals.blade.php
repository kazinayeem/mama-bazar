@php
    $items = $items ?? [];
    $window = $flashSaleWindow ?? [];
    $endsAt = $window['endsAt'] ?? null;
@endphp

@if(count($items) > 0)
<section class="store-section bg-brand-green-50/70">
    <div class="store-container">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3 sm:mb-6">
            <div>
                @if(!empty($section['eyebrow']))
                    <span class="mb-2 inline-flex items-center rounded-full border border-brand-orange-200 bg-brand-orange-50 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em] text-brand-orange-600">{{ $section['eyebrow'] }}</span>
                @endif
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl lg:text-[28px]">{{ $section['title'] ?? 'Flash Deals' }}</h2>
                @if(!empty($section['subtitle']))
                    <p class="mt-1 text-sm text-slate-500">{{ $section['subtitle'] }}</p>
                @endif
            </div>
            @if($endsAt)
                <div
                    class="rounded-[8px] border border-brand-orange-200 bg-white px-3.5 py-2 text-xs font-bold text-brand-orange-600"
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
        <div class="store-product-grid">
            @foreach($items as $i => $product)
                <x-product-card :product="$product" :index="$i" />
            @endforeach
        </div>
    </div>
</section>
@endif
