<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Collection;
use App\Models\Review;
use App\Models\Product;
use App\Models\Newsletter;
use Illuminate\Support\Facades\DB;
use Exception;

class HomepageService
{
    const DEFAULT_CONFIG = [
        'announcement' => [
            'enabled' => true,
            'text' => 'Free delivery on orders over ৳2,000 — shop today!',
            'backgroundColor' => '#1e293b',
            'textColor' => '#ffffff',
        ],
        'heroSlides' => [],
        'sections' => [
            ['id' => 'hero', 'type' => 'hero', 'enabled' => true],
            ['id' => 'trust_strip', 'type' => 'trust_strip', 'enabled' => true, 'title' => 'Why shop with us'],
            ['id' => 'categories', 'type' => 'categories', 'enabled' => true, 'title' => 'Explore Categories', 'subtitle' => 'Discover our wide range of products across all categories.', 'limit' => 12],
            ['id' => 'new_arrivals', 'type' => 'new_arrivals', 'enabled' => true, 'title' => 'New arrivals', 'subtitle' => 'Fresh products just added to the store.', 'limit' => 12],
            ['id' => 'promo_banner', 'type' => 'promo_banner', 'enabled' => true],
            ['id' => 'featured', 'type' => 'featured', 'enabled' => true, 'title' => 'Featured products', 'subtitle' => 'Handpicked favourites from our catalogue.', 'limit' => 12],
            ['id' => 'brands', 'type' => 'brands', 'enabled' => true, 'title' => 'Trusted brands', 'subtitle' => '100% authentic products from official distributors.', 'limit' => 10],
            ['id' => 'promo_banner_2', 'type' => 'promo_banner', 'enabled' => true],
            ['id' => 'collections', 'type' => 'collections', 'enabled' => true, 'title' => 'Featured collections', 'subtitle' => 'Complete setups built for every lifestyle.', 'limit' => 6],
            ['id' => 'flash_deals', 'type' => 'flash_deals', 'enabled' => true, 'title' => 'Flash Deals', 'subtitle' => 'Limited-time prices. When they\'re gone, they\'re gone.', 'limit' => 12, 'background' => 'muted'],
            ['id' => 'best_sellers', 'type' => 'best_sellers', 'enabled' => true, 'title' => 'Best sellers', 'subtitle' => 'The most-ordered products right now.', 'limit' => 12],
            ['id' => 'trending', 'type' => 'trending', 'enabled' => true, 'title' => 'Trending right now', 'subtitle' => 'The products everyone is talking about.', 'limit' => 10, 'background' => 'muted'],
            ['id' => 'limited_edition', 'type' => 'limited_edition', 'enabled' => true, 'title' => 'Limited Edition', 'subtitle' => 'Exclusive products available for a limited time.', 'limit' => 12],
            ['id' => 'official', 'type' => 'official', 'enabled' => true, 'title' => 'Official Products', 'subtitle' => '100% authentic products from official sources.', 'limit' => 12],
            ['id' => 'hot_deals', 'type' => 'hot_deals', 'enabled' => true, 'title' => 'Hot Deals', 'subtitle' => 'The hottest deals you don\'t want to miss.', 'limit' => 12, 'background' => 'muted'],
            ['id' => 'emi_available', 'type' => 'emi_available', 'enabled' => true, 'title' => 'EMI Available', 'subtitle' => 'Buy now and pay in easy installments.', 'limit' => 12],
            ['id' => 'recommendations', 'type' => 'recommendations', 'enabled' => true, 'title' => 'Recommended for you', 'subtitle' => 'Picked based on what you\'ve browsed and bought.', 'limit' => 10],
            ['id' => 'why_choose_us', 'type' => 'why_choose_us', 'enabled' => true, 'title' => 'Why choose Mama Bazar'],
            ['id' => 'reviews', 'type' => 'reviews', 'enabled' => true, 'title' => 'What customers say', 'subtitle' => 'Real feedback from verified buyers.', 'limit' => 8],
            ['id' => 'newsletter', 'type' => 'newsletter', 'enabled' => true, 'title' => 'Never miss a deal'],
        ],
        'trustStrip' => [
            ['icon' => 'Truck', 'title' => 'Fast nationwide delivery', 'text' => '2-5 days anywhere in Bangladesh'],
            ['icon' => 'ShieldCheck', 'title' => 'Official warranty', 'text' => '100% authentic, manufacturer-backed'],
            ['icon' => 'RefreshCcw', 'title' => 'Easy returns', 'text' => '7-day hassle-free returns'],
            ['icon' => 'Headphones', 'title' => '24/7 support', 'text' => 'Real humans, always here to help'],
        ],
        'whyChooseUs' => [
            ['icon' => 'BadgeCheck', 'title' => 'Authentic products', 'text' => 'Sourced directly from official distributors with full warranty coverage.'],
            ['icon' => 'Truck', 'title' => 'Cash on delivery', 'text' => 'Pay when your order arrives at your doorstep — across all districts.'],
            ['icon' => 'ShieldCheck', 'title' => 'Secure payments', 'text' => 'bKash, Nagad, card and bank transfers with verified transactions.'],
            ['icon' => 'Headphones', 'title' => 'Dedicated support', 'text' => 'Chat, call or message us — our team responds within minutes.'],
        ],
        'newsletter' => [
            'enabled' => true,
            'title' => 'Never miss a deal',
            'subtitle' => 'Subscribe for exclusive deals, early access to new arrivals and smart buying tips.',
            'buttonText' => 'Subscribe',
        ],
        'flashSaleWindow' => [
            'enabled' => false,
            'start' => null,
            'end' => null,
        ],
        'popularSearches' => ['Blender', 'Electric kettle', 'LED TV', 'Ceiling fan', 'Microwave oven'],
    ];

