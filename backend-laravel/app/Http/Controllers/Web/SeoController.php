<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PolicyPage;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    /**
     * XML Sitemap Index.
     */
    public function sitemapIndex(): Response
    {
        $xml = Cache::remember('seo_sitemap_index', 3600, function () {
            $now = now()->toIso8601String();
            $baseUrl = config('app.url', url('/'));

            $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            $subSitemaps = [
                '/sitemap-products.xml',
                '/sitemap-categories.xml',
                '/sitemap-brands.xml',
                '/sitemap-pages.xml',
            ];

            foreach ($subSitemaps as $path) {
                $content .= "    <sitemap>\n";
                $content .= '        <loc>'.htmlspecialchars(rtrim($baseUrl, '/').$path, ENT_XML1, 'UTF-8')."</loc>\n";
                $content .= "        <lastmod>{$now}</lastmod>\n";
                $content .= "    </sitemap>\n";
            }

            $content .= '</sitemapindex>';

            return $content;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Products XML Sitemap.
     */
    public function sitemapProducts(): Response
    {
        $xml = Cache::remember('seo_sitemap_products', 3600, function () {
            $products = Product::where('status', 'active')
                ->where('product_status', 'published')
                ->orderBy('id', 'desc')
                ->get(['id', 'slug', 'created_at']);

            $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($products as $p) {
                $url = route('products.show', ['slug' => $p->slug]);
                $lastmod = ($p->created_at ?? now())->toIso8601String();

                $content .= "    <url>\n";
                $content .= '        <loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8')."</loc>\n";
                $content .= "        <lastmod>{$lastmod}</lastmod>\n";
                $content .= "        <changefreq>daily</changefreq>\n";
                $content .= "        <priority>0.8</priority>\n";
                $content .= "    </url>\n";
            }

            $content .= '</urlset>';

            return $content;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Categories XML Sitemap.
     */
    public function sitemapCategories(): Response
    {
        $xml = Cache::remember('seo_sitemap_categories', 3600, function () {
            $categories = Category::where('status', 'active')
                ->orderBy('sort_order', 'asc')
                ->get(['id', 'slug', 'created_at']);

            $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($categories as $cat) {
                $url = route('shop', ['category' => $cat->slug]);
                $lastmod = ($cat->created_at ?? now())->toIso8601String();

                $content .= "    <url>\n";
                $content .= '        <loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8')."</loc>\n";
                $content .= "        <lastmod>{$lastmod}</lastmod>\n";
                $content .= "        <changefreq>weekly</changefreq>\n";
                $content .= "        <priority>0.7</priority>\n";
                $content .= "    </url>\n";
            }

            $content .= '</urlset>';

            return $content;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Brands XML Sitemap.
     */
    public function sitemapBrands(): Response
    {
        $xml = Cache::remember('seo_sitemap_brands', 3600, function () {
            $brands = Brand::where('status', 'active')
                ->has('products')
                ->get(['id', 'slug', 'created_at']);

            $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($brands as $brand) {
                $url = route('shop', ['brand' => $brand->slug]);
                $lastmod = ($brand->created_at ?? now())->toIso8601String();

                $content .= "    <url>\n";
                $content .= '        <loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8')."</loc>\n";
                $content .= "        <lastmod>{$lastmod}</lastmod>\n";
                $content .= "        <changefreq>weekly</changefreq>\n";
                $content .= "        <priority>0.6</priority>\n";
                $content .= "    </url>\n";
            }

            $content .= '</urlset>';

            return $content;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Public Pages and Home XML Sitemap.
     */
    public function sitemapPages(): Response
    {
        $xml = Cache::remember('seo_sitemap_pages', 3600, function () {
            $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            $staticPages = [
                ['loc' => url('/'), 'priority' => '1.0', 'freq' => 'daily'],
                ['loc' => route('shop'), 'priority' => '0.9', 'freq' => 'daily'],
                ['loc' => route('about'), 'priority' => '0.8', 'freq' => 'weekly'],
                ['loc' => route('faq'), 'priority' => '0.8', 'freq' => 'weekly'],
                ['loc' => route('contact'), 'priority' => '0.6', 'freq' => 'monthly'],
            ];

            foreach ($staticPages as $sp) {
                $content .= "    <url>\n";
                $content .= '        <loc>'.htmlspecialchars($sp['loc'], ENT_XML1, 'UTF-8')."</loc>\n";
                $content .= '        <lastmod>'.now()->toIso8601String()."</lastmod>\n";
                $content .= "        <changefreq>{$sp['freq']}</changefreq>\n";
                $content .= "        <priority>{$sp['priority']}</priority>\n";
                $content .= "    </url>\n";
            }

            // Public policy pages
            $policies = PolicyPage::where('status', 'published')
                ->orWhereNull('status')
                ->get(['slug', 'created_at']);

            foreach ($policies as $pol) {
                $url = url('/pages/'.$pol->slug);
                $lastmod = ($pol->created_at ?? now())->toIso8601String();

                $content .= "    <url>\n";
                $content .= '        <loc>'.htmlspecialchars($url, ENT_XML1, 'UTF-8')."</loc>\n";
                $content .= "        <lastmod>{$lastmod}</lastmod>\n";
                $content .= "        <changefreq>monthly</changefreq>\n";
                $content .= "        <priority>0.4</priority>\n";
                $content .= "    </url>\n";
            }

            $content .= '</urlset>';

            return $content;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Dynamic robots.txt route (if served through Laravel).
     */
    public function robots(): Response
    {
        $baseUrl = config('app.url', url('/'));
        $sitemapUrl = rtrim($baseUrl, '/').'/sitemap.xml';

        $text = "User-agent: *\n";
        $text .= "Allow: /\n";
        $text .= "Allow: /shop\n";
        $text .= "Allow: /products/\n";
        $text .= "Allow: /about\n";
        $text .= "Allow: /contact\n";
        $text .= "Allow: /faq\n";
        $text .= "Allow: /pages/\n";
        $text .= "\n";
        $text .= "# Private & Administrative areas\n";
        $text .= "Disallow: /admin/\n";
        $text .= "Disallow: /account/\n";
        $text .= "Disallow: /cart\n";
        $text .= "Disallow: /checkout\n";
        $text .= "Disallow: /order/success\n";
        $text .= "Disallow: /login\n";
        $text .= "Disallow: /register\n";
        $text .= "Disallow: /forgot-password\n";
        $text .= "Disallow: /reset-password\n";
        $text .= "Disallow: /verify-email\n";
        $text .= "Disallow: /email/\n";
        $text .= "Disallow: /invoice/\n";
        $text .= "Disallow: /api/\n";
        $text .= "Disallow: /storage/temp/\n";
        $text .= "\n";
        $text .= "Sitemap: {$sitemapUrl}\n";

        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Invalidate all sitemap caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('seo_sitemap_index');
        Cache::forget('seo_sitemap_products');
        Cache::forget('seo_sitemap_categories');
        Cache::forget('seo_sitemap_brands');
        Cache::forget('seo_sitemap_pages');
    }
}
