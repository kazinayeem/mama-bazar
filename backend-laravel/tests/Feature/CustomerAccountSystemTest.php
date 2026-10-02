<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAccountSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $otherCustomer;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')
            ->where('custom_role', 'SUPER_ADMIN')
            ->first();

        $this->customer = User::factory()->create([
            'name' => 'Alice Customer',
            'email' => 'alice@example.com',
            'phone' => '01711122233',
            'password' => Hash::make('Secret123!'),
            'role' => 'customer',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $this->otherCustomer = User::factory()->create([
            'name' => 'Bob Stranger',
            'email' => 'bob@example.com',
            'phone' => '01799988877',
            'password' => Hash::make('Secret123!'),
            'role' => 'customer',
            'status' => 'active',
            'must_change_password' => false,
        ]);
    }

    public function test_guest_sees_signin_and_register_options(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Sign In');
        $response->assertSee('Register');
        $response->assertDontSee('My Account');
    }

    public function test_authenticated_customer_sees_account_dropdown_links(): void
    {
        $response = $this->actingAs($this->customer)->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Alice Customer');
        $response->assertSee('My Profile');
        $response->assertSee('My Orders');
        $response->assertSee('My Addresses');
        $response->assertSee('Account Settings');
        $response->assertSee('Email Preferences');
        $response->assertSee('Logout');
        // Customer should NOT see Admin Panel link
        $response->assertDontSee('Admin Panel');
    }

    public function test_guest_redirected_to_login_when_accessing_account_pages(): void
    {
        $protectedRoutes = [
            route('account.dashboard'),
            route('account.orders'),
            route('account.profile'),
            route('account.addresses'),
            route('account.settings'),
            route('account.email'),
        ];

        foreach ($protectedRoutes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_customer_can_view_dashboard_with_real_order_statistics(): void
    {
        // Create 2 orders for Alice
        Order::create([
            'order_id' => 'BS-ORD-001',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'address' => 'Dhaka, Bangladesh',
            'shipping_cost' => 60,
            'subtotal' => 1000,
            'total_price' => 1060,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        Order::create([
            'order_id' => 'BS-ORD-002',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'address' => 'Dhaka, Bangladesh',
            'shipping_cost' => 60,
            'subtotal' => 2000,
            'total_price' => 2060,
            'status' => 'delivered',
            'payment_status' => 'success',
        ]);

        // Order for Bob
        Order::create([
            'order_id' => 'BS-ORD-999',
            'user_id' => $this->otherCustomer->id,
            'customer_name' => $this->otherCustomer->name,
            'phone' => $this->otherCustomer->phone,
            'address' => 'Chittagong, Bangladesh',
            'shipping_cost' => 120,
            'subtotal' => 5000,
            'total_price' => 5120,
            'status' => 'processing',
            'payment_status' => 'success',
        ]);

        $response = $this->actingAs($this->customer)->get(route('account.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Welcome back, Alice Customer!');
        $response->assertSee('BS-ORD-001');
        $response->assertSee('BS-ORD-002');
        // Alice MUST NOT see Bob's order
        $response->assertDontSee('BS-ORD-999');
    }

    public function test_customer_can_view_own_orders_and_filters(): void
    {
        Order::create([
            'order_id' => 'BS-ORD-AAA',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'address' => 'Dhaka, Bangladesh',
            'shipping_cost' => 60,
            'subtotal' => 500,
            'total_price' => 560,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        Order::create([
            'order_id' => 'BS-ORD-BBB',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'address' => 'Dhaka, Bangladesh',
            'shipping_cost' => 60,
            'subtotal' => 800,
            'total_price' => 860,
            'status' => 'delivered',
            'payment_status' => 'success',
        ]);

        // Filter status=pending
        $responsePending = $this->actingAs($this->customer)
            ->get(route('account.orders', ['status' => 'pending']));
        $responsePending->assertStatus(200);
        $responsePending->assertSee('BS-ORD-AAA');
        $responsePending->assertDontSee('BS-ORD-BBB');

        // Search query q=BBB
        $responseSearch = $this->actingAs($this->customer)
            ->get(route('account.orders', ['q' => 'BBB']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('BS-ORD-BBB');
        $responseSearch->assertDontSee('BS-ORD-AAA');
    }

    public function test_customer_can_view_own_order_details(): void
    {
        $order = Order::create([
            'order_id' => 'BS-DETAIL-100',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => $this->customer->phone,
            'address' => 'House 1, Road 2, Gulshan, Dhaka',
            'shipping_cost' => 60,
            'subtotal' => 1200,
            'total_price' => 1260,
            'status' => 'processing',
            'payment_status' => 'success',
            'courier_tracking_number' => 'ST-987654321',
        ]);

        $category = Category::create([
            'name' => 'Fruits',
            'slug' => 'fruits',
        ]);

        $product = Product::create([
            'title' => 'Fresh Organic Mangoes',
            'slug' => 'fresh-organic-mangoes',
            'category_id' => $category->id,
            'price' => 600,
            'status' => 'active',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => 'Fresh Organic Mangoes',
            'product_sku' => 'MNG-ORG-01',
            'quantity' => 2,
            'price' => 600,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('account.orders.show', $order->order_id));

        $response->assertStatus(200);
        $response->assertSee('BS-DETAIL-100');
        $response->assertSee('Fresh Organic Mangoes');
        $response->assertSee('House 1, Road 2, Gulshan, Dhaka');
        $response->assertSee('ST-987654321');
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $bobsOrder = Order::create([
            'order_id' => 'BS-BOB-SECRET',
            'user_id' => $this->otherCustomer->id,
            'customer_name' => $this->otherCustomer->name,
            'phone' => $this->otherCustomer->phone,
            'address' => 'Private Road, Sylhet',
            'shipping_cost' => 120,
            'subtotal' => 3000,
            'total_price' => 3120,
            'status' => 'delivered',
            'payment_status' => 'success',
        ]);

        // Alice tries to access Bob's order ID
        $response = $this->actingAs($this->customer)
            ->get(route('account.orders.show', $bobsOrder->order_id));

        $response->assertStatus(404);
    }

    public function test_customer_can_update_profile_without_modifying_role(): void
    {
        $response = $this->actingAs($this->customer)
            ->put(route('account.profile.update'), [
                'name' => 'Alice New Name',
                'phone' => '01711999999',
                'shipping_area' => 'outside_dhaka',
                'shipping_address' => 'Updated Shipping Street 123',
                // Malicious payload attempt
                'role' => 'admin',
                'custom_role' => 'SUPER_ADMIN',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertSame('Alice New Name', $this->customer->name);
        $this->assertSame('01711999999', $this->customer->phone);
        $this->assertSame('outside_dhaka', $this->customer->shipping_area);
        $this->assertSame('Updated Shipping Street 123', $this->customer->shipping_address);
        // Security check: role remains customer
        $this->assertSame('customer', $this->customer->role);
        $this->assertNull($this->customer->custom_role);
    }

    public function test_customer_can_manage_addresses_create_update_default_delete(): void
    {
        // 1. Create Address
        $createResponse = $this->actingAs($this->customer)
            ->post(route('account.addresses.store'), [
                'recipient_name' => 'Alice Office',
                'phone' => '01711122233',
                'shipping_area' => 'inside_dhaka',
                'division' => 'Dhaka',
                'district' => 'Dhaka',
                'area' => 'Banani',
                'address' => 'Plot 55, Road 11, Block D',
                'apartment' => 'Floor 4, Suite B',
                'postal_code' => '1213',
                'is_default' => 1,
            ]);

        $createResponse->assertRedirect();
        $createResponse->assertSessionHas('success');

        $address = UserAddress::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($address);
        $this->assertSame('Alice Office', $address->recipient_name);
        $this->assertTrue($address->is_default);

        // 2. Create Second Address
        $this->actingAs($this->customer)
            ->post(route('account.addresses.store'), [
                'recipient_name' => 'Alice Home',
                'phone' => '01711122233',
                'shipping_area' => 'inside_dhaka',
                'address' => 'House 10, Road 4, Dhanmondi',
                'is_default' => 0,
            ]);

        $this->assertSame(2, $this->customer->addresses()->count());

        $homeAddress = UserAddress::where('recipient_name', 'Alice Home')->first();
        $this->assertFalse($homeAddress->is_default);

        // 3. Set Second Address as Default
        $defaultResponse = $this->actingAs($this->customer)
            ->post(route('account.addresses.default', $homeAddress->id));
        $defaultResponse->assertRedirect();

        $homeAddress->refresh();
        $address->refresh();
        $this->assertTrue($homeAddress->is_default);
        $this->assertFalse($address->is_default);

        // 4. Update Second Address
        $updateResponse = $this->actingAs($this->customer)
            ->put(route('account.addresses.update', $homeAddress->id), [
                'recipient_name' => 'Alice Home Updated',
                'phone' => '01711122233',
                'shipping_area' => 'inside_dhaka',
                'address' => 'House 10, Road 4, Dhanmondi - 2nd Floor',
            ]);
        $updateResponse->assertRedirect();
        $homeAddress->refresh();
        $this->assertSame('Alice Home Updated', $homeAddress->recipient_name);

        // 5. Delete Address
        $deleteResponse = $this->actingAs($this->customer)
            ->delete(route('account.addresses.destroy', $homeAddress->id));
        $deleteResponse->assertRedirect();
        $this->assertNull(UserAddress::find($homeAddress->id));
    }

    public function test_customer_can_change_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->customer)
            ->post(route('account.settings.password'), [
                'current_password' => 'Secret123!',
                'password' => 'BrandNewPassword123!',
                'password_confirmation' => 'BrandNewPassword123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->customer->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123!', $this->customer->password));
    }

    public function test_password_change_fails_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->customer)
            ->post(route('account.settings.password'), [
                'current_password' => 'WrongPassword!',
                'password' => 'BrandNewPassword123!',
                'password_confirmation' => 'BrandNewPassword123!',
            ]);

        $response->assertSessionHasErrors('current_password');

        $this->customer->refresh();
        $this->assertTrue(Hash::check('Secret123!', $this->customer->password));
    }

    public function test_customer_logout_works_properly(): void
    {
        $response = $this->actingAs($this->customer)->post(route('logout'));
        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_existing_track_order_remains_functional(): void
    {
        $order = Order::create([
            'order_id' => 'BS-TRACK-888',
            'user_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'phone' => '01711122233',
            'address' => 'Dhaka, Bangladesh',
            'shipping_cost' => 60,
            'subtotal' => 1500,
            'total_price' => 1560,
            'status' => 'shipped',
            'payment_status' => 'success',
        ]);

        $response = $this->get(route('track', [
            'order_id' => 'BS-TRACK-888',
            'phone' => '01711122233',
        ]));

        $response->assertStatus(200);
        $response->assertSee('BS-TRACK-888');
        $response->assertSee('shipped');
    }
}
