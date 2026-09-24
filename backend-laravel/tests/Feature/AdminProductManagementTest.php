<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;
    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('custom_role', 'SUPER_ADMIN')->first() ?? User::where('role', 'admin')->first();

        $this->category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        $this->brand = Brand::create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'status' => 'active',
        ]);
    }

    public function test_guest_is_redirected_from_admin_products(): void
    {
        $response = $this->get('/admin/products');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_product_list(): void
    {
        Product::create([
            'title' => 'Samsung Galaxy S24 Ultra',
            'slug' => 'samsung-galaxy-s24-ultra',
            'price' => 125000,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 15,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/products');
        $response->assertStatus(200);
        $response->assertSee('Samsung Galaxy S24 Ultra');
        $response->assertSee('125,000');
    }

    public function test_product_search_and_filtering(): void
    {
        $p1 = Product::create([
            'title' => 'iPhone 15 Pro Max',
            'slug' => 'iphone-15-pro-max',
            'price' => 150000,
            'category_id' => $this->category->id,
            'sku' => 'IPH-15PM',
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 20,
        ]);

        $p2 = Product::create([
            'title' => 'Sony WH-1000XM5 Headphones',
            'slug' => 'sony-wh-1000xm5',
            'price' => 38000,
            'category_id' => $this->category->id,
            'sku' => 'SNY-XM5',
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 5,
            'low_stock_alert' => 10,
        ]);

        // Search by name
        $response = $this->actingAs($this->admin)->get('/admin/products?search=iPhone');
        $response->assertStatus(200);
        $response->assertSee('iPhone 15 Pro Max');
        $response->assertDontSee('Sony WH-1000XM5');

        // Search by SKU
        $response = $this->actingAs($this->admin)->get('/admin/products?search=SNY-XM5');
        $response->assertStatus(200);
        $response->assertSee('Sony WH-1000XM5');
        $response->assertDontSee('iPhone 15 Pro Max');

        // Filter by Stock status (low_stock)
        $response = $this->actingAs($this->admin)->get('/admin/products?stock=low_stock');
        $response->assertStatus(200);
        $response->assertSee('Sony WH-1000XM5');
        $response->assertDontSee('iPhone 15 Pro Max');
    }

    public function test_admin_can_view_create_product_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/products/create');
        $response->assertStatus(200);
        $response->assertSee('General Information');
        $response->assertSee('Pricing');
        $response->assertSee('Inventory');
        $response->assertSee('Variants');
        $response->assertSee('Specifications');
        $response->assertSee('SEO & Marketing');
    }

    public function test_admin_can_store_product_with_variants_and_specs(): void
    {
        $payload = [
            'title' => 'Wireless Bluetooth Earbuds',
            'slug' => 'wireless-bluetooth-earbuds',
            'price' => 2500,
            'sale_price' => 2200,
            'discount' => 12,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'sku' => 'EAR-BT-01',
            'has_variants' => 1,
            'variants' => json_encode([
                [
                    'name' => 'Black / Standard',
                    'price' => '2500',
                    'salePrice' => '2200',
                    'sku' => 'EAR-BT-BLK',
                    'stock' => '25',
                    'availability' => true,
                    'options' => ['color' => 'Black'],
                ],
                [
                    'name' => 'White / Standard',
                    'price' => '2500',
                    'salePrice' => '2200',
                    'sku' => 'EAR-BT-WHT',
                    'stock' => '15',
                    'availability' => true,
                    'options' => ['color' => 'White'],
                ],
            ]),
            'specs' => json_encode([
                ['label' => 'Battery Life', 'value' => '30 hours'],
                ['label' => 'Bluetooth Version', 'value' => '5.3'],
            ]),
            'is_featured' => 1,
            'is_flash_sale' => 1,
            'product_status' => 'published',
            'save_mode' => 'publish',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/products', $payload);
        $response->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'title' => 'Wireless Bluetooth Earbuds',
            'slug' => 'wireless-bluetooth-earbuds',
            'sku' => 'EAR-BT-01',
            'is_featured' => 1,
            'is_flash_sale' => 1,
            'product_status' => 'published',
        ]);

        $product = Product::where('slug', 'wireless-bluetooth-earbuds')->first();
        $this->assertNotNull($product);
        $this->assertEquals(2, $product->variants()->count());
        $this->assertEquals(2, $product->specs()->count());
    }

    public function test_admin_can_view_product_details_page(): void
    {
        $product = Product::create([
            'title' => 'MacBook Pro 16" M3 Max',
            'slug' => 'macbook-pro-16-m3-max',
            'price' => 385000,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'sku' => 'MBP-16-M3M',
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 10,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}");
        $response->assertStatus(200);
        $response->assertSee('MacBook Pro 16" M3 Max');
        $response->assertSee('MBP-16-M3M');
        $response->assertSee('385,000');
    }

    public function test_admin_can_edit_and_update_product(): void
    {
        $product = Product::create([
            'title' => 'Mechanical Keyboard',
            'slug' => 'mechanical-keyboard',
            'price' => 4500,
            'category_id' => $this->category->id,
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 30,
        ]);

        $editResponse = $this->actingAs($this->admin)->get("/admin/products/{$product->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Mechanical Keyboard');

        $updatePayload = [
            'title' => 'RGB Mechanical Gaming Keyboard',
            'slug' => 'mechanical-keyboard',
            'price' => 5200,
            'category_id' => $this->category->id,
            'stock' => 40,
            'save_mode' => 'publish',
        ];

        $updateResponse = $this->actingAs($this->admin)->put("/admin/products/{$product->id}", $updatePayload);
        $updateResponse->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'RGB Mechanical Gaming Keyboard',
            'price' => 5200,
            'stock' => 40,
        ]);
    }

    public function test_admin_can_duplicate_product(): void
    {
        $product = Product::create([
            'title' => 'Original Smart Watch',
            'slug' => 'original-smart-watch',
            'price' => 6500,
            'category_id' => $this->category->id,
            'status' => 'active',
            'product_status' => 'published',
            'stock' => 25,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/products/{$product->id}/duplicate");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', [
            'title' => 'Copy of Original Smart Watch',
        ]);
    }

    public function test_admin_can_toggle_featured_status(): void
    {
        $product = Product::create([
            'title' => 'Gaming Mouse',
            'slug' => 'gaming-mouse',
            'price' => 1500,
            'category_id' => $this->category->id,
            'is_featured' => 0,
            'status' => 'active',
            'product_status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/products/{$product->id}/toggle-featured", [
            'featured' => true,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'isFeatured' => true]);

        $this->assertEquals(1, $product->fresh()->is_featured);
    }

    public function test_admin_can_perform_bulk_actions(): void
    {
        $p1 = Product::create([
            'title' => 'Product 1',
            'slug' => 'p1',
            'price' => 100,
            'product_status' => 'draft',
            'status' => 'inactive',
        ]);

        $p2 = Product::create([
            'title' => 'Product 2',
            'slug' => 'p2',
            'price' => 200,
            'product_status' => 'draft',
            'status' => 'inactive',
        ]);

        // Bulk Publish
        $response = $this->actingAs($this->admin)->postJson('/admin/products/bulk', [
            'action' => 'publish',
            'ids' => [$p1->id, $p2->id],
        ]);
        $response->assertStatus(200);

        $this->assertEquals('published', $p1->fresh()->product_status);
        $this->assertEquals('published', $p2->fresh()->product_status);

        // Bulk Delete
        $response = $this->actingAs($this->admin)->postJson('/admin/products/bulk', [
            'action' => 'delete',
            'ids' => [$p1->id],
        ]);
        $response->assertStatus(200);
        $this->assertNull(Product::find($p1->id));
    }

    public function test_admin_can_export_csv(): void
    {
        Product::create([
            'title' => 'Exportable Item',
            'slug' => 'exportable-item',
            'price' => 999,
            'sku' => 'EXP-001',
            'category_id' => $this->category->id,
            'status' => 'active',
            'product_status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/products/export');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Exportable Item', $response->getContent());
        $this->assertStringContainsString('EXP-001', $response->getContent());
    }

    public function test_admin_can_import_csv(): void
    {
        $csvContent = "title,price,sku,stock,productStatus\nImported Product,3500,IMP-99,50,published\n";

        $response = $this->actingAs($this->admin)->postJson('/admin/products/import', [
            'csv' => $csvContent,
        ]);
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'imported' => 1]);

        $this->assertDatabaseHas('products', [
            'title' => 'Imported Product',
            'price' => 3500,
            'sku' => 'IMP-99',
            'stock' => 50,
        ]);
    }

    public function test_admin_can_delete_single_product(): void
    {
        $product = Product::create([
            'title' => 'To Be Deleted',
            'slug' => 'to-be-deleted',
            'price' => 100,
            'category_id' => $this->category->id,
            'status' => 'active',
            'product_status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");
        $response->assertRedirect('/admin/products');

        $this->assertNull(Product::find($product->id));
    }
}
