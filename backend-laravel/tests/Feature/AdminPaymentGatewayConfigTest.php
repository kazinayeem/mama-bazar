<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminPaymentGatewayController;
use App\Models\AdminAuditLog;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\SslcommerzSettings;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPaymentGatewayConfigTest extends TestCase
{
    use RefreshDatabase;

    private const PIN = '481516';

    private const STORE_ID = 'mamabazar01live';

    private const STORE_PASSWORD = 'Sup3r-Secret-Gateway-Pass';

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        PaymentMethod::ensureDefaults();
        config(['services.payment_settings.unlock_pin' => self::PIN, 'services.payment_settings.unlock_minutes' => 10]);

        $this->superAdmin = User::where('role', 'admin')->firstOrFail();
    }

    private function adminWithoutLivePermission(): User
    {
        return User::create([
            'name' => 'Store Admin',
            'phone' => '01711000001',
            'email' => 'store-admin@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'custom_role' => 'ADMIN',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function staffWith(array $permissions): User
    {
        return User::create([
            'name' => 'Payments Staff',
            'phone' => '0171'.fake()->unique()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('Password123!'),
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions_json' => $permissions,
        ]);
    }

    private function unlockAs(User $user): void
    {
        $this->actingAs($user)
            ->from(route('admin.payment-methods.index'))
            ->post(route('admin.payment-gateway.unlock'), ['pin' => self::PIN])
            ->assertSessionHasNoErrors();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function configPayload(array $overrides = []): array
    {
        return array_merge([
            'store_id' => self::STORE_ID,
            'store_password' => self::STORE_PASSWORD,
            'mode' => 'sandbox',
            'currency' => 'BDT',
            'enabled' => '0',
        ], $overrides);
    }

    private function rawGatewayConfig(): string
    {
        return (string) DB::table('payment_methods')->where('code', 'sslcommerz')->value('config');
    }

    private function auditTrail(): string
    {
        return AdminAuditLog::where('action', 'like', 'payment_gateway.%')->pluck('details')->implode(' ');
    }

    public function test_page_shows_masked_status_without_exposing_secrets(): void
    {
        SslcommerzSettings::persist(['store_id' => self::STORE_ID, 'store_password' => self::STORE_PASSWORD]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.payment-methods.index'))->assertOk();

        $response->assertSee('SSLCOMMERZ Gateway')
            ->assertSee('Unlock Configuration')
            ->assertSee(SslcommerzSettings::mask(self::STORE_ID))
            ->assertSee(route('payment.sslcommerz.ipn'))
            ->assertDontSee(self::STORE_ID)
            ->assertDontSee(self::STORE_PASSWORD)
            ->assertDontSee(self::PIN)
            ->assertDontSee('store_password_encrypted')
            ->assertDontSee('name="store_password"', false);

        $method = PaymentMethod::where('code', 'sslcommerz')->firstOrFail();
        $this->assertArrayNotHasKey('gateway', (array) $method->toApiArray()['config']);
        $this->assertArrayNotHasKey('config', $method->toArray());
    }

    public function test_wrong_pins_are_rate_limited_and_audited_without_the_pin(): void
    {
        $admin = $this->adminWithoutLivePermission();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($admin)->post(route('admin.payment-gateway.unlock'), ['pin' => '0000'])
                ->assertSessionHasErrorsIn('gatewayUnlock', 'pin');
        }

        $this->actingAs($admin)->post(route('admin.payment-gateway.unlock'), ['pin' => self::PIN])
            ->assertSessionHasErrorsIn('gatewayUnlock', 'pin');

        $this->assertNull(session(AdminPaymentGatewayController::UNLOCK_SESSION_KEY));
        $this->assertSame(5, AdminAuditLog::where('action', 'payment_gateway.unlock_failed')->count());
        $this->assertSame(1, AdminAuditLog::where('action', 'payment_gateway.unlock_blocked')->count());
        $this->assertStringNotContainsString('0000', $this->auditTrail());
        $this->assertStringNotContainsString(self::PIN, $this->auditTrail());
    }

    public function test_configuration_cannot_be_saved_without_a_valid_unlock(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload())
            ->assertSessionHas('error');

        $this->assertFalse(SslcommerzSettings::load()->hasCredentials());

        $this->unlockAs($this->superAdmin);
        $this->travel(11)->minutes();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload())
            ->assertSessionHas('error');
        $this->assertFalse(SslcommerzSettings::load()->hasCredentials());
    }

    public function test_saved_credentials_are_encrypted_and_blank_password_keeps_existing_value(): void
    {
        $this->unlockAs($this->superAdmin);
        $this->actingAs($this->superAdmin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload())
            ->assertSessionHas('success');

        $raw = $this->rawGatewayConfig();
        $this->assertStringNotContainsString(self::STORE_PASSWORD, $raw);
        $this->assertStringNotContainsString(self::STORE_ID, $raw);
        $stored = json_decode($raw, true)['gateway'];
        $this->assertSame(self::STORE_PASSWORD, Crypt::decryptString($stored['store_password']));
        $this->assertNull(session(AdminPaymentGatewayController::UNLOCK_SESSION_KEY), 'Saving should lock the configuration again.');

        $this->unlockAs($this->superAdmin);
        $this->actingAs($this->superAdmin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload(['store_id' => '', 'store_password' => '']))
            ->assertSessionHas('success');

        $settings = SslcommerzSettings::load();
        $this->assertSame(self::STORE_ID, $settings->storeId());
        $this->assertSame(self::STORE_PASSWORD, $settings->storePassword());
        $this->assertSame(SslcommerzSettings::SOURCE_DATABASE, $settings->storePasswordSource);

        $audit = $this->auditTrail();
        $this->assertStringContainsString('store_password', $audit);
        $this->assertStringNotContainsString(self::STORE_PASSWORD, $audit);
        $this->assertStringNotContainsString(self::STORE_ID, $audit);
    }

    public function test_credentials_can_be_cleared_explicitly(): void
    {
        SslcommerzSettings::persist(['store_id' => self::STORE_ID, 'store_password' => self::STORE_PASSWORD]);

        $this->unlockAs($this->superAdmin);
        $this->actingAs($this->superAdmin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload(['store_id' => '', 'store_password' => '', 'clear_store_password' => '1']))
            ->assertSessionHas('success');

        $settings = SslcommerzSettings::load();
        $this->assertSame(self::STORE_ID, $settings->storeId());
        $this->assertNull($settings->storePassword());
    }

    public function test_generic_payment_method_endpoints_cannot_overwrite_or_enable_the_gateway(): void
    {
        $method = PaymentMethod::where('code', 'sslcommerz')->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->post(route('admin.payment-methods.toggle', $method->id))
            ->assertSessionHas('error');
        $this->assertFalse($method->fresh()->enabled);

        SslcommerzSettings::persist(['store_id' => self::STORE_ID, 'store_password' => self::STORE_PASSWORD]);
        $this->actingAs($this->superAdmin)->put(route('admin.payment-methods.update', $method->id), [
            'name' => 'Card / Online Gateway',
            'type' => 'online',
            'enabled' => '0',
            'config' => ['instructions' => 'Pay online', 'gateway' => ['store_password' => 'hijacked']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(self::STORE_PASSWORD, SslcommerzSettings::load()->storePassword());
        $this->assertSame('Pay online', $method->fresh()->config_array['instructions']);

        $this->actingAs($this->superAdmin)->delete(route('admin.payment-methods.destroy', $method->id))->assertSessionHas('error');
        $this->assertNotNull($method->fresh());
    }

    public function test_live_mode_requires_permission_confirmation_and_a_passing_test(): void
    {
        Http::fake([
            'securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php*' => Http::response(['APIConnect' => 'DONE', 'no_of_trans_found' => 0]),
        ]);

        $admin = $this->adminWithoutLivePermission();
        $this->unlockAs($admin);
        $this->actingAs($admin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload(['mode' => 'live', 'confirm_live' => '1']))
            ->assertSessionHas('error');
        $this->assertTrue(SslcommerzSettings::load()->sandbox);

        $this->unlockAs($this->superAdmin);
        $this->actingAs($this->superAdmin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload(['mode' => 'live']))
            ->assertSessionHasErrors('confirm_live');
        $this->assertTrue(SslcommerzSettings::load()->sandbox);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.payment-gateway.update'), $this->configPayload(['mode' => 'live', 'confirm_live' => '1', 'enabled' => '1']))
            ->assertSessionHas('error');
        $settings = SslcommerzSettings::load();
        $this->assertFalse($settings->sandbox);
        $this->assertFalse($settings->methodEnabled, 'Live gateway must stay disabled until a test passes.');
        $this->assertSame('live_unverified', $settings->readiness());

        $this->actingAs($this->superAdmin)->post(route('admin.payment-gateway.test'))->assertSessionHas('success');
        $this->assertTrue(SslcommerzSettings::load()->isLiveVerified());

        $method = PaymentMethod::where('code', 'sslcommerz')->firstOrFail();
        $this->actingAs($this->superAdmin)->post(route('admin.payment-methods.toggle', $method->id))->assertSessionMissing('error');
        $this->assertTrue(SslcommerzSettings::load()->isCheckoutReady());

        SslcommerzSettings::persist(['store_password' => 'rotated-password']);
        $this->assertFalse(SslcommerzSettings::load()->isCheckoutReady(), 'Changing credentials must invalidate the live verification.');
        $this->assertSame(1, AdminAuditLog::where('action', 'payment_gateway.live_denied')->count());
    }

    public function test_connection_test_is_non_charging_and_reports_rejections_safely(): void
    {
        Http::fake([
            'sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php*' => Http::response(['APIConnect' => 'INVALID_REQUEST']),
        ]);
        SslcommerzSettings::persist(['store_id' => self::STORE_ID, 'store_password' => self::STORE_PASSWORD]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.payment-gateway.test'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Check the Store ID and Store Password')
                && ! str_contains($message, self::STORE_PASSWORD));

        Http::assertSentCount(1);
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'gwprocess'));
        $settings = SslcommerzSettings::load();
        $this->assertFalse($settings->isVerified());
        $this->assertFalse($settings->lastTest['ok']);
        $this->assertStringNotContainsString(self::STORE_PASSWORD, $this->auditTrail());
    }

    public function test_gateway_actions_require_dedicated_permissions(): void
    {
        $viewer = $this->staffWith(['payment_methods.view', 'payment_methods.manage']);

        $this->actingAs($viewer)->get(route('admin.payment-methods.index'))
            ->assertOk()
            ->assertDontSee('Unlock Configuration')
            ->assertDontSee('Test Configuration');
        $this->actingAs($viewer)->post(route('admin.payment-gateway.unlock'), ['pin' => self::PIN])->assertForbidden();
        $this->actingAs($viewer)->put(route('admin.payment-gateway.update'), $this->configPayload())->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.payment-gateway.test'))->assertForbidden();

        $configurer = $this->staffWith(['payment_methods.view', 'payment_methods.configure']);
        $this->unlockAs($configurer);
        $this->actingAs($configurer)->get(route('admin.members.index'))->assertForbidden();
    }
}