    public static function mergeDeep(array $base, ?array $saved): array
    {
        if (!$saved || !is_array($saved)) {
            return $base;
        }

        $config = [
            'announcement' => array_merge($base['announcement'], $saved['announcement'] ?? []),
            'heroSlides' => is_array($saved['heroSlides'] ?? null) ? $saved['heroSlides'] : $base['heroSlides'],
            'sections' => is_array($saved['sections'] ?? null) ? $saved['sections'] : $base['sections'],
            'trustStrip' => is_array($saved['trustStrip'] ?? null) ? $saved['trustStrip'] : $base['trustStrip'],
            'whyChooseUs' => is_array($saved['whyChooseUs'] ?? null) ? $saved['whyChooseUs'] : $base['whyChooseUs'],
            'newsletter' => array_merge($base['newsletter'], $saved['newsletter'] ?? []),
            'flashSaleWindow' => array_merge($base['flashSaleWindow'], $saved['flashSaleWindow'] ?? []),
            'popularSearches' => is_array($saved['popularSearches'] ?? null) ? $saved['popularSearches'] : $base['popularSearches'],
        ];

        $byId = [];
        foreach ($config['sections'] as $section) {
            if (!empty($section['id'])) {
                $byId[$section['id']] = $section;
            }
        }
        foreach ($base['sections'] as $def) {
            $id = $def['id'] ?? null;
            if ($id && !isset($byId[$id])) {
                $config['sections'][] = $def;
            }
        }

        return $config;
    }

    public static function getConfig(): array
    {
        $setting = SiteSetting::where('key', 'homepage_config')->first();
        if (!$setting || empty($setting->value)) {
            return self::DEFAULT_CONFIG;
        }

        $decoded = json_decode($setting->value, true);

        return is_array($decoded) ? self::mergeDeep(self::DEFAULT_CONFIG, $decoded) : self::DEFAULT_CONFIG;
    }

    public static function saveConfig(array $config): array
    {
        $merged = self::mergeDeep(self::DEFAULT_CONFIG, $config);
        SiteSetting::updateOrCreate(
            ['key' => 'homepage_config'],
            ['value' => json_encode($merged)]
        );

        return $merged;
    }

    public static function resetConfig(): array
    {
        SiteSetting::updateOrCreate(
            ['key' => 'homepage_config'],
            ['value' => json_encode(self::DEFAULT_CONFIG)]
        );
        return self::DEFAULT_CONFIG;
    }

