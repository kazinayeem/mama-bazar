<?php

namespace Tests\Feature;

use App\Models\CheckoutSession;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\IncompleteOrderService;
use App\Services\RbacService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IncompleteOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->superAdmin = User::where('role', 'admin')->first();
        if (! $this->superAdmin->email) {
            $this->superAdmin->email = 'admin@example.com';
            $this->superAdmin->save();
        }
    }

    protected function seedStoreBasics(): array
    {
        $product = Product::create([
            'title' => 'Test Trackable Product',
            'slug' => 'test-trackable-product',
            'price' => 1200,
            'stock' => 50,
            'status' => 'active',
        ]);

        $shipping = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'charge' => 60,
            'estimated_delivery' => '24-48h',
            'applicable_areas' => 'inside_dhaka,outside_dhaka',
            'priority' => 1,
            'status' => 'active',
        ]);

        $payment = PaymentMethod::create([
            'code' => 'cod',
            'name' => 'Cash on Delivery',
            'type' => 'cod',
            'enabled' => true,
            'sort_order' => 1,
            'maintenance_mode' => false,
            'config' => [],
        ]);

        return compact('product', 'shipping', 'payment');
    }

    /** 1. Progress updates are idempotent and track milestones without duplicates */
    public function test_track_progress_records_session_and_milestones_idempotently(): void
    {
        $sessionId = 'cs_test_session_abc123';

        // Step 1: Open checkout
        $res1 = $this->postJson(route('checkout.track'), [
            'checkout_session_id' => $sessionId,
            'progress_percent' => 10,
            'current_step' => 'opened',
            'milestone' => 'opened',
            'cart_item_count' => 1,
            'cart_total' => 1260,
            'device_type' => 'desktop',
        ]);

        $res1->assertStatus(200);
        $res1->assertJson([
            'success' => true,
            'session_id' => $sessionId,
            'progress' => 10,
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('checkout_sessions', 1);
        $session = CheckoutSession::where('session_id', $sessionId)->first();
        $this->assertNotNull($session);
        $this->assertSame('active', $session->status);
        $this->assertSame(10, $session->progress_percent);
        $this->assertSame('opened', $session->current_step);

        // Step 2: Customer enters contact information (approx 50% completed)
        $res2 = $this->postJson(route('checkout.track'), [
            'checkout_session_id' => $sessionId,
            'progress_percent' => 50,
            'current_step' => 'contact_completed',
            'milestone' => 'progress_50',
            'completed_fields' => ['customer_name', 'phone'],
            'cart_item_count' => 1,
            'cart_total' => 1260,
            'device_type' => 'desktop',
        ]);

        $res2->assertStatus(200);
        $res2->assertJson([
            'success' => true,
            'session_id' => $sessionId,
            'progress' => 50,
        ]);

        // Database must still have only 1 row (no duplicate rows created)
        $this->assertDatabaseCount('checkout_sessions', 1);

        $session->refresh();
        $this->assertSame(50, $session->progress_percent);
        $this->assertSame('contact_completed', $session->current_step);
        $this->assertContains('customer_name', $session->completed_fields);
        $this->assertContains('phone', $session->completed_fields);
        $this->assertCount(2, $session->milestones);
    }

    /** 2. Monotonic progress: clients cannot revert a higher progress to a lower one */
    public function test_progress_percent_is_monotonically_non_decreasing(): void
    {
        $sessionId = 'cs_monotonic_789';

        $this->postJson(route('checkout.track'), [
            'checkout_session_id' => $sessionId,
            'progress_percent' => 75,
            'current_step' => 'shipping_completed',
        ]);

        // Attempt to submit lower progress
        $this->postJson(route('checkout.track'), [
            'checkout_session_id' => $sessionId,
            'progress_percent' => 25,
            'current_step' => 'contact_started',
        ]);

        $session = CheckoutSession::where('session_id', $sessionId)->first();
        $this->assertSame(75, $session->progress_percent);
    }

    /** 3. Successful order creation marks the checkout session as converted */
    public function test_successful_order_creation_converts_checkout_session(): void
    {
        $basics = $this->seedStoreBasics();
        $sessionId = 'cs_convert_flow_999';

        // Customer tracks up to 85%
        $this->postJson(route('checkout.track'), [
            'checkout_session_id' => $sessionId,
            'progress_percent' => 85,
            'current_step' => 'shipping_completed',
            'completed_fields' => ['customer_name', 'phone', 'district', 'address', 'shipping_method'],
        ]);

        $session = CheckoutSession::where('session_id', $sessionId)->first();
        $this->assertSame('active', $session->status);

        // Place order with checkout_session_id passed in form
        $orderPayload = [
            'checkout_session_id' => $sessionId,
            'order_key' => 'ok_'.uniqid(),
            'customer_name' => 'John Doe',
            'phone' => '01712345678',
            'district' => 'Dhaka',
            'address' => 'House 12, Road 5, Dhanmondi',
            'shipping_method_id' => $basics['shipping']->id,
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $basics['product']->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $res = $this->post(route('checkout.process'), $orderPayload);
        $res->assertSessionHasNoErrors();
        $res->assertRedirect();

        $session->refresh();
        $this->assertSame('converted', $session->status);
        $this->assertSame(100, $session->progress_percent);
        $this->assertSame('completed', $session->current_step);
        $this->assertNotNull($session->order_id);
        $this->assertNotNull($session->converted_at);
        $this->assertTrue($session->isConverted());
    }

    /** 4. Inactivity transitions: active -> incomplete (after 30 min) and incomplete -> expired (after 7 days) */
    public function test_inactivity_evaluation_transitions(): void
    {
        // 1. Session active 40 minutes ago -> should transition to incomplete
        $staleActive = CheckoutSession::create([
            'session_id' => 'cs_stale_active',
            'status' => 'active',
            'progress_percent' => 50,
            'current_step' => 'contact_completed',
            'first_active_at' => Carbon::now()->subMinutes(45),
            'last_active_at' => Carbon::now()->subMinutes(35),
        ]);

        // 2. Session active 10 minutes ago -> should remain active
        $recentActive = CheckoutSession::create([
            'session_id' => 'cs_recent_active',
            'status' => 'active',
            'progress_percent' => 30,
            'current_step' => 'contact_started',
            'first_active_at' => Carbon::now()->subMinutes(15),
            'last_active_at' => Carbon::now()->subMinutes(10),
        ]);

        // 3. Incomplete session 8 days old -> should transition to expired
        $oldIncomplete = CheckoutSession::create([
            'session_id' => 'cs_old_incomplete',
            'status' => 'incomplete',
            'progress_percent' => 50,
            'current_step' => 'contact_completed',
            'first_active_at' => Carbon::now()->subDays(9),
            'last_active_at' => Carbon::now()->subDays(8),
        ]);

        IncompleteOrderService::evaluateInactivity(30);

        $staleActive->refresh();
        $this->assertSame('incomplete', $staleActive->status);
        $this->assertNotNull($staleActive->abandoned_at);

        $recentActive->refresh();
        $this->assertSame('active', $recentActive->status);

        $oldIncomplete->refresh();
        $this->assertSame('expired', $oldIncomplete->status);
    }

    /** 5. Prune command cleans up checkout sessions older than retention days */
    public function test_prune_checkout_sessions_artisan_command(): void
    {
        // Session 35 days old
        CheckoutSession::create([
            'session_id' => 'cs_retention_old',
            'status' => 'incomplete',
            'progress_percent' => 25,
            'first_active_at' => Carbon::now()->subDays(40),
            'last_active_at' => Carbon::now()->subDays(35),
        ]);

        // Session 10 days old
        CheckoutSession::create([
            'session_id' => 'cs_retention_recent',
            'status' => 'incomplete',
            'progress_percent' => 50,
            'first_active_at' => Carbon::now()->subDays(12),
            'last_active_at' => Carbon::now()->subDays(10),
        ]);

        $this->artisan('checkout-sessions:prune --days=30')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('checkout_sessions', ['session_id' => 'cs_retention_old']);
        $this->assertDatabaseHas('checkout_sessions', ['session_id' => 'cs_retention_recent']);
    }

    /** 6. RBAC protections on Incomplete Orders admin dashboard */
    public function test_admin_dashboard_rbac_access_control(): void
    {
        // 1. Guest is redirected to login
        $this->get(route('admin.incomplete-orders.index'))
            ->assertRedirect(route('login'));

        // 2. Staff without incomplete_orders.view is forbidden (403)
        $unauthorizedStaff = User::create([
            'name' => 'Restricted Staff',
            'phone' => '01711223344',
            'email' => 'restricted@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions_json' => ['orders.view'],
        ]);

        $this->actingAs($unauthorizedStaff)
            ->get(route('admin.incomplete-orders.index'))
            ->assertStatus(403);

        // 3. Staff with incomplete_orders.view has access (200 OK)
        $authorizedStaff = User::create([
            'name' => 'Analytics Viewer',
            'phone' => '01711223355',
            'email' => 'viewer@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions_json' => ['incomplete_orders.view'],
        ]);

        $res = $this->actingAs($authorizedStaff)
            ->get(route('admin.incomplete-orders.index'));

        $res->assertStatus(200);
        $res->assertSee('Incomplete Orders &amp; Abandoned Checkouts', false);
        $res->assertSee('Checkout Conversion Funnel');

        // IP column must not be shown without incomplete_orders.view_ip
        $res->assertDontSee('Client IP');

        // 4. Staff with incomplete_orders.view_ip sees Client IP
        $authorizedStaff->permissions_json = ['incomplete_orders.view', 'incomplete_orders.view_ip'];
        $authorizedStaff->save();
        RbacService::invalidateUserPermissionCache($authorizedStaff->id);

        $resIp = $this->actingAs($authorizedStaff)
            ->get(route('admin.incomplete-orders.index'));

        $resIp->assertStatus(200);
        $resIp->assertSee('Client IP');
    }

    /** 7. Export CSV endpoint checks permission and streams data */
    public function test_export_csv_checks_permission_and_streams(): void
    {
        $staffNoExport = User::create([
            'name' => 'No Export Staff',
            'phone' => '01711223366',
            'email' => 'noexport@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions_json' => ['incomplete_orders.view'],
        ]);

        // Forbidden without export permission
        $this->actingAs($staffNoExport)
            ->get(route('admin.incomplete-orders.export'))
            ->assertStatus(403);

        // Allowed with export permission
        $staffNoExport->permissions_json = ['incomplete_orders.view', 'incomplete_orders.export'];
        $staffNoExport->save();
        RbacService::invalidateUserPermissionCache($staffNoExport->id);

        CheckoutSession::create([
            'session_id' => 'cs_for_export_1',
            'status' => 'incomplete',
            'progress_percent' => 50,
            'current_step' => 'contact_completed',
            'first_active_at' => now(),
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($staffNoExport)
            ->get(route('admin.incomplete-orders.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** 8. Retention pruning endpoint checks manage_retention permission */
    public function test_retention_prune_endpoint_permission(): void
    {
        $staffNoManage = User::create([
            'name' => 'No Manage Staff',
            'phone' => '01711223377',
            'email' => 'nomanage@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions_json' => ['incomplete_orders.view'],
        ]);

        $this->actingAs($staffNoManage)
            ->post(route('admin.incomplete-orders.prune'), ['retention_days' => 30])
            ->assertStatus(403);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.incomplete-orders.prune'), ['retention_days' => 30])
            ->assertRedirect(route('admin.incomplete-orders.index'))
            ->assertSessionHas('success');
    }

    /** 9. Geolocation handles private/localhost IP addresses gracefully */
    public function test_ip_geolocation_handles_local_and_private_networks(): void
    {
        $session = IncompleteOrderService::recordProgress([
            'session_id' => 'cs_localhost_test',
            'progress_percent' => 25,
        ], request()->create('/checkout/track', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]));

        $this->assertSame('Local Network', $session->country);
    }
}
