<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Get SEO metadata for Product Detail Page.
     */
    public static function getForProduct(Product|array $product): array
    {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';
        $baseUrl = config('app.url', url('/'));

        $isModel = $product instanceof Product;
        $id = $isModel ? $product->id : ($product['id'] ?? 0);
        $title = $isModel ? $product->title : ($product['title'] ?? '');
        $slug = $isModel ? $product->slug : ($product['slug'] ?? '');
        $sku = $isModel ? $product->sku : ($product['sku'] ?? null);
        $price = (float) ($isModel ? ($product->sale_price ?? $product->price) : ($product['salePrice'] ?? $product['price'] ?? 0));
        $originalPrice = (float) ($isModel ? $product->price : ($product['price'] ?? 0));
        $stock = (int) ($isModel ? $product->stock : ($product['stock'] ?? 0));
        $unlimitedStock = (bool) ($isModel ? $product->unlimited_stock : ($product['unlimitedStock'] ?? false));
        $inStock = $stock > 0 || $unlimitedStock;

        // Custom SEO meta from database table if present
        $seoMeta = SeoMeta::where('model_type', Product::class)
            ->where('model_id', $id)
            ->first();

        // Brand resolution
        $brandName = null;
        if ($isModel) {
            $brandName = $product->brandRel?->name ?? $product->brand ?? null;
        } else {
            $brandName = $product['brandInfo']['name'] ?? $product['brand'] ?? null;
        }

        // Category resolution
        $categoryName = null;
        $categorySlug = null;
        if ($isModel) {
            $categoryName = $product->category?->name;
            $categorySlug = $product->category?->slug;
        } else {
            $categoryName = $product['category']['name'] ?? null;
            $categorySlug = $product['category']['slug'] ?? null;
        }

        // Title generation
        $customTitle = $seoMeta?->seo_title ?: ($isModel ? $product->seo_title : ($product['seoTitle'] ?? null));
        if ($customTitle) {
            $metaTitle = $customTitle;
        } else {
            $brandPart = $brandName ? " - {$brandName}" : '';
            $metaTitle = "{$title}{$brandPart} | {$siteName}";
        }

        // Description generation
        $customDesc = $seoMeta?->seo_description ?: ($isModel ? $product->seo_description : ($product['seoDescription'] ?? null));
        if ($customDesc) {
            $metaDescription = $customDesc;
        } else {
            $rawDesc = $isModel
                ? ($product->short_description ?: strip_tags($product->description ?? ''))
                : ($product['shortDescription'] ?? strip_tags($product['description'] ?? ''));
            $cleanDesc = trim(preg_replace('/\s+/', ' ', (string) $rawDesc));

            if (mb_strlen($cleanDesc) > 30) {
                $metaDescription = Str::limit($cleanDesc, 155);
            } else {
                $stockText = $inStock ? 'In stock and ready to ship' : 'Order online';
                $brandText = $brandName ? " from {$brandName}" : '';
                $catText = $categoryName ? " in {$categoryName}" : '';
                $metaDescription = "Buy {$title}{$brandText}{$catText} at {$siteName}. ৳".number_format($price).". {$stockText} across Bangladesh.";
            }
        }

        // Canonical URL
        $productUrl = route('products.show', ['slug' => $slug]);
        $customCanonical = $seoMeta?->canonical_url ?: ($isModel ? $product->canonical_url : ($product['canonicalUrl'] ?? null));
        $canonicalUrl = $customCanonical ?: $productUrl;

        // Image resolution
        $images = [];
        if ($isModel) {
            $images = is_array($product->images) ? $product->images : (json_decode($product->images ?? '[]', true) ?: []);
        } else {
            $images = is_array($product['images'] ?? null) ? $product['images'] : [];
        }

        $ogImage = $seoMeta?->og_image ?: ($isModel ? $product->og_image : ($product['ogImage'] ?? null));
        if (! $ogImage && ! empty($images[0])) {
            $ogImage = $images[0];
        }
        if (! $ogImage) {
            $ogImage = $business['logo_url'] ?? '/brandlogo.png';
        }
        if (! Str::startsWith($ogImage, ['http://', 'https://'])) {
            $ogImage = url($ogImage);
        }

        // Keywords
        $keywords = $seoMeta?->seo_keywords ?: ($isModel ? $product->seo_keywords : ($product['seoKeywords'] ?? null));
        if (! $keywords) {
            $kwList = array_filter([$title, $brandName, $categoryName, 'online shopping', 'Bangladesh']);
            $keywords = implode(', ', $kwList);
        }

        // Status & robots
        $status = $isModel ? $product->status : ($product['status'] ?? 'active');
        $productStatus = $isModel ? $product->product_status : ($product['product_status'] ?? 'published');
        $robots = ($status === 'active' && $productStatus === 'published')
            ? ($seoMeta?->robots ?: 'index, follow')
            : 'noindex, nofollow';

        // Reviews & Rating for Schema (genuine only)
        $ratingAverage = (float) ($isModel ? 0 : ($product['rating'] ?? 0));
        $reviewCount = (int) ($isModel ? 0 : ($product['reviewCount'] ?? 0));

        if ($isModel) {
            $approvedReviews = Review::where('product_id', $id)->where('status', 'approved');
            $reviewCount = $approvedReviews->count();
            $ratingAverage = $reviewCount > 0 ? (float) $approvedReviews->avg('rating') : 0;
        }

        // Build Schema.org JSON-LD for Product
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $title,
            'description' => $metaDescription,
            'url' => $canonicalUrl,
        ];

        if ($sku) {
            $schema['sku'] = $sku;
        }

        if (! empty($images)) {
            $schema['image'] = array_map(function ($img) {
                return Str::startsWith($img, ['http://', 'https://']) ? $img : url($img);
            }, array_slice($images, 0, 5));
        } else {
            $schema['image'] = [$ogImage];
        }

        if ($brandName) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $brandName,
            ];
        }

        $schema['offers'] = [
            '@type' => 'Offer',
            'url' => $canonicalUrl,
            'priceCurrency' => 'BDT',
            'price' => number_format($price, 2, '.', ''),
            'priceValidUntil' => now()->addYear()->format('Y-12-31'),
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => $siteName,
            ],
        ];

        // Genuine reviews only
        if ($reviewCount > 0 && $ratingAverage > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($ratingAverage, 1, '.', ''),
                'reviewCount' => $reviewCount,
                'bestRating' => '5',
                'worstRating' => '1',
            ];
        }

        // BreadcrumbList Schema
        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => url('/'),
            ],
        ];
        $pos = 2;
        if ($categoryName && $categorySlug) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $categoryName,
                'item' => route('shop', ['category' => $categorySlug]),
            ];
        }
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => $pos,
            'name' => $title,
            'item' => $canonicalUrl,
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];

        return [
            'title' => $metaTitle,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $keywords,
            'canonical_url' => $canonicalUrl,
            'robots' => $robots,
            'og_type' => 'product',
            'og_title' => $metaTitle,
            'og_description' => $metaDescription,
            'og_image' => $ogImage,
            'og_url' => $canonicalUrl,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $metaTitle,
            'twitter_description' => $metaDescription,
            'twitter_image' => $ogImage,
            'schemas' => [$schema, $breadcrumbSchema],
        ];
    }

    /**
     * Get SEO metadata for Shop, Category, Brand, and Filtered listing pages.
     */
    public static function getForShop(
        Request $request,
        ?Category $selectedCategory = null,
        ?Brand $selectedBrand = null,
        ?Category $selectedSubcategory = null
    ): array {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';
        $search = trim((string) $request->input('search', $request->input('q', '')));

        // Check if faceted or temporary filters are present that should NOT be indexed
        $hasFacetFilters = $request->filled('minPrice')
            || $request->filled('maxPrice')
            || $request->filled('min_price')
            || $request->filled('max_price')
            || $request->filled('color')
            || $request->filled('size')
            || $request->filled('availability')
            || $request->filled('stock')
            || $request->filled('sale')
            || $request->filled('rating')
            || ($request->filled('sort') && ! in_array($request->input('sort'), ['newest', 'default'], true))
            || $request->filled('view');

        $isSearch = $search !== '';
        $page = (int) $request->input('page', 1);

        // Determine base entity and canonical target
        if ($isSearch) {
            $metaTitle = "Search: {$search} | {$siteName}";
            $metaDescription = "Search results for \"{$search}\" on {$siteName}. Find top quality groceries, lifestyle items, and electronics.";
            $canonicalUrl = route('shop');
            $robots = 'noindex, follow'; // Prevent search results index bloat
        } elseif ($selectedCategory && $selectedBrand) {
            $metaTitle = "{$selectedCategory->name} by {$selectedBrand->name} | {$siteName}";
            $metaDescription = "Explore {$selectedCategory->name} by {$selectedBrand->name} at {$siteName}. High quality and genuine products.";
            $canonicalUrl = route('shop', ['category' => $selectedCategory->slug, 'brand' => $selectedBrand->slug]);
            $robots = $hasFacetFilters ? 'noindex, follow' : 'index, follow';
        } elseif ($selectedSubcategory) {
            $metaTitle = "{$selectedSubcategory->name} - Buy Online | {$siteName}";
            $metaDescription = "Shop {$selectedSubcategory->name} online at {$siteName}. Best prices, trusted quality, and fast home delivery.";
            $canonicalUrl = route('shop', ['category' => $selectedSubcategory->slug]);
            $robots = $hasFacetFilters ? 'noindex, follow' : 'index, follow';
        } elseif ($selectedCategory) {
            $catSeo = SeoMeta::where('model_type', Category::class)->where('model_id', $selectedCategory->id)->first();
            $metaTitle = $catSeo?->seo_title
                ?: ($selectedCategory->seo_title ?: "{$selectedCategory->name} - Buy Online at {$siteName}");
            $metaDescription = $catSeo?->seo_description
                ?: ($selectedCategory->seo_description ?: "Shop {$selectedCategory->name} at {$siteName}. Discover genuine products with fast doorstep delivery across Bangladesh.");
            $canonicalUrl = $catSeo?->canonical_url ?: route('shop', ['category' => $selectedCategory->slug]);
            $robots = $hasFacetFilters ? 'noindex, follow' : ($catSeo?->robots ?: 'index, follow');
        } elseif ($selectedBrand) {
            $brandSeo = SeoMeta::where('model_type', Brand::class)->where('model_id', $selectedBrand->id)->first();
            $metaTitle = $brandSeo?->seo_title
                ?: ($selectedBrand->seo_title ?: "{$selectedBrand->name} Products - {$siteName}");
            $metaDescription = $brandSeo?->seo_description
                ?: ($selectedBrand->seo_description ?: "Shop genuine {$selectedBrand->name} products at {$siteName}. 100% authentic with manufacturer warranty.");
            $canonicalUrl = $brandSeo?->canonical_url ?: route('shop', ['brand' => $selectedBrand->slug]);
            $robots = $hasFacetFilters ? 'noindex, follow' : ($brandSeo?->robots ?: 'index, follow');
        } else {
            $metaTitle = "Shop All Products | {$siteName}";
            $metaDescription = "Browse all products at {$siteName}. Daily essentials, groceries, fashion, and electronics with express delivery.";
            $canonicalUrl = route('shop');
            $robots = $hasFacetFilters ? 'noindex, follow' : 'index, follow';
        }

        // If paginated page > 1, adjust title for crawlers
        if ($page > 1) {
            $metaTitle .= " (Page {$page})";
        }

        $ogImage = url($business['logo_url'] ?? '/brandlogo.png');
        if ($selectedCategory?->image) {
            $ogImage = Str::startsWith($selectedCategory->image, ['http://', 'https://'])
                ? $selectedCategory->image
                : url($selectedCategory->image);
        } elseif ($selectedBrand?->logo) {
            $ogImage = Str::startsWith($selectedBrand->logo, ['http://', 'https://'])
                ? $selectedBrand->logo
                : url($selectedBrand->logo);
        }

        // Build BreadcrumbList Schema
        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Shop',
                'item' => route('shop'),
            ],
        ];

        if ($selectedCategory) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $selectedCategory->name,
                'item' => route('shop', ['category' => $selectedCategory->slug]),
            ];
        } elseif ($selectedBrand) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $selectedBrand->name,
                'item' => route('shop', ['brand' => $selectedBrand->slug]),
            ];
        }

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];

        // CollectionPage Schema
        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $metaTitle,
            'description' => $metaDescription,
            'url' => $canonicalUrl,
        ];

        return [
            'title' => $metaTitle,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => 'shop, buy online, grocery, bangladesh, mama bazar',
            'canonical_url' => $canonicalUrl,
            'robots' => $robots,
            'og_type' => 'website',
            'og_title' => $metaTitle,
            'og_description' => $metaDescription,
            'og_image' => $ogImage,
            'og_url' => $canonicalUrl,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $metaTitle,
            'twitter_description' => $metaDescription,
            'twitter_image' => $ogImage,
            'schemas' => [$collectionSchema, $breadcrumbSchema],
        ];
    }

    /**
     * Get SEO metadata for Homepage.
     */
    public static function getForHome(): array
    {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';
        $tagline = $business['tagline'] ?? 'Online Grocery & Lifestyle Essentials';
        $description = $business['business_description']
            ?? 'Mama Bazar is your trusted daily online grocery, lifestyle and essentials store. Quality products delivered across Bangladesh.';
        $canonicalUrl = url('/');
        $logoUrl = url($business['logo_url'] ?? '/brandlogo.png');

        // Check custom SEO meta for home
        $seoMeta = SeoMeta::where('route_name', 'home')->first();
        if ($seoMeta) {
            $title = $seoMeta->seo_title ?: "{$siteName} - {$tagline}";
            $metaDescription = $seoMeta->seo_description ?: $description;
            $canonicalUrl = $seoMeta->canonical_url ?: $canonicalUrl;
            $robots = $seoMeta->robots ?: 'index, follow';
            $ogImage = $seoMeta->og_image ? url($seoMeta->og_image) : $logoUrl;
        } else {
            $title = "{$siteName} - {$tagline}";
            $metaDescription = $description;
            $robots = 'index, follow';
            $ogImage = $logoUrl;
        }

        // Organization Structured Data
        $socialLinks = [];
        if (! empty($business['facebook_url'])) {
            $socialLinks[] = $business['facebook_url'];
        }
        if (! empty($business['instagram_url'])) {
            $socialLinks[] = $business['instagram_url'];
        }

        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => $logoUrl,
            'description' => $metaDescription,
            'telephone' => $business['primary_phone'] ?? '01943124216',
            'email' => $business['primary_email'] ?? 'support@mama-bazar.com',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $business['address_line1'] ?? 'House 12, Road 4, Sector 3, Uttara',
                'addressLocality' => $business['city'] ?? 'Dhaka',
                'postalCode' => $business['postal_code'] ?? '1230',
                'addressCountry' => 'BD',
            ],
            'sameAs' => $socialLinks,
            'knowsAbout' => [
                'Online Grocery Bangladesh',
                'Daily Essentials Delivery',
                'E-commerce Platform',
            ],
        ];

        // WebSite Structured Data with SearchAction & Developer Creator Attribution
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => url('/'),
            'creator' => [
                '@type' => 'Organization',
                'name' => 'Bornosoft',
                'url' => 'https://bornosoft.bd',
                'description' => 'Software development and digital solutions company in Bangladesh specializing in custom e-commerce platforms and web applications.',
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/shop').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return [
            'title' => $title,
            'meta_title' => $title,
            'meta_description' => $metaDescription,
            'meta_keywords' => 'online grocery, ecommerce bangladesh, mama bazar, daily essentials, fresh food',
            'canonical_url' => $canonicalUrl,
            'robots' => $robots,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $metaDescription,
            'og_image' => $ogImage,
            'og_url' => $canonicalUrl,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $title,
            'twitter_description' => $metaDescription,
            'twitter_image' => $ogImage,
            'schemas' => [$organizationSchema, $websiteSchema],
        ];
    }

    /**
     * Get SEO metadata for About Us page with AboutPage schema & creator entity.
     */
    public static function getForAbout(): array
    {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';
        $fullTitle = "About Us | {$siteName}";
        $desc = "Learn about {$siteName}, your trusted everyday online grocery and essentials marketplace in Bangladesh, engineered with high-performance technology by Bornosoft.";
        $canon = route('about');
        $ogImage = url($business['logo_url'] ?? '/brandlogo.png');

        $breadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'About Us', 'item' => $canon],
            ],
        ];

        $aboutPageSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            'name' => $fullTitle,
            'description' => $desc,
            'url' => $canon,
            'mainEntity' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => url('/'),
                'description' => $desc,
                'creator' => [
                    '@type' => 'Organization',
                    'name' => 'Bornosoft',
                    'url' => 'https://bornosoft.bd',
                    'sameAs' => ['https://bornosoft.bd'],
                    'description' => 'Software engineering and web application development company in Bangladesh that architected and developed Mama Bazar.',
                ],
            ],
        ];

        return [
            'title' => $fullTitle,
            'meta_title' => $fullTitle,
            'meta_description' => $desc,
            'meta_keywords' => 'about mama bazar, who developed mama bazar, bornosoft, online grocery bangladesh',
            'canonical_url' => $canon,
            'robots' => 'index, follow',
            'og_type' => 'website',
            'og_title' => $fullTitle,
            'og_description' => $desc,
            'og_image' => $ogImage,
            'og_url' => $canon,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $fullTitle,
            'twitter_description' => $desc,
            'twitter_image' => $ogImage,
            'schemas' => [$aboutPageSchema, $breadcrumbs],
        ];
    }

    /**
     * Get SEO metadata for Team page with AboutPage / Team schema.
     */
    public static function getForTeam(): array
    {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';
        $fullTitle = "Our Leadership & Team | {$siteName}";
        $desc = "Meet the passionate leadership, software engineers, designers, and operations specialists powering {$siteName} across Bangladesh.";
        $canon = route('team');
        $ogImage = url($business['logo_url'] ?? '/brandlogo.png');

        $breadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Our Team', 'item' => $canon],
            ],
        ];

        return [
            'title' => $fullTitle,
            'meta_title' => $fullTitle,
            'meta_description' => $desc,
            'meta_keywords' => 'mama bazar team, leadership, executives, software engineers, management team, bangladesh e-commerce',
            'canonical_url' => $canon,
            'robots' => 'index, follow',
            'og_type' => 'website',
            'og_title' => $fullTitle,
            'og_description' => $desc,
            'og_image' => $ogImage,
            'og_url' => $canon,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $fullTitle,
            'twitter_description' => $desc,
            'twitter_image' => $ogImage,
            'schemas' => [$breadcrumbs],
        ];
    }

    /**
     * Get SEO metadata for FAQ page with FAQPage schema.
     */
    public static function getForFaq(): array
    {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';
        $fullTitle = "Frequently Asked Questions (FAQ) | {$siteName}";
        $desc = "Find answers to common questions about orders, payments, delivery, returns, and the technology behind {$siteName}.";
        $canon = route('faq');
        $ogImage = url($business['logo_url'] ?? '/brandlogo.png');

        $breadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'FAQ', 'item' => $canon],
            ],
        ];

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'How do I place an order?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Browse our categories or search for desired products. Click "Add to Cart", then proceed to Checkout to enter your delivery address and choose your payment method.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Is Cash on Delivery available?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Yes, we offer Cash on Delivery (COD) across all service areas in Bangladesh. You inspect your package upon delivery and pay the courier directly.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'How can I track my order?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Visit the Track Order page and enter either your Order ID (BS-XXXXXX) or the phone number you used during checkout.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is the return and refund policy?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'If an item is damaged or defective upon arrival, notify us within 7 days for a replacement or full refund.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Who developed and maintains the Mama Bazar platform?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Mama Bazar was engineered and is actively maintained by Bornosoft (https://bornosoft.bd), a software development and digital transformation company based in Bangladesh specializing in custom e-commerce platforms and scalable web applications.',
                    ],
                ],
            ],
        ];

        return [
            'title' => $fullTitle,
            'meta_title' => $fullTitle,
            'meta_description' => $desc,
            'meta_keywords' => 'mama bazar faq, mama bazar developer, bornosoft, order delivery bangladesh',
            'canonical_url' => $canon,
            'robots' => 'index, follow',
            'og_type' => 'website',
            'og_title' => $fullTitle,
            'og_description' => $desc,
            'og_image' => $ogImage,
            'og_url' => $canon,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $fullTitle,
            'twitter_description' => $desc,
            'twitter_image' => $ogImage,
            'schemas' => [$faqSchema, $breadcrumbs],
        ];
    }

    /**
     * Get SEO metadata for generic public content pages (About, FAQ, Contact, Policies).
     */
    public static function getForPage(
        string $title,
        ?string $description = null,
        ?string $canonical = null,
        ?string $robots = 'index, follow',
        array $breadcrumbs = []
    ): array {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';
        $fullTitle = "{$title} | {$siteName}";
        $desc = $description ?: "Learn more about {$title} at {$siteName}.";
        $canon = $canonical ?: url()->current();
        $ogImage = url($business['logo_url'] ?? '/brandlogo.png');

        $schemas = [];
        if (! empty($breadcrumbs)) {
            $items = [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url('/'),
                ],
            ];
            $pos = 2;
            foreach ($breadcrumbs as $bName => $bUrl) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $pos++,
                    'name' => $bName,
                    'item' => $bUrl,
                ];
            }
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $items,
            ];
        }

        return [
            'title' => $fullTitle,
            'meta_title' => $fullTitle,
            'meta_description' => $desc,
            'meta_keywords' => strtolower($title).', '.$siteName,
            'canonical_url' => $canon,
            'robots' => $robots ?: 'index, follow',
            'og_type' => 'article',
            'og_title' => $fullTitle,
            'og_description' => $desc,
            'og_image' => $ogImage,
            'og_url' => $canon,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $fullTitle,
            'twitter_description' => $desc,
            'twitter_image' => $ogImage,
            'schemas' => $schemas,
        ];
    }

    /**
     * Get SEO metadata for private/administrative/account pages (MUST NEVER BE INDEXED).
     */
    public static function getForPrivate(string $title): array
    {
        $business = app(BusinessSettingService::class)->all();
        $siteName = $business['site_name'] ?? 'Mama Bazar';

        return [
            'title' => "{$title} | {$siteName}",
            'meta_title' => "{$title} | {$siteName}",
            'meta_description' => '',
            'meta_keywords' => '',
            'canonical_url' => '',
            'robots' => 'noindex, nofollow',
            'og_type' => 'website',
            'og_title' => "{$title} | {$siteName}",
            'og_description' => '',
            'og_image' => url('/brandlogo.png'),
            'og_url' => '',
            'twitter_card' => 'summary',
            'twitter_title' => "{$title} | {$siteName}",
            'twitter_description' => '',
            'twitter_image' => url('/brandlogo.png'),
            'schemas' => [],
        ];
    }

    /**
     * Perform catalog SEO audit.
     */
    public static function auditCatalog(): array
    {
        $productsTotal = Product::where('status', 'active')->count();
        $productsMissingTitle = Product::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('seo_title')->orWhere('seo_title', ''))
            ->count();
        $productsMissingDescription = Product::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('seo_description')->orWhere('seo_description', ''))
            ->count();
        $productsMissingImages = Product::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('images')->orWhere('images', '[]')->orWhere('images', ''))
            ->count();

        // Duplicate title detection
        $duplicateProductTitles = Product::where('status', 'active')
            ->selectRaw('title, COUNT(*) as count')
            ->groupBy('title')
            ->having('count', '>', 1)
            ->pluck('title')
            ->all();

        // Categories audit
        $categoriesTotal = Category::where('status', 'active')->count();
        $categoriesMissingDesc = Category::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', ''))
            ->count();
        $categoriesMissingImage = Category::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('image')->orWhere('image', ''))
            ->count();

        // Brands audit
        $brandsTotal = Brand::where('status', 'active')->count();
        $brandsMissingLogo = Brand::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('logo')->orWhere('logo', ''))
            ->count();

        // Health Score calculation (0 - 100%)
        $totalChecks = ($productsTotal * 3) + ($categoriesTotal * 2) + $brandsTotal;
        $totalIssues = $productsMissingTitle + $productsMissingDescription + $productsMissingImages
            + $categoriesMissingDesc + $categoriesMissingImage + $brandsMissingLogo;

        $healthScore = $totalChecks > 0
            ? max(10, min(100, round((($totalChecks - $totalIssues) / $totalChecks) * 100)))
            : 100;

        return [
            'products_total' => $productsTotal,
            'products_missing_title' => $productsMissingTitle,
            'products_missing_description' => $productsMissingDescription,
            'products_missing_images' => $productsMissingImages,
            'duplicate_product_titles' => $duplicateProductTitles,
            'categories_total' => $categoriesTotal,
            'categories_missing_description' => $categoriesMissingDesc,
            'categories_missing_image' => $categoriesMissingImage,
            'brands_total' => $brandsTotal,
            'brands_missing_logo' => $brandsMissingLogo,
            'health_score' => $healthScore,
            'last_audited_at' => now()->toDateTimeString(),
        ];
    }
}
