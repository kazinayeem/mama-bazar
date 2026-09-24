@php
    /** @var array $homepageData */
    /** @var array $config */
    $sections = $homepageData['sections'] ?? [];
    $promoCounter = 0;
@endphp

@if(empty($sections))
    <div class="mx-auto max-w-7xl px-4 py-20 text-center">
        <h2 class="text-lg font-extrabold text-slate-900">Homepage not configured</h2>
        <p class="mt-2 text-sm text-slate-500">Open the Homepage Builder in admin and publish a layout.</p>
    </div>
@else
    @foreach($sections as $section)
        @if(($section['enabled'] ?? true) === false)
            @continue
        @endif

        @php
            $type = $section['type'] ?? '';
            $data = $section['data'] ?? [];
            $items = $data['items'] ?? [];
            if ($items instanceof \Illuminate\Support\Collection) {
                $items = $items->all();
            }

            $skip = false;
            $emptySkip = in_array($type, [
                'categories', 'category_products', 'featured', 'best_sellers', 'trending', 'new_arrivals',
                'limited_edition', 'official', 'hot_deals', 'emi_available', 'recommendations',
                'brands', 'collections', 'reviews', 'flash_deals', 'promo_banner',
            ], true);

            if ($emptySkip && empty($items)) {
                $skip = true;
            }

            if ($type === 'hero') {
                // Hero section always renders (carousel and/or popular searches) when enabled.
                $skip = false;
            }

            if ($type === 'trust_strip') {
                $items = !empty($items) ? $items : ($homepageData['trustStrip'] ?? $config['trustStrip'] ?? []);
                if (empty($items)) $skip = true;
            }

            if ($type === 'why_choose_us') {
                $items = !empty($items) ? $items : ($homepageData['whyChooseUs'] ?? $config['whyChooseUs'] ?? []);
                if (empty($items)) $skip = true;
            }

            if ($type === 'newsletter') {
                $newsletter = $data['settings'] ?? ($homepageData['newsletter'] ?? $config['newsletter'] ?? []);
                if (empty($newsletter['enabled'])) $skip = true;
            }

            $currentPromoIndex = 0;
            if (!$skip && $type === 'promo_banner') {
                $currentPromoIndex = $promoCounter++;
            }
        @endphp

        @if(!$skip)
            @include('web.homepage.section', [
                'section' => $section,
                'type' => $type,
                'data' => $data,
                'items' => $items ?? [],
                'homepageData' => $homepageData,
                'config' => $config,
                'promoIndex' => $currentPromoIndex,
            ])
        @endif
    @endforeach
@endif
