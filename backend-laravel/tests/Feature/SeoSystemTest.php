<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')
            ->where('custom_role', 'SUPER_ADMIN')
            ->first();
    }

    public function test_homepage_renders_complete_seo_tags_and_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="index, follow">', false);
        $response->assertSee('<meta property="og:type" content="website">', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('https://schema.org', false);
        $response->assertSee('"@type": "Organization"', false);
        $response->assertSee('"@type": "WebSite"', false);
        $response->assertSee('"@type": "SearchAction"', false);
    }

    public function test_product_detail_page_renders_dynamic_seo_and_product_schema(): void
    {
        $category = Category::create([
            'name' => 'Organic Groceries',
            'slug' => 'organic-groceries',
            'status' => 'active',
        ]);

        $brand = Brand::create([
            'name' => 'Green Fields',
            'slug' => 'green-fields',
            'status' => 'active',
        ]);

        $product = Product::create([
            'title' => 'Pure Mustard Oil 1L',
            'slug' => 'pure-mustard-oil-1l',
            'description' => '<p>Cold-pressed pure mustard oil from premium seeds.</p>',
            'price' => 350.00,
            'sale_price' => 320.00,
            'stock' => 50,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'product_status' => 'published',
            'sku' => 'OIL-001',
            'images' => ['https://mama-bazar.com/images/mustard-oil.jpg'],
            'seo_title' => 'Pure Mustard Oil 1L - Green Fields | Best Price in BD',
            'seo_description' => 'Buy pure mustard oil 1L by Green Fields at best price in Bangladesh.',
        ]);

        $response = $this->get(route('products.show', ['slug' => $product->slug]));

        $response->assertStatus(200);
        $response->assertSee('<title>Pure Mustard Oil 1L - Green Fields | Best Price in BD</title>', false);
        $response->assertSee('<meta name="description" content="Buy pure mustard oil 1L by Green Fields at best price in Bangladesh.">', false);
        $response->assertSee('<link rel="canonical" href="'.route('products.show', ['slug' => $product->slug]).'">', false);
        $response->assertSee('<meta property="og:type" content="product">', false);
        $response->assertSee('"@type": "Product"', false);
        $response->assertSee('"@type": "Offer"', false);
        $response->assertSee('"price": "320.00"', false);
        $response->assertSee('"priceCurrency": "BDT"', false);
        $response->assertSee('https://schema.org/InStock', false);
        $response->assertSee('"@type": "BreadcrumbList"', false);
    }

    public function test_product_schema_only_includes_reviews_when_approved_ratings_exist(): void
    {
        $product = Product::create([
            'title' => 'Unrated Basmati Rice 5kg',
            'slug' => 'unrated-basmati-rice-5kg',
            'description' => 'Premium aromatic basmati rice.',
            'price' => 750.00,
            'stock' => 20,
            'status' => 'active',
            'product_status' => 'published',
        ]);

        $response = $this->get(route('products.show', ['slug' => $product->slug]));

        $response->assertStatus(200);
        $response->assertDontSee('"@type": "AggregateRating"');

        // Now add an approved review
        $customer = User::create([
            'name' => 'Reviewer One',
            'email' => 'reviewer1@example.com',
            'phone' => '01811223344',
            'password' => bcrypt('Secret123!'),
        ]);

        Review::create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'title' => 'Excellent quality',
            'comment' => 'Very good rice grains.',
            'status' => 'approved',
        ]);

        $responseWithReview = $this->get(route('products.show', ['slug' => $product->slug]));
        $responseWithReview->assertStatus(200);
        $responseWithReview->assertSee('"@type": "AggregateRating"', false);
        $responseWithReview->assertSee('"ratingValue": "5.0"', false);
    }

    public function test_category_and_shop_handles_canonical_and_faceted_filter_noindex(): void
    {
        $category = Category::create([
            'name' => 'Fresh Dairy',
            'slug' => 'fresh-dairy',
            'status' => 'active',
            'seo_title' => 'Fresh Dairy Products Online | Mama Bazar',
        ]);

        // Clean category page should be indexable
        $responseClean = $this->get(route('shop', ['category' => $category->slug]));
        $responseClean->assertStatus(200);
        $responseClean->assertSee('<meta name="robots" content="index, follow">', false);
        $responseClean->assertSee('<link rel="canonical" href="'.route('shop', ['category' => $category->slug]).'">', false);

        // Faceted filter should have noindex, follow to prevent crawl traps
        $responseFiltered = $this->get(route('shop', ['category' => $category->slug, 'minPrice' => 50, 'maxPrice' => 300]));
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertSee('<meta name="robots" content="noindex, follow">', false);
        // Canonical should point to the clean base category without filter parameters
        $responseFiltered->assertSee('<link rel="canonical" href="'.route('shop', ['category' => $category->slug]).'">', false);

        // Internal search results should have noindex, follow
        $responseSearch = $this->get(route('shop', ['q' => 'milk']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_private_pages_are_protected_with_noindex_nofollow(): void
    {
        $cartResponse = $this->get(route('cart'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $checkoutResponse = $this->get(route('checkout'));
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $registerResponse = $this->get(route('register'));
        $registerResponse->assertStatus(200);
        $registerResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_technical_seo_robots_and_dynamic_sitemaps(): void
    {
        // Dynamic robots.txt
        $robotsResponse = $this->get(route('seo.robots'));
        $robotsResponse->assertStatus(200);
        $robotsResponse->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $robotsResponse->assertSee('User-agent: *');
        $robotsResponse->assertSee('Disallow: /admin/');
        $robotsResponse->assertSee('Disallow: /cart');
        $robotsResponse->assertSee('Disallow: /checkout');
        $robotsResponse->assertSee('Sitemap:');

        // Dynamic XML Sitemap Index
        $sitemapResponse = $this->get(route('seo.sitemap'));
        $sitemapResponse->assertStatus(200);
        $sitemapResponse->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $sitemapResponse->assertSee('<sitemapindex', false);
        $sitemapResponse->assertSee('sitemap-products.xml');
        $sitemapResponse->assertSee('sitemap-categories.xml');
        $sitemapResponse->assertSee('sitemap-brands.xml');
        $sitemapResponse->assertSee('sitemap-pages.xml');

        // Dynamic Products XML Sitemap
        $productsSitemapResponse = $this->get(route('seo.sitemap.products'));
        $productsSitemapResponse->assertStatus(200);
        $productsSitemapResponse->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $productsSitemapResponse->assertSee('<urlset', false);

        // Dynamic Pages XML Sitemap
        $pagesSitemapResponse = $this->get(route('seo.sitemap.pages'));
        $pagesSitemapResponse->assertStatus(200);
        $pagesSitemapResponse->assertSee('<urlset', false);
        $pagesSitemapResponse->assertSee('/about');
        $pagesSitemapResponse->assertSee('/contact');
    }

    public function test_category_and_brand_friendly_redirects(): void
    {
        $categoryRedirect = $this->get('/category/snacks');
        $categoryRedirect->assertStatus(301);
        $categoryRedirect->assertRedirect(route('shop', ['category' => 'snacks']));

        $brandRedirect = $this->get('/brand/nestle');
        $brandRedirect->assertStatus(301);
        $brandRedirect->assertRedirect(route('shop', ['brand' => 'nestle']));
    }

    public function test_admin_seo_dashboard_and_export_reports(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.seo.index'));
        $response->assertStatus(200);
        $response->assertSee('Catalog Health');
        $response->assertSee('Missing SEO Titles');
        $response->assertSee('Refresh Sitemap');

        // CSV export
        $csvResponse = $this->get(route('admin.seo.export-csv'));
        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('Content-Type'));

        // PDF / print report
        $pdfResponse = $this->get(route('admin.seo.export-pdf'));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertSee('Technical SEO Audit Report');
    }
}
