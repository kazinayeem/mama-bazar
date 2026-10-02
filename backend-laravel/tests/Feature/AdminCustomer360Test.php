<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CustomerNote;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminCustomer360Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected User $customerWithoutOrders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')
            ->where('custom_role', 'SUPER_ADMIN')
            ->first();

        $this->customer = User::create([
            'name' => 'Tariq Al-Mansoor',
            'email' => 'tariq@example.com',
            'phone' => '01712345678',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'status' => 'active',
            'shipping_area' => 'inside_dhaka',
            'shipping_address' => 'House 12, Road 4, Dhanmondi, Dhaka',
            'last_login_at' => now()->subDay(),
            'created_at' => now()->subMonths(2),
        ]);

        $this->customerWithoutOrders = User::create([
            'name' => 'Nabila Rahman',
            'email' => 'nabila@example.com',
            'phone' => '01887654321',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'status' => 'active',
            'created_at' => now()->subWeek(),
        ]);

        // Create orders for Tariq
        $order1 = Order::create([
            'order_id' => 'ORD-1001',
            'invoice_number' => 'INV-2026-0001',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'email' => $this->customer->email,
            'shipping_area' => 'inside_dhaka',
            'address' => 'House 12, Road 4, Dhanmondi, Dhaka',
            'subtotal' => 1200.00,
            'shipping_cost' => 60.00,
            'discount' => 50.00,
            'total_price' => 1210.00,
            'payment_method' => 'bkash',
            'payment_status' => 'verified',
            'transaction_id' => 'TXN-BKASH-9988',
            'status' => 'delivered',
            'created_at' => now()->subMonth(),
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_title' => 'Organic Mustard Oil 1L',
            'quantity' => 2,
            'price' => 600.00,
        ]);

        $order2 = Order::create([
            'order_id' => 'ORD-1002',
            'invoice_number' => 'INV-2026-0002',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'email' => $this->customer->email,
            'shipping_area' => 'inside_dhaka',
            'address' => 'House 12, Road 4, Dhanmondi, Dhaka',
            'subtotal' => 800.00,
            'shipping_cost' => 60.00,
            'discount' => 0.00,
            'total_price' => 860.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_title' => 'Pure Ghee 500g',
            'quantity' => 1,
            'price' => 800.00,
        ]);

        // Address
        UserAddress::create([
            'user_id' => $this->customer->id,
            'recipient_name' => 'Tariq Al-Mansoor Office',
            'phone' => '01712345678',
            'division' => 'Dhaka',
            'district' => 'Dhaka',
            'area' => 'Banani',
            'shipping_area' => 'inside_dhaka',
            'address' => 'Road 11, Block D, Banani',
            'is_default' => true,
        ]);
    }

    public function test_guest_cannot_access_customer_list_or_details(): void
    {
        $response = $this->get(route('admin.customers.index'));
        $response->assertRedirect(route('login'));

        $detailResponse = $this->get(route('admin.customers.show', $this->customer->id));
        $detailResponse->assertRedirect(route('login'));
    }

    public function test_admin_can_view_customers_list_with_aggregates(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Customer Directory & 360° Profiles');
        $response->assertSee('Tariq Al-Mansoor');
        $response->assertSee('Nabila Rahman');
        $response->assertSee('Export CSV');
        $response->assertSee('Export PDF');
    }

    public function test_customer_list_filters_by_search_and_order_activity(): void
    {
        // Search by name
        $response = $this->actingAs($this->admin)->get(route('admin.customers.index', ['search' => 'Tariq']));
        $response->assertStatus(200);
        $response->assertSee('Tariq Al-Mansoor');
        $response->assertDontSee('Nabila Rahman');

        // Filter by no orders
        $noOrdersResponse = $this->actingAs($this->admin)->get(route('admin.customers.index', ['order_filter' => 'no_orders']));
        $noOrdersResponse->assertStatus(200);
        $noOrdersResponse->assertSee('Nabila Rahman');
        $noOrdersResponse->assertDontSee('Tariq Al-Mansoor');

        // Filter by with orders
        $withOrdersResponse = $this->actingAs($this->admin)->get(route('admin.customers.index', ['order_filter' => 'with_orders']));
        $withOrdersResponse->assertStatus(200);
        $withOrdersResponse->assertSee('Tariq Al-Mansoor');
        $withOrdersResponse->assertDontSee('Nabila Rahman');
    }

    public function test_customer_list_export_csv_and_pdf(): void
    {
        $csvResponse = $this->actingAs($this->admin)->get(route('admin.customers.export-list', ['format' => 'csv']));
        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('Content-Type'));

        $pdfResponse = $this->actingAs($this->admin)->get(route('admin.customers.export-list', ['format' => 'pdf']));
        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_admin_can_view_customer_360_profile_and_overview_kpis(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $this->customer->id));
        $response->assertStatus(200);
        $response->assertSee('Tariq Al-Mansoor');
        $response->assertSee('01712345678');
        $response->assertSee('tariq@example.com');
        $response->assertSee('ORD-1001');
        $response->assertSee('ORD-1002');
        $response->assertSee('Export Dossier');
    }

    public function test_customer_orders_tab_displays_correct_orders_and_filters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', [
            'id' => $this->customer->id,
            'tab' => 'orders',
            'order_status' => 'delivered',
        ]));
        $response->assertStatus(200);
        $response->assertSee('ORD-1001');
        $response->assertDontSee('ORD-1002');
    }

    public function test_customer_payments_tab_displays_actual_transactions(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', [
            'id' => $this->customer->id,
            'tab' => 'payments',
        ]));
        $response->assertStatus(200);
        $response->assertSee('TXN-BKASH-9988');
        $response->assertSee('bkash');
    }

    public function test_admin_can_add_update_and_delete_customer_address(): void
    {
        // Add address
        $addResponse = $this->actingAs($this->admin)->post(route('admin.customers.addresses.store', $this->customer->id), [
            'recipient_name' => 'Tariq Warehouse',
            'phone' => '01712345678',
            'division' => 'Dhaka',
            'district' => 'Gazipur',
            'shipping_area' => 'outside_dhaka',
            'address' => 'Plot 4, Industrial Area, Tongi',
            'is_default' => 0,
        ]);
        $addResponse->assertRedirect();
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->customer->id,
            'recipient_name' => 'Tariq Warehouse',
            'district' => 'Gazipur',
        ]);

        $createdAddr = UserAddress::where('recipient_name', 'Tariq Warehouse')->first();

        // Set Default
        $defaultResponse = $this->actingAs($this->admin)->post(route('admin.customers.addresses.default', [
            'id' => $this->customer->id,
            'addressId' => $createdAddr->id,
        ]));
        $defaultResponse->assertRedirect();
        $this->assertTrue($createdAddr->fresh()->is_default);

        // Delete address
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.customers.addresses.destroy', [
            'id' => $this->customer->id,
            'addressId' => $createdAddr->id,
        ]));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('user_addresses', ['id' => $createdAddr->id]);
    }

    public function test_admin_can_add_and_delete_internal_notes(): void
    {
        $noteResponse = $this->actingAs($this->admin)->post(route('admin.customers.notes.store', $this->customer->id), [
            'note' => 'VIP customer requesting phone confirmation prior to shipping.',
        ]);
        $noteResponse->assertRedirect();

        $this->assertDatabaseHas('customer_notes', [
            'customer_id' => $this->customer->id,
            'admin_id' => $this->admin->id,
            'note' => 'VIP customer requesting phone confirmation prior to shipping.',
        ]);

        $note = CustomerNote::where('customer_id', $this->customer->id)->first();

        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.customers.notes.destroy', [
            'id' => $this->customer->id,
            'noteId' => $note->id,
        ]));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('customer_notes', ['id' => $note->id]);
    }

    public function test_admin_can_update_customer_profile_and_toggle_status(): void
    {
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.customers.update', $this->customer->id), [
            'name' => 'Tariq Al-Mansoor Updated',
            'phone' => '01712345678',
            'email' => 'tariq.updated@example.com',
            'shipping_area' => 'inside_dhaka',
            'shipping_address' => 'Updated Shipping Address',
            'status' => 'active',
        ]);
        $updateResponse->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'name' => 'Tariq Al-Mansoor Updated',
            'email' => 'tariq.updated@example.com',
        ]);

        // Toggle status to inactive
        $toggleResponse = $this->actingAs($this->admin)->post(route('admin.customers.toggle', $this->customer->id));
        $toggleResponse->assertRedirect();
        $this->assertEquals('inactive', $this->customer->fresh()->status);
    }

    public function test_admin_can_export_individual_customer_360_dossier(): void
    {
        $pdfResponse = $this->actingAs($this->admin)->get(route('admin.customers.export', [
            'id' => $this->customer->id,
            'format' => 'pdf',
        ]));
        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('Content-Type'));

        $csvResponse = $this->actingAs($this->admin)->get(route('admin.customers.export', [
            'id' => $this->customer->id,
            'format' => 'csv',
        ]));
        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('Content-Type'));
    }

    public function test_admin_can_send_direct_email_to_customer(): void
    {
        Mail::fake();

        $emailResponse = $this->actingAs($this->admin)->post(route('admin.customers.email.send', $this->customer->id), [
            'subject' => 'Mama Bazar Special VIP Discount For You',
            'email_type' => 'transactional',
            'message' => 'Thank you for being a loyal customer of Mama Bazar. Here is a 10% coupon code for your next order.',
        ]);
        $emailResponse->assertRedirect();

        $this->assertDatabaseHas('email_logs', [
            'user_id' => $this->customer->id,
            'recipient_email' => $this->customer->email,
            'subject' => 'Mama Bazar Special VIP Discount For You',
        ]);
    }
}
