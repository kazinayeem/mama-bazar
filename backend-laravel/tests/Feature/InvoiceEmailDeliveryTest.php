<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceEmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();

        $this->order = Order::create([
            'order_id' => 'BS-TEST01',
            'email' => 'customer-test@example.com',
            'customer_name' => 'Test Customer',
            'phone' => '01711111111',
            'address' => '123 Main St',
            'city' => 'Dhaka',
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'payment_status' => 'success',
            'status' => 'confirmed',
            'subtotal' => 1000,
            'total_price' => 1060,
        ]);
    }

    public function test_email_invoice_requires_authenticated_admin_with_permission(): void
    {
        $response = $this->post(route('admin.orders.email-invoice', $this->order->id));
        $response->assertRedirect();
    }

    public function test_email_invoice_sync_mode_sends_email_and_logs(): void
    {
        config(['email_system.queue_connection' => 'sync']);
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.email-invoice', $this->order->id));

        $response->assertSessionHas('success');

        $log = EmailLog::where('order_id', $this->order->id)->where('email_type', 'invoice')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);
        $this->assertEquals('customer-test@example.com', $log->recipient_email);
    }

    public function test_email_invoice_background_mode_creates_queued_log(): void
    {
        config(['email_system.queue_connection' => 'database']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.orders.email-invoice', $this->order->id));

        $response->assertSessionHas('success');

        $log = EmailLog::where('order_id', $this->order->id)->where('email_type', 'invoice')->first();
        $this->assertNotNull($log);
        $this->assertEquals('queued', $log->status);
    }

    public function test_order_show_view_displays_customer_email_logs(): void
    {
        EmailLog::create([
            'order_id' => $this->order->id,
            'recipient_email' => $this->order->email,
            'subject' => 'Invoice #BS-TEST01',
            'email_type' => 'invoice',
            'status' => 'queued',
            'attempts' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order->id));
        $response->assertStatus(200);
        $response->assertSee('Invoice #BS-TEST01');
        $response->assertSee('Queued');
    }

    public function test_order_show_view_displays_retry_button_on_failed_log(): void
    {
        $log = EmailLog::create([
            'order_id' => $this->order->id,
            'recipient_email' => $this->order->email,
            'subject' => 'Invoice #BS-TEST01',
            'email_type' => 'invoice',
            'status' => 'failed',
            'attempts' => 1,
            'error_message' => 'Connection refused to SMTP host',
            'metadata' => [
                'replay' => [
                    'kind' => 'order',
                    'order_id' => $this->order->id,
                    'trigger' => 'invoice',
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order->id));
        $response->assertStatus(200);
        $response->assertSee('Connection refused to SMTP host');
        $response->assertSee('Retry delivery');
    }
}
