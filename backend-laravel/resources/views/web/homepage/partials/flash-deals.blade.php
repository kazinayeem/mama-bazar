@php
    $items = $items ?? [];
    $window = $flashSaleWindow ?? [];
    $endsAt = $window['endsAt'] ?? now()->addDays(2)->toIso8601String();
    $firstProduct = $items[0] ?? null;
    $remainingProducts = count($items) > 1 ? array_slice($items, 1) : $items;
@endphp

@if(count($items) > 0)
<section class="py-6 sm:py-8 bg-[#f5f5f5]">
    <div class="store-container space-y-6">

        {{-- ── Big Pink/Burgundy Flash Sale Countdown Banner ── --}}
        <div class="relative overflow-hidden rounded-2xl shadow-md p-6 sm:p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6"
             style="background: linear-gradient(135deg, #7a0035 0%, #b80053 45%, #e2006a 100%)"
             x-data="{
                ends: new Date({{ json_encode($endsAt) }}).getTime(),
                days: '00', hours: '00', mins: '00', secs: '00',
                tick() {
                    const d = Math.max(0, this.ends - Date.now());
                    this.days = String(Math.floor(d / (1000 * 60 * 60 * 24))).padStart(2, '0');
                    this.hours = String(Math.floor((d % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                    this.mins = String(Math.floor((d % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                    this.secs = String(Math.floor((d % (1000 * 60)) / 1000)).padStart(2, '0');
                }
             }"
             x-init="tick(); setInterval(() => tick(), 1000)">

            {{-- Left: Badge + Tagline --}}
            <div class="flex items-center gap-4 text-center md:text-left">
                <div class="hidden sm:flex flex-col items-center justify-center w-20 h-20 rounded-xl bg-white/20 backdrop-blur-md border border-white/30 shrink-0">
                    <span class="text-xs font-bold text-yellow-300">LIMITED</span>
                    <span class="text-base font-black leading-tight">DEALS</span>
                    <span class="text-[10px] font-semibold text-white/80">SOON</span>
                </div>
                <div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight uppercase">FLASH SALE</h2>
                    <p class="text-xs sm:text-sm text-pink-100 font-medium mt-1">The biggest sale ever on latest &amp; hot collection</p>
                </div>
            </div>

            {{-- Right: Live Countdown Digit Blocks --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="flex flex-col items-center">
                    <div class="w-13 h-13 sm:w-16 sm:h-16 rounded-xl bg-white text-slate-900 font-black text-xl sm:text-2xl flex items-center justify-center shadow-lg" x-text="days">00</div>
                    <span class="text-[10px] sm:text-xs font-bold mt-1 text-pink-100 uppercase tracking-wider">Days</span>
                </div>
                <span class="text-2xl font-black text-white/80 pb-4">:</span>
                <div class="flex flex-col items-center">
                    <div class="w-13 h-13 sm:w-16 sm:h-16 rounded-xl bg-white text-slate-900 font-black text-xl sm:text-2xl flex items-center justify-center shadow-lg" x-text="hours">00</div>
                    <span class="text-[10px] sm:text-xs font-bold mt-1 text-pink-100 uppercase tracking-wider">Hours</span>
                </div>
                <span class="text-2xl font-black text-white/80 pb-4">:</span>
                <div class="flex flex-col items-center">
                    <div class="w-13 h-13 sm:w-16 sm:h-16 rounded-xl bg-white text-slate-900 font-black text-xl sm:text-2xl flex items-center justify-center shadow-lg" x-text="mins">00</div>
                    <span class="text-[10px] sm:text-xs font-bold mt-1 text-pink-100 uppercase tracking-wider">Mins</span>
                </div>
                <span class="text-2xl font-black text-white/80 pb-4">:</span>
                <div class="flex flex-col items-center">
                    <div class="w-13 h-13 sm:w-16 sm:h-16 rounded-xl bg-white text-slate-900 font-black text-xl sm:text-2xl flex items-center justify-center shadow-lg" x-text="secs">00</div>
                    <span class="text-[10px] sm:text-xs font-bold mt-1 text-pink-100 uppercase tracking-wider">Secs</span>
                </div>
            </div>
        </div>

        {{-- ── Section Header ── --}}
        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-6 rounded-full" style="background:#e2006a"></span>
                <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">Flash Sale</h3>
            </div>
            <a href="{{ route('shop', ['sale' => 'true']) }}" class="group inline-flex items-center gap-1 text-xs font-bold text-slate-600 transition hover:text-[#e2006a]">
                <span>View All</span>
                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- ── Flash Deals Grid: Hero Left Card + Product Grid Right ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-stretch">
            {{-- Left: Dark Feature Card --}}
            @if($firstProduct)
                @php
                    $fp = is_array($firstProduct) ? $firstProduct : (array) $firstProduct;
                    $fpSlug = $fp['slug'] ?? '';
                    $fpUrl = $fpSlug ? route('products.show', $fpSlug) : route('shop');
                    $fpImg = $fp['image'] ?? ($fp['thumbnail'] ?? '/brandlogo.png');
                    if (is_array($fp['images'] ?? null) && !empty($fp['images'])) {
                        $fpImg = is_array($fp['images'][0]) ? ($fp['images'][0]['url'] ?? '/brandlogo.png') : $fp['images'][0];
                    }
                @endphp
                <div class="lg:col-span-1 rounded-2xl p-6 text-white flex flex-col justify-between shadow-lg relative overflow-hidden"
                     style="background: linear-gradient(160deg, #1f1b2e 0%, #2a2035 100%)">
                    <div>
                        <span class="inline-block rounded-full px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white mb-4">
                            ⚡ Hot Deal
                        </span>
                        <h4 class="text-2xl font-black uppercase tracking-tight text-white leading-tight">FLASH SALE</h4>
                        <p class="text-xs text-slate-300 mt-2 line-clamp-2">The biggest discount of the season. Grab yours now!</p>
                    </div>

                    <div class="my-6 flex justify-center items-center">
                        <img src="{{ $fpImg }}" alt="{{ $fp['title'] ?? 'Flash Deal' }}" class="max-h-48 w-auto object-contain drop-shadow-2xl transition hover:scale-105 duration-300">
                    </div>

                    <div>
                        <div class="text-center mb-3">
                            <span class="text-xs font-bold text-slate-300">Starts From</span>
                            <div class="text-xl font-extrabold text-pink-400">৳{{ number_format((float)($fp['salePrice'] ?? $fp['sale_price'] ?? $fp['price'] ?? 0), 0) }}</div>
                        </div>
                        <a href="{{ $fpUrl }}"
                           class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-white font-extrabold text-xs shadow-md transition hover:brightness-110"
                           style="background:#e2006a">
                            <span>Shop Now</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Right: Product Cards Grid --}}
            <div class="{{ $firstProduct ? 'lg:col-span-3' : 'lg:col-span-4' }} grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                @foreach(array_slice($remainingProducts, 0, 6) as $i => $product)
                    <x-product-card :product="$product" :index="$i" />
                @endforeach
            </div>
        </div>

    </div>
</section>
@endif

