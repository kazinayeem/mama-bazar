<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\EmailOtp;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\EmailOtpService;
use App\Services\EmailSettingService;
use App\Services\JwtService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailAutomationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('role', 'admin')->first();

        // Ensure outgoing email is enabled in settings
        EmailSettingService::updateSettings([
            'mail_enabled' => 1,
            'mail_host' => 'mail.mama-bazar.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_username' => 'contact@mama-bazar.com',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'email_require_registration_email' => 1,
            'email_verification_enforced' => 1,
        ]);

        config(['email_system.queue_connection' => 'sync']);
        Mail::fake();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Panel & Settings Persistence
    |--------------------------------------------------------------------------
    */

    public function test_admin_automation_panel_requires_admin_authentication(): void
    {
        $response = $this->get(route('admin.email.automation'));
        $response->assertRedirect();

        $response = $this->actingAs($this->admin)->get(route('admin.email.automation'));
        $response->assertStatus(200);
        $response->assertSee('Account Verification OTP');
        $response->assertSee('Order Confirmation');
        $response->assertSee('Order Shipped');
        $response->assertSee('Review Invitation');
    }

    public function test_admin_can_persist_automation_checkbox_states(): void
    {
        $payload = [
            'email_auto_account_otp' => '1',
            'email_auto_welcome' => '1',
            'email_auto_order_created' => '1',
            'email_auto_order_shipped' => '0',
            'email_auto_review_invitation' => '1',
            'email_admin_notification_address' => 'alerts@mama-bazar.com',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.email.automation.update'), $payload);
        $response->assertSessionHas('success');

        $this->assertTrue(EmailSettingService::isAutomationEnabled('account_otp'));
        $this->assertTrue(EmailSettingService::isAutomationEnabled('welcome'));
        $this->assertTrue(EmailSettingService::isAutomationEnabled('order_created'));
        $this->assertFalse(EmailSettingService::isAutomationEnabled('order_shipped'));
        $this->assertTrue(EmailSettingService::isAutomationEnabled('review_invitation'));
        $this->assertEquals('alerts@mama-bazar.com', EmailSettingService::adminNotificationAddress());
    }

    /*
    |--------------------------------------------------------------------------
    | Account & Auth Automations
    |--------------------------------------------------------------------------
    */

    public function test_account_registration_otp_generation_and_verification(): void
    {
        EmailSettingService::updateSettings(['email_auto_account_otp' => 1]);

        $regData = [
            'name' => 'John Doe',
            'phone' => '01700000001',
            'email' => 'john.doe@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $response = $this->post(route('register.submit'), $regData);
        $response->assertRedirect(route('auth.verify-otp'));

        // OTP is hashed in DB, never plaintext
        $otp = EmailOtp::where('email', 'john.doe@example.com')->where('type', EmailOtpService::TYPE_ACCOUNT)->first();
        $this->assertNotNull($otp);
        $this->assertNotEmpty($otp->otp_hash);
        $this->assertNotEquals('123456', $otp->otp_hash);

        // Check delivery logged
        $log = EmailLog::where('recipient_email', 'john.doe@example.com')->where('email_type', 'otp')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);

        // Verification with invalid code fails
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->actingAs($user);

        $failResponse = $this->post(route('auth.verify-otp.submit'), ['code' => '999999']);
        $failResponse->assertSessionHas('error');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_account_otp_is_suppressed_when_automation_disabled(): void
    {
        EmailSettingService::updateSettings(['email_auto_account_otp' => 0]);

        $result = EmailOtpService::issueAndSend('suppressed@example.com', EmailOtpService::TYPE_ACCOUNT, 'Test User');
        $this->assertFalse($result['success']);
        $this->assertTrue($result['disabled'] ?? false);

        $log = EmailLog::where('recipient_email', 'suppressed@example.com')->first();
        $this->assertNull($log);
    }

    public function test_welcome_email_triggered_upon_successful_verification_not_registration(): void
    {
        EmailSettingService::updateSettings([
            'email_auto_account_otp' => 1,
            'email_auto_welcome' => 1,
        ]);

        $user = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '01700000002',
            'password' => Hash::make('secret123'),
            'role' => 'customer',
        ]);

        // Prior to verification, no welcome email
        $this->assertNull(EmailLog::where('recipient_email', 'jane.smith@example.com')->where('template_key', 'welcome_email')->first());

        // Generate OTP and verify
        $otpResult = EmailOtpService::generateOtp($user->email, EmailOtpService::TYPE_ACCOUNT, $user->id);
        $this->assertTrue($otpResult['success']);
        $code = $otpResult['code'];

        $this->actingAs($user);
        $response = $this->post(route('auth.verify-otp.submit'), ['code' => $code]);
        $response->assertRedirect(route('home'));

        $this->assertNotNull($user->fresh()->email_verified_at);

        // Welcome email sent after verification
        $welcomeLog = EmailLog::where('recipient_email', 'jane.smith@example.com')->where('template_key', 'welcome_email')->first();
        $this->assertNotNull($welcomeLog);
        $this->assertEquals('sent', $welcomeLog->status);

        // Subsequent verification attempts do not resend welcome email (dedupe check)
        $this->post(route('auth.verify-otp.submit'), ['code' => $code]);
        $this->assertEquals(1, EmailLog::where('recipient_email', 'jane.smith@example.com')->where('template_key', 'welcome_email')->count());
    }

    public function test_password_reset_flow_and_security_notice(): void
    {
        EmailSettingService::updateSettings([
            'email_auto_password_reset' => 1,
            'email_auto_security_notice' => 1,
        ]);

        $user = User::create([
            'name' => 'Alice Reset',
            'email' => 'alice@example.com',
            'phone' => '01700000003',
            'password' => Hash::make('oldpassword'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        // Request password reset
        $response = $this->post(route('auth.forgot-password.submit'), ['email' => 'alice@example.com']);
        $response->assertSessionHas('success');

        $resetLog = EmailLog::where('recipient_email', 'alice@example.com')->where('template_key', 'password_reset')->first();
        $this->assertNotNull($resetLog);
        $this->assertEquals('sent', $resetLog->status);

        // User received reset token hash
        $this->assertNotNull($user->fresh()->reset_token_hash);

        // Reset password with a fresh valid token
        $token = str_repeat('a', 64);
        $user->forceFill([
            'reset_token_hash' => hash('sha256', $token),
            'reset_token_expires_at' => now()->addMinutes(60),
        ])->save();

        $resetResponse = $this->post(route('auth.reset-password.submit'), [
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $resetResponse->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));

        // Security notice was sent
        $secLog = EmailLog::where('recipient_email', 'alice@example.com')->where('template_key', 'account_security_notice')->first();
        $this->assertNotNull($secLog);
        $this->assertEquals('sent', $secLog->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Order & Payment Automations
    |--------------------------------------------------------------------------
    */

    public function test_order_created_confirmation_email(): void
    {
        EmailSettingService::updateSettings(['email_auto_order_created' => 1]);

        $order = Order::create([
            'order_id' => 'BS-ORDTEST01',
            'email' => 'buyer@example.com',
            'customer_name' => 'Buyer One',
            'phone' => '01700000004',
            'address' => '456 Market St',
            'shipping_cost' => 80,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 1500,
            'total_price' => 1580,
        ]);

        $log = EmailLog::where('order_id', $order->id)->where('template_key', 'order_confirmation')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);
        $this->assertEquals('buyer@example.com', $log->recipient_email);
    }

    public function test_order_created_suppressed_when_automation_disabled(): void
    {
        EmailSettingService::updateSettings(['email_auto_order_created' => 0]);

        $order = Order::create([
            'order_id' => 'BS-ORDTEST02',
            'email' => 'buyer2@example.com',
            'customer_name' => 'Buyer Two',
            'phone' => '01700000005',
            'address' => '789 Market St',
            'shipping_cost' => 80,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 2000,
            'total_price' => 2080,
        ]);

        $log = EmailLog::where('order_id', $order->id)->where('template_key', 'order_confirmation')->first();
        $this->assertNull($log);
    }

    public function test_payment_confirmed_email_triggers_only_on_verified_payment(): void
    {
        EmailSettingService::updateSettings(['email_auto_payment_confirmed' => 1]);

        $order = Order::create([
            'order_id' => 'BS-PAYTEST01',
            'email' => 'payer@example.com',
            'customer_name' => 'Payer One',
            'phone' => '01700000006',
            'address' => '100 Gateway Ave',
            'shipping_cost' => 60,
            'payment_method' => 'bkash',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 500,
            'total_price' => 560,
        ]);

        // Prior to payment confirmation, no payment email
        $this->assertNull(EmailLog::where('order_id', $order->id)->where('template_key', 'payment_confirmation')->first());

        // Update payment_status to 'success'
        $order->update(['payment_status' => 'success']);

        $log = EmailLog::where('order_id', $order->id)->where('template_key', 'payment_confirmation')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);
    }

    public function test_invoice_pdf_attachment_respects_setting(): void
    {
        // 1. When invoice_pdf is enabled, invoice is rendered & attached
        EmailSettingService::updateSettings([
            'email_auto_order_created' => 1,
            'email_auto_invoice_pdf' => 1,
        ]);

        $order = Order::create([
            'order_id' => 'BS-INVTEST01',
            'email' => 'invoicereceiver@example.com',
            'customer_name' => 'Invoice Receiver',
            'phone' => '01700000007',
            'address' => '200 Finance Rd',
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'payment_status' => 'success',
            'status' => 'confirmed',
            'subtotal' => 1200,
            'total_price' => 1260,
        ]);

        $product = Product::create([
            'title' => 'Test Product',
            'slug' => 'test-product',
            'price' => 1200,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => 'Test Product',
            'quantity' => 1,
            'price' => 1200,
        ]);

        $log = EmailLog::where('order_id', $order->id)->where('template_key', 'order_confirmation')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);

        // 2. When invoice_pdf is disabled, no invoice attached
        EmailSettingService::updateSettings([
            'email_auto_order_created' => 1,
            'email_auto_invoice_pdf' => 0,
        ]);

        $order2 = Order::create([
            'order_id' => 'BS-INVTEST02',
            'email' => 'noinvoice@example.com',
            'customer_name' => 'No Invoice Customer',
            'phone' => '01700000008',
            'address' => '201 Finance Rd',
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'payment_status' => 'success',
            'status' => 'confirmed',
            'subtotal' => 800,
            'total_price' => 860,
        ]);

        $log2 = EmailLog::where('order_id', $order2->id)->where('template_key', 'order_confirmation')->first();
        $this->assertNotNull($log2);
        $this->assertEquals('sent', $log2->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Order Status Transition Automations
    |--------------------------------------------------------------------------
    */

    public function test_order_status_transitions_trigger_expected_templates(): void
    {
        EmailSettingService::updateSettings([
            'email_auto_order_confirmed' => 1,
            'email_auto_order_processing' => 1,
            'email_auto_order_shipped' => 1,
            'email_auto_order_out_for_delivery' => 1,
            'email_auto_order_delivered' => 1,
            'email_auto_order_cancelled' => 1,
        ]);

        $order = Order::create([
            'order_id' => 'BS-STATUSTEST01',
            'email' => 'statuscustomer@example.com',
            'customer_name' => 'Status Customer',
            'phone' => '01700000009',
            'address' => '300 Delivery Lane',
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 900,
            'total_price' => 960,
        ]);

        // Transition: Confirmed
        $order->update(['status' => 'confirmed']);
        $this->assertNotNull(EmailLog::where('order_id', $order->id)->where('template_key', 'order_confirmed')->first());

        // Transition: Processing
        $order->update(['status' => 'processing']);
        $this->assertNotNull(EmailLog::where('order_id', $order->id)->where('template_key', 'order_processing')->first());

        // Transition: Shipped (with courier tracking)
        $order->update(['status' => 'shipped', 'courier_tracking_number' => 'STEADFAST-12345']);
        $shippedLog = EmailLog::where('order_id', $order->id)->where('template_key', 'order_shipped')->first();
        $this->assertNotNull($shippedLog);

        // Transition: Out for Delivery
        $order->update(['status' => 'out_for_delivery']);
        $this->assertNotNull(EmailLog::where('order_id', $order->id)->where('template_key', 'out_for_delivery')->first());

        // Transition: Delivered
        $order->update(['status' => 'delivered']);
        $this->assertNotNull(EmailLog::where('order_id', $order->id)->where('template_key', 'order_delivered')->first());

        // Repeated update with same status does NOT trigger duplicate email
        $logCount = EmailLog::where('order_id', $order->id)->where('template_key', 'order_delivered')->count();
        $order->update(['address' => '301 Delivery Lane Modified']);
        $this->assertEquals($logCount, EmailLog::where('order_id', $order->id)->where('template_key', 'order_delivered')->count());
    }

    public function test_disabled_order_status_automation_is_suppressed(): void
    {
        EmailSettingService::updateSettings([
            'email_auto_order_shipped' => 0,
        ]);

        $order = Order::create([
            'order_id' => 'BS-STATUSTEST02',
            'email' => 'status2@example.com',
            'customer_name' => 'Status Two',
            'phone' => '01700000010',
            'address' => '400 Delivery Lane',
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'processing',
            'subtotal' => 1100,
            'total_price' => 1160,
        ]);

        $order->update(['status' => 'shipped']);
        $this->assertNull(EmailLog::where('order_id', $order->id)->where('template_key', 'order_shipped')->first());
    }

    /*
    |--------------------------------------------------------------------------
    | Engagement Automations
    |--------------------------------------------------------------------------
    */

    public function test_contact_form_auto_reply_and_admin_alert(): void
    {
        EmailSettingService::updateSettings([
            'email_auto_contact_form' => 1,
            'email_auto_contact_admin' => 1,
            'email_admin_notification_address' => 'admin-inbox@mama-bazar.com',
        ]);

        $response = $this->post(route('contact.submit'), [
            'name' => 'Contact Sender',
            'phone' => '01700000011',
            'email' => 'sender@example.com',
            'message' => 'I have a question about delivery times.',
        ]);

        $response->assertSessionHas('success');

        // Customer auto-reply
        $replyLog = EmailLog::where('recipient_email', 'sender@example.com')->where('template_key', 'contact_form_notification')->first();
        $this->assertNotNull($replyLog);
        $this->assertEquals('sent', $replyLog->status);

        // Admin alert
        $adminLog = EmailLog::where('recipient_email', 'admin-inbox@mama-bazar.com')->where('template_key', 'contact_form_admin')->first();
        $this->assertNotNull($adminLog);
        $this->assertEquals('sent', $adminLog->status);
    }

    public function test_contact_form_admin_alert_works_without_customer_email(): void
    {
        EmailSettingService::updateSettings([
            'email_auto_contact_form' => 1,
            'email_auto_contact_admin' => 1,
            'email_admin_notification_address' => 'admin-inbox@mama-bazar.com',
        ]);

        $response = $this->post(route('contact.submit'), [
            'name' => 'Phone Only Contact',
            'phone' => '01700000012',
            'message' => 'Please call me back regarding my order.',
        ]);

        $response->assertSessionHas('success');

        // No auto-reply since no customer email
        $this->assertEquals(0, EmailLog::where('template_key', 'contact_form_notification')->count());

        // Admin alert still sent
        $adminLog = EmailLog::where('recipient_email', 'admin-inbox@mama-bazar.com')->where('template_key', 'contact_form_admin')->first();
        $this->assertNotNull($adminLog);
        $this->assertEquals('sent', $adminLog->status);
    }

    public function test_review_invitations_scheduled_command(): void
    {
        EmailSettingService::updateSettings(['email_auto_review_invitation' => 1]);

        $order = Order::create([
            'order_id' => 'BS-REVIEWTEST01',
            'email' => 'reviewer@example.com',
            'customer_name' => 'Reviewer One',
            'phone' => '01700000013',
            'address' => '500 Rating St',
            'shipping_cost' => 60,
            'payment_method' => 'cod',
            'payment_status' => 'success',
            'status' => 'delivered',
            'subtotal' => 700,
            'total_price' => 760,
        ]);

        // Insert into order_status_history as delivered 4 days ago
        DB::table('order_status_history')->insert([
            'order_id' => $order->id,
            'status' => 'delivered',
            'created_at' => now()->subDays(4),
        ]);

        Artisan::call('email:send-review-invitations');

        $log = EmailLog::where('order_id', $order->id)->where('template_key', 'review_invitation')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);

        // Re-running command does not create duplicate
        Artisan::call('email:send-review-invitations');
        $this->assertEquals(1, EmailLog::where('order_id', $order->id)->where('template_key', 'review_invitation')->count());
    }

    public function test_passwordless_login_otp_flow_and_disabled_state(): void
    {
        // 1. When disabled, route aborts with 404
        EmailSettingService::updateSettings(['email_auto_login_otp' => 0]);
        $response = $this->post(route('auth.login-otp.send'), ['email' => 'loginuser@example.com']);
        $response->assertStatus(404);

        // 2. When enabled, sends OTP and allows passwordless login
        EmailSettingService::updateSettings(['email_auto_login_otp' => 1]);

        $user = User::create([
            'name' => 'Passwordless User',
            'email' => 'loginuser@example.com',
            'phone' => '01700000014',
            'password' => Hash::make('secret123'),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $sendResponse = $this->post(route('auth.login-otp.send'), ['email' => 'loginuser@example.com']);
        $sendResponse->assertRedirect(route('auth.login-otp'));

        $log = EmailLog::where('recipient_email', 'loginuser@example.com')->where('template_key', 'login_otp')->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);

        $otp = EmailOtp::where('email', 'loginuser@example.com')->where('type', EmailOtpService::TYPE_LOGIN)->latest('id')->first();
        $this->assertNotNull($otp);

        // Verify with code
        $verifyResponse = $this->withSession(['login_otp_email' => 'loginuser@example.com'])
            ->post(route('auth.login-otp.verify'), ['code' => '999999']);
        $verifyResponse->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_api_password_change_triggers_security_notice(): void
    {
        EmailSettingService::updateSettings(['email_auto_security_notice' => 1]);

        $user = User::create([
            'name' => 'API Password User',
            'email' => 'apiuser@example.com',
            'phone' => '01700000015',
            'password' => Hash::make('oldpassword'),
            'role' => 'customer',
        ]);

        $jwt = JwtService::sign(['id' => $user->id, 'phone' => $user->phone, 'role' => $user->role]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$jwt])
            ->postJson('/api/users/change-password', [
                'oldPassword' => 'oldpassword',
                'newPassword' => 'brandnewpassword',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $log = EmailLog::where('recipient_email', 'apiuser@example.com')
            ->where('template_key', 'account_security_notice')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);
    }

    public function test_account_email_change_triggers_security_notice_to_old_email(): void
    {
        config(['email_system.otp.resend_cooldown_seconds' => 0]);
        EmailSettingService::updateSettings(['email_auto_security_notice' => 1]);

        $user = User::create([
            'name' => 'Email Changer',
            'email' => 'original@example.com',
            'phone' => '01700000016',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // Step 1: Request change
        $reqResponse = $this->post(route('account.email.change'), [
            'new_email' => 'updated@example.com',
            'current_password' => 'password123',
        ]);
        $reqResponse->assertSessionHas('success');

        $otp = EmailOtp::where('email', 'updated@example.com')->where('type', EmailOtpService::TYPE_EMAIL_CHANGE)->latest('id')->first();
        $this->assertNotNull($otp);

        // Generate known code for confirmation test
        $res = EmailOtpService::generateOtp('updated@example.com', EmailOtpService::TYPE_EMAIL_CHANGE, $user->id);
        $this->assertTrue($res['success']);

        // Step 2: Confirm change
        $confirmResponse = $this->withSession(['email_change_pending' => 'updated@example.com'])
            ->post(route('account.email.confirm'), [
                'code' => $res['code'],
            ]);
        $confirmResponse->assertSessionHas('success');

        $this->assertEquals('updated@example.com', $user->fresh()->email);

        // Security notice was sent to the OLD email address!
        $log = EmailLog::where('recipient_email', 'original@example.com')
            ->where('template_key', 'account_security_notice')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);
    }
}
