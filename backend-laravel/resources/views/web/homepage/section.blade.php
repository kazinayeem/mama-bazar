@php
    $type = $type ?? ($section['type'] ?? '');
    $data = $data ?? ($section['data'] ?? []);
    $items = $items ?? ($data['items'] ?? []);
@endphp

@switch($type)
    @case('hero')
        @include('web.homepage.partials.hero', [
            'slides' => $data['slides'] ?? ($homepageData['heroSlides'] ?? []),
            'popularSearches' => $homepageData['popularSearches'] ?? ($config['popularSearches'] ?? []),
        ])
        @break

    @case('trust_strip')
        @include('web.homepage.partials.trust-strip', [
            'items' => !empty($items) ? $items : ($homepageData['trustStrip'] ?? []),
        ])
        @break

    @case('categories')
        <x-homepage.section-shell :section="$section">
            @php
                $cols = (int) ($section['columns'] ?? 4);
                $gridClass = match (true) {
                    $cols >= 6 => 'grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6',
                    $cols === 5 => 'grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
                    default => 'grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4',
                };
            @endphp
            <div class="{{ $gridClass }}">
                @foreach($items as $cat)
                    @php
                        $c = is_array($cat) ? $cat : (array) $cat;
                        $slug = $c['slug'] ?? '';
                        $name = $c['name'] ?? '';
                        $image = $c['image'] ?? $c['thumbnail'] ?? null;
                        $count = $c['productCount'] ?? $c['products_count'] ?? null;
                    @endphp
                    <a href="{{ route('shop', ['category' => $slug]) }}" class="group flex flex-col items-center rounded-[10px] border border-brand-green-100 bg-white px-2.5 py-3.5 text-center transition duration-200 hover:-translate-y-0.5 hover:border-brand-green-300 hover:shadow-soft">
                        <div class="mb-2.5 flex h-14 w-14 items-center justify-center rounded-full bg-brand-green-50 p-2 sm:h-16 sm:w-16">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $name }}" class="h-10 w-10 object-contain sm:h-12 sm:w-12" loading="lazy">
                            @else
                                <span class="text-lg font-black text-brand-green-600">{{ mb_substr($name, 0, 1) }}</span>
                            @endif
                        </div>
                        <span class="max-w-full truncate text-xs font-bold text-slate-800 group-hover:text-brand-green-600">{{ $name }}</span>
                        @if($count !== null)
                            <span class="mt-0.5 text-[10px] text-slate-400">{{ $count }} items</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </x-homepage.section-shell>
        @break

    @case('category_products')
    @case('featured')
    @case('best_sellers')
    @case('trending')
    @case('new_arrivals')
    @case('limited_edition')
    @case('official')
    @case('hot_deals')
    @case('emi_available')
    @case('recommendations')
        <x-homepage.section-shell :section="$section">
            <div class="store-product-grid">
                @foreach($items as $i => $product)
                    <x-product-card :product="$product" :index="$i" />
                @endforeach
            </div>
        </x-homepage.section-shell>
        @break

    @case('flash_deals')
        @include('web.homepage.partials.flash-deals', [
            'section' => $section,
            'items' => $items,
            'flashSaleWindow' => $homepageData['flashSaleWindow'] ?? [],
        ])
        @break

    @case('promo_banner')
        @php
            $offset = ((int) ($promoIndex ?? 0)) * 2;
            $banners = array_slice(array_values($items), $offset, 2);
        @endphp
        @if(!empty($banners))
            <section class="store-section !py-4">
                <div class="store-container grid gap-4 lg:grid-cols-2">
                    @foreach($banners as $banner)
                        @php
                            $b = is_array($banner) ? $banner : (array) $banner;
                            if (isset($banner->image)) {
                                $b = [
                                    'image' => $banner->image,
                                    'title' => $banner->title,
                                    'link' => $banner->link,
                                    'button_text' => $banner->button_text ?? null,
                                ];
                            }
                            $img = $b['image'] ?? $b['desktopImage'] ?? null;
                            $link = $b['link'] ?? $b['primaryButtonUrl'] ?? '#';
                            $title = $b['title'] ?? '';
                        @endphp
                        @if($img)
                            <a href="{{ $link }}" class="group relative block overflow-hidden rounded-[12px]">
                                <img src="{{ $img }}" alt="{{ $title }}" class="h-40 w-full object-cover transition duration-500 group-hover:scale-[1.03] sm:h-52" loading="lazy">
                                @if($title)
                                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-5 py-4 text-sm font-bold text-white">{{ $title }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
        @break

    @case('brands')
        <x-homepage.section-shell :section="$section">
            <div class="flex gap-3 overflow-x-auto pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($items as $brand)
                    @php
                        $b = is_array($brand) ? $brand : (array) $brand;
                        $logo = $b['logo'] ?? null;
                        $name = $b['name'] ?? '';
                        $slug = $b['slug'] ?? '';
                    @endphp
                    <a href="{{ route('shop', ['brand' => $slug]) }}" class="store-brand-chip flex h-16 w-28 shrink-0 items-center justify-center rounded-[10px] border border-slate-200 bg-white px-3 transition duration-200 hover:border-brand-green-300 sm:h-[72px] sm:w-32" title="{{ $name }}">
                        @if($logo)
                            <img src="{{ $logo }}" alt="{{ $name }}" class="store-brand-logo max-h-9 max-w-full object-contain sm:max-h-10" loading="lazy">
                        @else
                            <span class="text-xs font-bold text-slate-600">{{ $name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </x-homepage.section-shell>
        @break

    @case('collections')
        <x-homepage.section-shell :section="$section">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($items as $collection)
                    @php
                        $c = is_array($collection) ? $collection : [
                            'name' => $collection->name ?? '',
                            'slug' => $collection->slug ?? '',
                            'image' => $collection->image ?? $collection->banner ?? null,
                            'description' => $collection->description ?? '',
                        ];
                        $img = $c['image'] ?? $c['banner'] ?? null;
                    @endphp
                    <a href="{{ route('shop', ['collection' => $c['slug'] ?? '']) }}" class="group relative overflow-hidden rounded-3xl border border-brand-green-100 bg-white">
                        <div class="aspect-[16/9] bg-brand-green-50">
                            @if($img)
                                <img src="{{ $img }}" alt="{{ $c['name'] ?? '' }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="text-sm font-extrabold text-slate-900">{{ $c['name'] ?? '' }}</h3>
                            @if(!empty($c['description']))
                                <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $c['description'] }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </x-homepage.section-shell>
        @break

    @case('reviews')
        <x-homepage.section-shell :section="$section">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($items as $review)
                    @php
                        $r = is_array($review) ? $review : (array) $review;
                        $rating = (int) ($r['rating'] ?? 5);
                        $name = $r['customerName'] ?? $r['customer_name'] ?? 'Customer';
                        $comment = $r['comment'] ?? '';
                        $productTitle = $r['productTitle'] ?? $r['product_title'] ?? '';
                    @endphp
                    <div class="rounded-[10px] border border-brand-green-100 bg-white p-4">
                        <div class="flex gap-0.5 text-amber-400" aria-label="{{ $rating }} out of 5 stars">
                            @for($i = 0; $i < 5; $i++)
                                <span class="text-sm" aria-hidden="true">{{ $i < $rating ? '★' : '☆' }}</span>
                            @endfor
                        </div>
                        <p class="mt-2.5 line-clamp-3 text-sm leading-6 text-slate-600">{{ $comment }}</p>
                        <p class="mt-3 text-xs font-bold text-slate-900">{{ $name }}</p>
                        @if($productTitle)
                            <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $productTitle }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-homepage.section-shell>
        @break

    @case('why_choose_us')
        @include('web.homepage.partials.why-choose-us', [
            'section' => $section,
            'items' => !empty($items) ? $items : ($homepageData['whyChooseUs'] ?? []),
        ])
        @break

    @case('newsletter')
        @include('web.homepage.partials.newsletter', [
            'settings' => $data['settings'] ?? ($homepageData['newsletter'] ?? $config['newsletter'] ?? []),
        ])
        @break

    @default
        {{-- Unknown section types are silently skipped --}}
@endswitch
