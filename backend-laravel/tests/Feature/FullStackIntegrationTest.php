<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\PaymentMethod;
use App\Services\MediaStorageService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\PolicyPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class FullStackIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            AdminSeeder::class,
            AdminUserSeeder::class,
            PolicyPageSeeder::class,
        ]);
    }

    public function test_seeded_admin_can_log_in_via_env_credentials(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'ChangeMe123!');

        $response = $this->post('/admin/login', [
            'login' => $email,
            'password' => $password,
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_admin_panel_is_protected_against_unauthorized_guests(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_user_facing_blade_pages_render_successfully(): void
    {
        $routes = ['/', '/shop', '/cart', '/checkout', '/track', '/about', '/contact', '/faq'];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_product_detail_page_renders_with_seeded_product(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'fresh-groceries'],
            ['name' => 'Fresh Groceries', 'status' => 'active']
        );

        $product = Product::firstOrCreate(
            ['slug' => 'test-mustard-oil'],
            [
                'title' => 'Test Mustard Oil 1L',
                'price' => 350,
                'category_id' => $category->id,
                'status' => 'active',
                'stock' => 50,
            ]
        );

        $response = $this->get("/products/{$product->slug}");
        $response->assertStatus(200);
        $response->assertSee('Test Mustard Oil 1L');
    }

    public function test_local_image_storage_works_and_returns_public_storage_url(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test-product.jpg', 600, 600);
        $upload = MediaStorageService::uploadFile($file, 'products');

        $this->assertEquals('local', $upload['provider']);
        $this->assertStringStartsWith('/storage/products/', $upload['url']);
        Storage::disk('public')->assertExists($upload['path']);

        // Test delete
        $deleted = MediaStorageService::deleteFile($upload['url']);
        $this->assertTrue($deleted);
        Storage::disk('public')->assertMissing($upload['path']);
    }

    public function test_order_placement_works_and_persists_in_sqlite(): void
    {
        $shipping = ShippingMethod::firstOrCreate(
            ['name' => 'Inside Dhaka Standard'],
            ['charge' => 60, 'status' => 'active']
        );

        $category = Category::firstOrCreate(['slug' => 'test-cat'], ['name' => 'Test Cat', 'status' => 'active']);
        $product = Product::firstOrCreate(
            ['slug' => 'test-rice-5kg'],
            ['title' => 'Miniket Rice 5kg', 'price' => 450, 'category_id' => $category->id, 'status' => 'active', 'stock' => 20]
        );

        $payload = [
            'customer_name' => 'Rahim Chowdhury',
            'phone' => '01812345678',
            'address' => 'House 12, Road 4, Dhanmondi',
            'district' => 'Dhaka',
            'shipping_method_id' => $shipping->id,
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 450,
                ]
            ],
        ];

        $response = $this->post('/checkout', $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Rahim Chowdhury',
            'phone' => '01812345678',
            'payment_method' => 'cod',
        ]);
    }

    public function test_api_compatibility_for_react_frontend(): void
    {
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);

        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data', 'pagination']);

        $response = $this->getJson('/api/homepage');
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);

        $response = $this->getJson('/api/settings');
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }
}
