<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductEditAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected Brand $brand;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('custom_role', 'SUPER_ADMIN')->first() ?? User::where('role', 'admin')->first();

        $this->category = Category::create([
            'name' => 'Baby Care',
            'slug' => 'baby-care',
            'status' => 'active',
        ]);

        $this->brand = Brand::create([
            'name' => 'Pigeon',
            'slug' => 'pigeon',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'title' => 'Pigeon Feeding Bottle 240ml',
            'slug' => 'pigeon-feeding-bottle-240ml',
            'price' => 520,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'sku' => 'PGN-BTL-240',
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 50,
            'images' => [
                '/storage/products/test-image-1.svg',
                '/storage/products/test-image-2.svg',
                '/storage/products/test-image-3.svg',
            ],
        ]);
    }

    /**
     * TEST 1: Open existing product with 3 images.
     * Expected: All 3 images appear in the edit form view.
     */
    public function test_existing_product_images_load_correctly_on_edit_page(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/products/{$this->product->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('test-image-1.svg');
        $response->assertSee('test-image-2.svg');
        $response->assertSee('test-image-3.svg');
        $response->assertSee('The first image is used as the product thumbnail across the store.');
    }

    /**
     * TEST 2 & 3: Upload 1 new image and multiple images via the upload endpoint.
     * Expected: Upload returns success JSON with public storage URL.
     */
    public function test_admin_can_upload_product_images(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('new-product-photo.jpg', 600, 600);

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/products/{$this->product->id}/upload-image", [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertNotEmpty($response->json('url'));
        $this->assertStringContainsString('/storage/products/', $response->json('url'));
    }

    /**
     * TEST 4 & 5: Persist reordered images and main image changes.
     * Expected: Setting #3 as Main shifts it to index 0 and persists after update.
     */
    public function test_reordered_images_and_new_main_image_are_persisted(): void
    {
        $newOrder = [
            '/storage/products/test-image-3.svg', // Moved to Main
            '/storage/products/test-image-1.svg',
            '/storage/products/test-image-2.svg',
        ];

        $payload = [
            'title' => $this->product->title,
            'slug' => $this->product->slug,
            'price' => $this->product->price,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'sku' => $this->product->sku,
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 50,
            'images' => json_encode($newOrder),
        ];

        $response = $this->actingAs($this->admin)
            ->put("/admin/products/{$this->product->id}", $payload);

        $response->assertRedirect();

        $fresh = $this->product->fresh();
        $this->assertEquals($newOrder, $fresh->images);
        $this->assertEquals('/storage/products/test-image-3.svg', $fresh->images[0]);
    }

    /**
     * TEST 6, 7 & 8: Delete image and safe file cleanup.
     * Expected: Deleting an image removes it from DB; unreferenced file is deleted.
     */
    public function test_delete_image_endpoint_updates_product_and_checks_security(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/orphan.jpg', 'fake-image-bytes');

        // Add orphan image to product
        $this->product->images = array_merge($this->product->images, ['/storage/products/orphan.jpg']);
        $this->product->save();

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/products/{$this->product->id}/delete-image", [
                'url' => '/storage/products/orphan.jpg',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $fresh = $this->product->fresh();
        $this->assertNotContains('/storage/products/orphan.jpg', $fresh->images);
        $this->assertFalse(Storage::disk('public')->exists('products/orphan.jpg'));
    }

    /**
     * TEST 9: Security audit - user cannot delete/manipulate image belonging to another product.
     */
    public function test_user_cannot_delete_image_not_belonging_to_product(): void
    {
        $otherProduct = Product::create([
            'title' => 'Other Product',
            'slug' => 'other-product-unique',
            'price' => 100,
            'status' => 'active',
            'product_status' => 'published',
            'images' => ['/storage/products/other-image.svg'],
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/products/{$this->product->id}/delete-image", [
                'url' => '/storage/products/other-image.svg',
            ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    /**
     * TEST 10: Validation error preservation.
     * Expected: When required field is missing or price is negative, returns 302 with session errors.
     */
    public function test_invalid_fields_trigger_validation_errors_without_corrupting_images(): void
    {
        $payload = [
            'title' => '', // Missing title
            'price' => -50, // Invalid negative price
            'images' => json_encode($this->product->images),
        ];

        $response = $this->actingAs($this->admin)
            ->from("/admin/products/{$this->product->id}/edit")
            ->put("/admin/products/{$this->product->id}", $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['title', 'price']);

        // Verify database images were NOT touched or corrupted
        $fresh = $this->product->fresh();
        $this->assertCount(3, $fresh->images);
    }

    /**
     * TEST 11: Security audit - blob: and data: URLs are stripped from images array.
     */
    public function test_blob_and_data_urls_are_sanitized_and_rejected(): void
    {
        $payload = [
            'title' => $this->product->title,
            'slug' => $this->product->slug,
            'price' => $this->product->price,
            'category_id' => $this->category->id,
            'sku' => $this->product->sku,
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 50,
            'images' => json_encode([
                'blob:http://localhost:8000/12345-temporary-blob',
                'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                '/storage/products/test-image-1.svg',
            ]),
        ];

        $response = $this->actingAs($this->admin)
            ->put("/admin/products/{$this->product->id}", $payload);

        $response->assertRedirect();

        $fresh = $this->product->fresh();
        foreach ($fresh->images as $img) {
            $this->assertStringStartsNotWith('blob:', $img);
            $this->assertStringStartsNotWith('data:', $img);
        }
        $this->assertEquals(['/storage/products/test-image-1.svg'], $fresh->images);
    }

    /**
     * TEST 12: Empty string foreign keys and numerics are sanitized to null.
     */
    public function test_empty_optional_fields_are_safely_persisted_without_sql_errors(): void
    {
        $payload = [
            'title' => 'Updated Pigeon Bottle',
            'slug' => 'updated-pigeon-bottle',
            'price' => 550,
            'category_id' => '', // Empty string should become null
            'brand_id' => '',    // Empty string should become null
            'supplier_id' => '',
            'vendor_id' => '',
            'collection_id' => '',
            'discount' => '',
            'sku' => 'PGN-BTL-UPD',
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 10,
        ];

        $response = $this->actingAs($this->admin)
            ->put("/admin/products/{$this->product->id}", $payload);

        $response->assertRedirect();

        $fresh = $this->product->fresh();
        $this->assertNull($fresh->category_id);
        $this->assertNull($fresh->brand_id);
        $this->assertEquals(550, $fresh->price);
    }

    /**
     * TEST: Replace image #2 keeping position in array and safely cleaning up replaced file.
     */
    public function test_replace_image_keeps_exact_position_and_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/old-second-image.jpg', 'bytes');

        $this->product->images = [
            '/storage/products/test-image-1.svg',
            '/storage/products/old-second-image.jpg',
            '/storage/products/test-image-3.svg',
        ];
        $this->product->save();

        $replacedOrder = [
            '/storage/products/test-image-1.svg',
            '/storage/products/new-replacement-image.jpg', // Replaced in-place at index 1
            '/storage/products/test-image-3.svg',
        ];

        $payload = [
            'title' => $this->product->title,
            'slug' => $this->product->slug,
            'price' => $this->product->price,
            'category_id' => $this->category->id,
            'sku' => $this->product->sku,
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 50,
            'images' => json_encode($replacedOrder),
            'deleted_images' => json_encode(['/storage/products/old-second-image.jpg']),
        ];

        $response = $this->actingAs($this->admin)
            ->put("/admin/products/{$this->product->id}", $payload);

        $response->assertRedirect();

        $fresh = $this->product->fresh();
        $this->assertEquals($replacedOrder, $fresh->images);
        $this->assertEquals('/storage/products/new-replacement-image.jpg', $fresh->images[1]);
        $this->assertFalse(Storage::disk('public')->exists('products/old-second-image.jpg'));
    }

    /**
     * TEST: Images from product A do NOT leak into product B edit form.
     */
    public function test_editing_different_products_keeps_images_completely_isolated(): void
    {
        $productB = Product::create([
            'title' => 'Completely Different Product',
            'slug' => 'completely-different-product',
            'price' => 999,
            'status' => 'active',
            'product_status' => 'published',
            'images' => [
                '/storage/products/product-b-photo-1.svg',
            ],
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$productB->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('product-b-photo-1.svg');
        $response->assertDontSee('test-image-1.svg');
        $response->assertDontSee('test-image-2.svg');
        $response->assertDontSee('test-image-3.svg');
    }
}
