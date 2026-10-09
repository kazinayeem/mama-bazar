<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ScheduledReport;
use App\Models\User;
use App\Services\ActivityLoggerService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminActivityMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $staff;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->superAdmin = User::where('role', 'admin')
            ->where('custom_role', 'SUPER_ADMIN')
            ->first();

        if (! $this->superAdmin->email) {
            $this->superAdmin->email = 'superadmin@example.com';
            $this->superAdmin->save();
        }

        $this->staff = User::factory()->create([
            'email' => 'staff@example.com',
            'phone' => '01888888888',
            'password' => Hash::make('StaffPassword123!'),
            'role' => 'staff',
            'custom_role' => 'SALES_MANAGER',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer@example.com',
            'phone' => '01777777777',
            'password' => Hash::make('CustomerPass123!'),
            'role' => 'customer',
            'status' => 'active',
            'must_change_password' => false,
        ]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.activity.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_is_forbidden_from_accessing_activity_monitor(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.activity.index'));
        // Non-admin will either be redirected or 403 by admin.access middleware
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
    }

    public function test_staff_without_activity_permission_is_forbidden(): void
    {
        $response = $this->actingAs($this->staff)->get(route('admin.activity.index'));
        $response->assertForbidden();

        $jsonResponse = $this->actingAs($this->staff)->getJson(route('admin.activity.index'));
        $jsonResponse->assertStatus(403);
    }

    public function test_super_admin_can_access_activity_monitor_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.activity.index'));

        $response->assertStatus(200);
        $response->assertSee('Activity Monitor');
        $response->assertSee('Unified Activity Timeline');
    }

    public function test_dashboard_displays_real_metrics_and_comparison_presets(): void
    {
        // Seed some activities
        ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'order.created',
            'module' => 'orders',
            'actor_type' => 'customer',
            'actor_id' => $this->customer->id,
            'actor_name' => $this->customer->name,
            'actor_role' => 'Customer',
            'subject_type' => 'order',
            'subject_id' => '101',
            'description' => 'Customer placed order #BS-1001',
            'status' => 'success',
            'source' => 'storefront',
            'occurred_at' => now(),
        ]);

        ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'security.failed_login',
            'module' => 'security',
            'actor_type' => 'guest',
            'actor_name' => 'guest',
            'actor_role' => 'Guest',
            'description' => 'Failed login attempt for user intruder@example.com',
            'status' => 'failed',
            'source' => 'admin',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.activity.index', ['time_preset' => 'today']));

        $response->assertStatus(200);
        $response->assertSee('Customer placed order #BS-1001');
        $response->assertSee('security.failed_login');
    }

    public function test_order_activity_logging_records_events_properly(): void
    {
        $order = Order::create([
            'order_id' => 'BS-TEST1234',
            'user_id' => $this->customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipping_cost' => 60.00,
            'total_price' => 1560.00,
            'subtotal' => 1500.00,
            'customer_name' => 'Test Customer',
            'phone' => '01777777777',
            'address' => 'Dhaka, Bangladesh',
        ]);

        $logger = app(ActivityLoggerService::class);
        $logger->logOrder('order.created', $order, 'Order #BS-TEST1234 created', [
            'total' => 1500,
        ], null, 'success', 'storefront');

        $log = ActivityLog::where('event_name', 'order.created')
            ->where('subject_id', (string) $order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('orders', $log->module);
        $this->assertSame('success', $log->status);
        $this->assertSame('storefront', $log->source);
        $this->assertStringContainsString('BS-TEST1234', $log->description);
    }

    public function test_product_activity_logging_records_diff(): void
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $product = Product::create([
            'title' => 'Smart Watch Pro',
            'slug' => 'smart-watch-pro',
            'category_id' => $category->id,
            'price' => 2500,
            'status' => 'active',
        ]);

        $logger = app(ActivityLoggerService::class);
        $logger->logProduct(
            'product.price_changed',
            $product,
            'Price changed for Smart Watch Pro',
            [
                'oldValues' => ['price' => 2500],
                'newValues' => ['price' => 2800],
                'actor' => $this->superAdmin,
                'status' => 'success',
            ]
        );

        $log = ActivityLog::where('event_name', 'product.price_changed')
            ->where('subject_id', (string) $product->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('products', $log->module);
        $this->assertSame(2500, $log->old_values['price']);
        $this->assertSame(2800, $log->new_values['price']);
        $this->assertSame($this->superAdmin->name, $log->actor_name);
    }

    public function test_sensitive_information_is_safely_redacted(): void
    {
        $logger = app(ActivityLoggerService::class);
        $logger->logSecurity(
            'team.password_changed',
            'Admin changed password',
            [
                'newValues' => [
                    'password' => 'SuperSecretP@ss1',
                    'password_confirmation' => 'SuperSecretP@ss1',
                    'otp_code' => '987654',
                    'token' => 'xyz-token-abc',
                    'smtp_password' => 'mailsecret',
                    'email' => 'admin@mama-bazar.com',
                ],
                'actor' => $this->superAdmin,
            ]
        );

        $log = ActivityLog::where('event_name', 'team.password_changed')->first();

        $this->assertNotNull($log);
        $this->assertSame('[REDACTED]', $log->new_values['password']);
        $this->assertSame('[REDACTED]', $log->new_values['password_confirmation']);
        $this->assertSame('[REDACTED]', $log->new_values['otp_code']);
        $this->assertSame('[REDACTED]', $log->new_values['token']);
        $this->assertSame('[REDACTED]', $log->new_values['smtp_password']);
        $this->assertSame('admin@mama-bazar.com', $log->new_values['email']);
    }

    public function test_filtering_and_search_endpoints(): void
    {
        $log1 = ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'order.shipped',
            'module' => 'orders',
            'description' => 'Order #BS-9999 shipped via courier',
            'status' => 'success',
            'occurred_at' => now(),
        ]);

        $log2 = ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'email.failed',
            'module' => 'email',
            'description' => 'Delivery failed to invalid@example.com',
            'status' => 'failed',
            'occurred_at' => now(),
        ]);

        // Filter by module=orders
        $responseOrders = $this->actingAs($this->superAdmin)
            ->get(route('admin.activity.index', ['module' => 'orders']));
        $responseOrders->assertStatus(200);
        $responseOrders->assertSee('Order #BS-9999 shipped via courier');
        $responseOrders->assertDontSee('Delivery failed to invalid@example.com');

        // Filter by status=failed
        $responseFailed = $this->actingAs($this->superAdmin)
            ->get(route('admin.activity.index', ['status' => 'failed']));
        $responseFailed->assertStatus(200);
        $responseFailed->assertSee('Delivery failed to invalid@example.com');
        $responseFailed->assertDontSee('Order #BS-9999 shipped via courier');

        // Search by keyword
        $responseSearch = $this->actingAs($this->superAdmin)
            ->get(route('admin.activity.index', ['search' => 'BS-9999']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Order #BS-9999 shipped via courier');
    }

    public function test_activity_detail_json_endpoint(): void
    {
        $log = ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'system.backup_created',
            'module' => 'system',
            'description' => 'Manual database backup created',
            'status' => 'success',
            'source' => 'admin',
            'actor_name' => $this->superAdmin->name,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.activity.show', $log->uuid));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'activity' => [
                'uuid' => $log->uuid,
                'event_name' => 'system.backup_created',
                'module' => 'system',
            ],
        ]);
    }

    public function test_csv_export_stream_and_formula_injection_sanitization(): void
    {
        // Activity with potentially malicious spreadsheet formula in description
        ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'test.formula_injection',
            'module' => 'security',
            'description' => '=cmd|"/C calc"!A0',
            'status' => 'success',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.activity.export', ['format' => 'csv']));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Must have UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        // Malicious '=' prefix must be escaped with single quote "'"
        $this->assertStringContainsString("'=cmd|", $content);
    }

    public function test_pdf_export_returns_streamed_pdf(): void
    {
        ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'order.paid',
            'module' => 'orders',
            'description' => 'Payment received for Order #BS-PDF1',
            'status' => 'success',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.activity.export', ['format' => 'pdf', 'report_type' => 'order']));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }

    public function test_excel_export_returns_spreadsheet(): void
    {
        ActivityLog::create([
            'uuid' => (string) Str::uuid(),
            'event_name' => 'customer.registered',
            'module' => 'customers',
            'description' => 'New customer registered',
            'status' => 'success',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.activity.export', ['format' => 'xlsx', 'report_type' => 'customer']));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.ms-excel', (string) $response->headers->get('Content-Type'));
    }

    public function test_scheduled_reports_creation_and_command_execution(): void
    {
        Mail::fake();

        // 1. Create a scheduled report via web route
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.activity.scheduled.store'), [
                'title' => 'Weekly Security Audit',
                'report_type' => 'security',
                'format' => 'pdf',
                'frequency' => 'weekly',
                'recipients' => 'security@mama-bazar.com, audit@mama-bazar.com',
                'delivery_time' => '08:00',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $schedule = ScheduledReport::where('title', 'Weekly Security Audit')->first();
        $this->assertNotNull($schedule);
        $this->assertSame('weekly', $schedule->frequency);
        $this->assertContains('security@mama-bazar.com', $schedule->recipient_emails);

        // 2. Run the scheduled report artisan command
        $exitCode = Artisan::call('reports:send-scheduled', ['--id' => $schedule->id]);
        $this->assertSame(0, $exitCode);

        $schedule->refresh();
        $this->assertSame('success', $schedule->last_status);
        $this->assertNotNull($schedule->last_run_at);
        $this->assertNotNull($schedule->next_run_at);
    }
}