    public static function getHomepage(?int $userId = null): array
    {
        $config = self::getConfig();

        $heroBanners = Banner::where('status', 'active')
            ->where('position', 'hero')
            ->orderBy('priority', 'desc')
            ->get();

        $slides = $heroBanners->map(fn($b) => [
            'id' => (string) $b->id,
            'title' => $b->title,
            'subtitle' => $b->subtitle,
            'desktopImage' => $b->image,
            'mobileImage' => $b->image_mobile,
            'tabletImage' => $b->image_tablet,
            'link' => $b->link,
            'buttonText' => $b->button_text,
            'status' => $b->status,
            'priority' => $b->priority,
        ])->toArray();

        $categories = Category::where('status', 'active')
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'asc')
            ->take(12)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image,
                'icon' => $c->icon,
                'thumbnail' => $c->thumbnail,
                'productCount' => Product::where('status', 'active')->where('category_id', $c->id)->count(),
            ])->toArray();

        $brands = Brand::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->take(10)
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'logo' => $b->logo,
            ])->toArray();

        $collections = Collection::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->take(6)
            ->get();

        $promoBanners = Banner::where('status', 'active')
            ->whereIn('position', ['banner', 'promo'])
            ->orderBy('priority', 'desc')
            ->get();

        $reviews = Review::where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'customerName' => $r->customer_name,
                'rating' => $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'createdAt' => $r->created_at ? $r->created_at->toIso8601String() : null,
                'productTitle' => $r->product?->title,
                'productSlug' => $r->product?->slug,
            ])->toArray();

        // Product queries for each label
        $fetchLabelProducts = function ($labelField, $limit = 12) {
            $prods = Product::where('status', 'active')
                ->where($labelField, true)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
            $ratings = ProductService::fetchRatingMap($prods->pluck('id')->toArray());
            return $prods->map(fn($p) => ProductService::formatProduct($p, $ratings[$p->id] ?? null))->toArray();
        };

        $flashSaleProducts = $fetchLabelProducts('is_flash_sale', 12);
        $featuredProducts = $fetchLabelProducts('is_featured', 12);
        $newArrivals = $fetchLabelProducts('is_new_arrival', 12);
        $bestSellers = $fetchLabelProducts('is_best_seller', 12);
        $trendingProducts = $fetchLabelProducts('is_trending', 12);

        $sections = array_map(function ($section) use (
            $slides, $categories, $brands, $collections, $promoBanners, $reviews,
            $flashSaleProducts, $featuredProducts, $newArrivals, $bestSellers, $trendingProducts, $config
        ) {
            $data = [];
            switch ($section['type']) {
                case 'hero':
                    $data['slides'] = $slides;
                    break;
                case 'trust_strip':
                    $data['items'] = $config['trustStrip'] ?? [];
                    break;
                case 'categories':
                    $data['items'] = $categories;
                    break;
                case 'brands':
                    $data['items'] = $brands;
                    break;
                case 'collections':
                    $data['items'] = $collections;
                    break;
                case 'promo_banner':
                    $data['items'] = $promoBanners;
                    break;
                case 'reviews':
                    $data['items'] = $reviews;
                    break;
                case 'flash_deals':
                    $data['items'] = $flashSaleProducts;
                    break;
                case 'featured':
                    $data['items'] = $featuredProducts;
                    break;
                case 'new_arrivals':
                    $data['items'] = $newArrivals;
                    break;
                case 'best_sellers':
                    $data['items'] = $bestSellers;
                    break;
                case 'trending':
                    $data['items'] = $trendingProducts;
                    break;
                case 'why_choose_us':
                    $data['items'] = $config['whyChooseUs'] ?? [];
                    break;
                case 'newsletter':
                    $data['settings'] = $config['newsletter'] ?? [];
                    break;
            }
            $section['data'] = $data;
            return $section;
        }, $config['sections'] ?? []);

        return [
            'announcement' => $config['announcement'] ?? null,
            'heroSlides' => $slides,
            'flashSaleWindow' => array_merge($config['flashSaleWindow'] ?? [], [
                'isActive' => true,
                'endsAt' => $config['flashSaleWindow']['end'] ?? null,
            ]),
            'popularSearches' => $config['popularSearches'] ?? [],
            'sections' => $sections,
        ];
    }

    public static function subscribeNewsletter(string $email, ?string $source = 'homepage'): array
    {
        $normalized = strtolower(trim($email));
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("A valid email address is required", 400);
        }

        $existing = Newsletter::where('email', $normalized)->first();
        if ($existing) {
            return ['email' => $normalized, 'alreadySubscribed' => true];
        }

        Newsletter::create([
            'email' => $normalized,
            'source' => $source ?: 'homepage',
            'status' => 'subscribed',
        ]);

        return ['email' => $normalized, 'alreadySubscribed' => false];
    }

    public static function getSubscribers()
    {
        return Newsletter::orderBy('subscribed_at', 'desc')->get();
    }
}
