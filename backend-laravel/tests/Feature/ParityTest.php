<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use App\Services\JwtService;
use App\Services\RbacService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed default roles and admin accounts
        $this->seed(AdminSeeder::class);
    }

    /**
     * Test Root API info
     */
    public function test_root_endpoint_returns_expected_json(): void
    {
        $response = $this->getJson('/');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'service' => 'Mama Bazar API',
                'status' => 'running',
                'version' => '1.0.0',
            ])
            ->assertJsonStructure(['success', 'service', 'status', 'version', 'timestamp']);
    }

    /**
     * Test Health endpoint
     */
    public function test_health_endpoint_returns_expected_json(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'service' => 'Mama Bazar API',
                'status' => 'healthy',
            ])
            ->assertJsonStructure(['success', 'service', 'status', 'uptime', 'timestamp']);
    }

    /**
     * Test Dev-Login provides valid JWT token for seeded Super Admin
     */
    public function test_dev_login_returns_token_and_user_profile(): void
    {
        $response = $this->postJson('/api/users/dev-login');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'phone', 'role', 'permissions'],
                ],
            ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Verify decoded token
        $payload = JwtService::verifyToken($token);
        $this->assertNotNull($payload);
        $this->assertEquals('admin', $payload['role']);
    }

    /**
     * Test Authenticated Profile with Bearer token
     */
    public function test_authenticated_profile_access(): void
    {
        $loginRes = $this->postJson('/api/users/dev-login');
        $token = $loginRes->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'phone', 'role', 'permissions'],
            ]);
    }

    /**
     * Test Categories API
     */
    public function test_categories_endpoints(): void
    {
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);

        $treeResponse = $this->getJson('/api/categories/tree');
        $treeResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test Products API
     */
    public function test_products_list_and_search(): void
    {
        $response = $this->getJson('/api/products');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['page', 'limit', 'total', 'totalPages'],
            ]);

        $searchResponse = $this->getJson('/api/products/search?q=test');
        $searchResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test Homepage data compilation
     */
    public function test_homepage_endpoint(): void
    {
        $response = $this->getJson('/api/homepage');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'announcement',
                    'heroSlides',
                    'flashSaleWindow',
                    'popularSearches',
                    'sections',
                ],
            ]);
    }

    /**
     * Test Store Info
     */
    public function test_store_info_endpoint(): void
    {
        $response = $this->getJson('/api/settings/store-info');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'storeName' => 'Mama Bazar',
                    'email' => 'support@mamabazar.com',
                ],
            ]);
    }

    /**
     * Test Backup Dynamic PIN verification
     */
    public function test_backup_pin_verification(): void
    {
        $loginRes = $this->postJson('/api/users/dev-login');
        $token = $loginRes->json('data.token');

        // Wrong PIN
        $wrongRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/backup/verify-pin', ['pin' => '00000000']);
        $wrongRes->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Wrong PIN. Nice try 😄 Please check your backup PIN and try again.',
            ]);

        // Right PIN (calculated via BackupService)
        $pins = BackupService::getValidPins();
        $validPin = $pins[0];

        $correctRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/backup/verify-pin', ['pin' => $validPin]);
        $correctRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Security PIN verified successfully',
            ]);
    }

    /**
     * Test Order validation when empty items provided
     */
    public function test_order_creation_validation(): void
    {
        $response = $this->postJson('/api/order', [
            'customerName' => 'John Doe',
            'phone' => '01711111111',
            'items' => [],
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /**
     * Test RBAC Super Admin full permissions bypass
     */
    public function test_super_admin_rbac_bypass(): void
    {
        $resolved = RbacService::resolveUserPermissions(1, 'admin', 'SUPER_ADMIN');
        $this->assertEquals('SUPER_ADMIN', $resolved['customRole']);
        $this->assertEquals(['*'], $resolved['permissions']);
        $this->assertTrue(RbacService::hasPermission(['role' => 'admin', 'customRole' => 'SUPER_ADMIN', 'permissions' => ['*']], 'any.permission.code'));
    }

    /**
     * Test Shipping methods public and estimate
     */
    public function test_shipping_endpoints(): void
    {
        $response = $this->getJson('/api/shipping-methods/public');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);

        $estimate = $this->postJson('/api/shipping-methods/estimate', ['subtotal' => 1500]);
        $estimate->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test Payment methods public
     */
    public function test_payment_methods_public(): void
    {
        $response = $this->getJson('/api/payment-methods/public');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test Checkout Notices public
     */
    public function test_checkout_notices_public(): void
    {
        $response = $this->getJson('/api/checkout-notices/public');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test Tracking config
     */
    public function test_tracking_config(): void
    {
        $response = $this->getJson('/api/tracking/config');
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['customHeadScripts', 'customBodyScripts']]);
    }

    /**
     * Test Contact message submission
     */
    public function test_contact_submission(): void
    {
        $response = $this->postJson('/api/pages/contact', [
            'name' => 'Tanvir Ahmed',
            'phone' => '01812345678',
            'message' => 'Hello from parity test',
        ]);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'আপনার বার্তাটি পেয়েছি, শীঘ্রই যোগাযোগ করব।',
            ]);
    }

    /**
     * Test Chat validation
     */
    public function test_chat_validation(): void
    {
        $response = $this->postJson('/api/chat', ['message' => '']);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Message is required',
            ]);
    }
}
