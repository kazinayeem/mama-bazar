<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDescriptionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('custom_role', 'SUPER_ADMIN')->first()
            ?? User::where('role', 'admin')->first();

        $this->category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);
    }

    public function test_product_create_strips_xss_from_description(): void
    {
        $payload = [
            'title' => 'XSS Probe Phone',
            'slug' => 'xss-probe-phone',
            'price' => 1000,
            'category_id' => $this->category->id,
            'description' => '<p>Safe</p><script>alert(1)</script><img src=x onerror=alert(1)>'
                .'<a href="javascript:alert(1)">bad</a><iframe src="https://evil.test"></iframe>',
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 5,
        ];

        $product = ProductService::create($payload);

        $this->assertStringNotContainsString('<script', strtolower($product['description'] ?? ''));
        $this->assertStringNotContainsString('onerror', strtolower($product['description'] ?? ''));
        $this->assertStringNotContainsString('javascript:', strtolower($product['description'] ?? ''));
        $this->assertStringNotContainsString('iframe', strtolower($product['description'] ?? ''));
        $this->assertStringContainsString('Safe', $product['description'] ?? '');
    }

    public function test_storefront_renders_sanitized_html_not_raw_xss(): void
    {
        $product = Product::create([
            'title' => 'Safe HTML Product',
            'slug' => 'safe-html-product',
            'price' => 2500,
            'category_id' => $this->category->id,
            'description' => '<p>Hello <strong>world</strong></p><script>alert(1)</script>',
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 3,
        ]);

        // Re-save through service so description is purified as on write path
        ProductService::update($product->id, [
            'description' => '<h2>Features</h2><p>Battery life</p><script>alert(document.cookie)</script>',
        ]);

        $response = $this->get('/products/'.$product->slug);
        $response->assertStatus(200);
        $response->assertSee('Features', false);
        $response->assertSee('Battery life', false);
        $response->assertDontSee('<script>', false);
        $response->assertDontSee('document.cookie', false);
    }

    public function test_plain_text_description_is_wrapped_not_executed_as_html(): void
    {
        $product = ProductService::create([
            'title' => 'Plain Text Product',
            'slug' => 'plain-text-product',
            'price' => 500,
            'category_id' => $this->category->id,
            'description' => "Simple line\n<script>alert(1)</script>",
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 1,
        ]);

        $this->assertStringStartsWith('<p>', $product['description'] ?? '');
        $this->assertStringContainsString('&lt;script&gt;', $product['description'] ?? '');
    }

    public function test_editor_image_upload_accepts_only_local_jpg_png_webp(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('desc.webp', 100, 100);

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.products.upload-editor-image'),
            ['file' => $file, 'alt' => 'Product detail']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertStringStartsWith('/storage/', $response->json('url'));
        $this->assertStringContainsString('products/descriptions/', $response->json('url'));
        $response->assertJsonPath('alt', 'Product detail');
    }

    public function test_editor_image_upload_rejects_gif(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('anim.gif', 40, 40);

        $response = $this->actingAs($this->admin)->postJson(
            route('admin.products.upload-editor-image'),
            ['file' => $file]
        );

        $this->assertTrue(in_array($response->status(), [400, 422], true));
    }
}
