<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTagSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected Brand $brand;

    protected Product $fairyLightProduct;

    protected Product $crystalLampProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Lighting & Home Decor',
            'slug' => 'lighting-home-decor',
            'status' => 'active',
        ]);

        $this->brand = Brand::create([
            'name' => 'MamaBazar Home',
            'slug' => 'mamabazar-home',
            'status' => 'active',
        ]);

        // Product with multi-phrase tags and Bangla + English keywords
        $this->fairyLightProduct = Product::create([
            'title' => 'নান্দনিক রেড অ্যান্ড হোয়াইট রোজ ফেয়ারি লাইট - হোম ও রুম ডেকর স্ট্রিং লাইট',
            'slug' => 'red-white-rose-flower-fairy-string-lights',
            'price' => 450,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock' => 15,
            'status' => 'active',
            'product_status' => 'published',
            'tags' => [
                'রোজ ফেয়ারি লাইট, গোলাপ ফুলের লাইট, লাল সাদা গোলাপ ফেয়ারি লাইট, rose fairy light, red white rose string light, artificial rose flower fairy light',
                'rose fairy light',
                'artificial rose flower fairy light',
                'রোজ ফেয়ারি লাইট',
                'গোলাপ ফুলের লাইট,',
            ],
            'seo_keywords' => 'rose fairy light, romantic rose lights price in bangladesh, wedding decor flower lights',
        ]);

        // Another product with different tags
        $this->crystalLampProduct = Product::create([
            'title' => 'লাক্সারি ক্রিস্টাল ডোম টেবিল ল্যাম্প',
            'slug' => 'luxury-crystal-dome-table-lamp',
            'price' => 1250,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock' => 8,
            'status' => 'active',
            'product_status' => 'published',
            'tags' => [
                'ক্রিস্টাল টেবিল ল্যাম্প',
                'লাক্সারি নাইট ল্যাম্প',
                'crystal table lamp',
            ],
            'seo_keywords' => 'crystal table lamp, bedside luxury light',
        ]);
    }

    public function test_tag_normalization_splits_combined_seo_keywords_into_discrete_tags(): void
    {
        $rawTags = [
            'রোজ ফেয়ারি লাইট, গোলাপ ফুলের লাইট, লাল সাদা গোলাপ ফেয়ারি লাইট, rose fairy light, red white rose string light',
            'rose fairy light',
            '#FairyLights #RoomDecorBD',
            'গোলাপ ফুলের লাইট, ।',
        ];

        $normalized = ProductService::normalizeTags($rawTags);

        $this->assertContains('রোজ ফেয়ারি লাইট', $normalized);
        $this->assertContains('গোলাপ ফুলের লাইট', $normalized);
        $this->assertContains('লাল সাদা গোলাপ ফেয়ারি লাইট', $normalized);
        $this->assertContains('rose fairy light', $normalized);
        $this->assertContains('red white rose string light', $normalized);
        $this->assertContains('FairyLights', $normalized);
        $this->assertContains('RoomDecorBD', $normalized);
        // Ensure commas, danda, and hash marks were stripped cleanly without corrupting UTF-8
        $this->assertNotContains('গোলাপ ফুলের লাইট, ।', $normalized);
    }

    public function test_clicking_bangla_tag_returns_matching_products(): void
    {
        $response = $this->get(route('shop', ['tag' => 'রোজ ফেয়ারি লাইট']));

        $response->assertStatus(200);
        $response->assertSee($this->fairyLightProduct->title);
        $response->assertDontSee($this->crystalLampProduct->title);
        $response->assertSee('Tag: রোজ ফেয়ারি লাইট');
    }

    public function test_clicking_english_tag_returns_matching_products(): void
    {
        $response = $this->get(route('shop', ['tag' => 'rose fairy light']));

        $response->assertStatus(200);
        $response->assertSee($this->fairyLightProduct->title);
        $response->assertDontSee($this->crystalLampProduct->title);
        $response->assertSee('Tag: rose fairy light');
    }

    public function test_clicking_tag_with_spaces_and_punctuation_works_correctly(): void
    {
        $response = $this->get(route('shop', ['tag' => 'গোলাপ ফুলের লাইট']));

        $response->assertStatus(200);
        $response->assertSee($this->fairyLightProduct->title);
    }

    public function test_clicking_one_tag_does_not_search_the_entire_seo_keyword_list(): void
    {
        // When user visits product details page, tags are rendered as separate discrete links
        $response = $this->get(route('products.show', $this->fairyLightProduct->slug));

        $response->assertStatus(200);
        // Ensure individual tags are linked separately
        $response->assertSee(route('shop', ['tag' => 'rose fairy light']));
        $response->assertSee(route('shop', ['tag' => 'রোজ ফেয়ারি লাইট']));
    }

    public function test_matching_products_are_returned_even_when_unrelated_seo_keywords_are_present(): void
    {
        $response = $this->get(route('shop', ['tag' => 'crystal table lamp']));

        $response->assertStatus(200);
        $response->assertSee($this->crystalLampProduct->title);
        $response->assertDontSee($this->fairyLightProduct->title);
    }

    public function test_tag_with_no_matching_products_displays_accurate_empty_state(): void
    {
        $response = $this->get(route('shop', ['tag' => 'nonexistenttagxyz']));

        $response->assertStatus(200);
        $response->assertSee('No products found');
        $response->assertSee('Showing 0–0 of 0 products');
    }

    public function test_clear_all_restores_normal_product_listing(): void
    {
        // Visiting /shop with no filters shows all products
        $response = $this->get(route('shop'));

        $response->assertStatus(200);
        $response->assertSee($this->fairyLightProduct->title);
        $response->assertSee($this->crystalLampProduct->title);
    }

    public function test_refreshing_page_preserves_selected_tag(): void
    {
        $url = route('shop', ['tag' => 'rose fairy light']);
        $response1 = $this->get($url);
        $response2 = $this->get($url);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response2->assertSee($this->fairyLightProduct->title);
        $response2->assertSee('Tag: rose fairy light');
    }

    public function test_category_and_brand_filters_work_together_with_tag_filters(): void
    {
        $response = $this->get(route('shop', [
            'category' => $this->category->slug,
            'brand' => $this->brand->slug,
            'tag' => 'rose fairy light',
        ]));

        $response->assertStatus(200);
        $response->assertSee($this->fairyLightProduct->title);
        $response->assertDontSee($this->crystalLampProduct->title);
    }

    public function test_comma_separated_search_query_splits_and_matches_relevant_products(): void
    {
        // Legacy combined query from SEO keyword string
        $longQuery = 'রোজ ফেয়ারি লাইট, গোলাপ ফুলের লাইট, rose fairy light, artificial rose flower fairy light';

        $response = $this->get(route('shop', ['q' => $longQuery]));

        $response->assertStatus(200);
        $response->assertSee($this->fairyLightProduct->title);
    }

    public function test_general_text_search_continues_to_work(): void
    {
        $response = $this->get(route('shop', ['search' => 'ক্রিস্টাল']));

        $response->assertStatus(200);
        $response->assertSee($this->crystalLampProduct->title);
    }
}
