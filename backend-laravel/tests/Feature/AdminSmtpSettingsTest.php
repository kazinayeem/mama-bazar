<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\EmailSettingService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmtpSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();
    }

    public function test_authorized_admin_can_access_smtp_settings_directly_without_pin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.email.settings'));

        $response->assertStatus(200);
        $response->assertSee('Outgoing Mail Server (SMTP)');
        $response->assertSee('Save settings');
        $response->assertDontSee('SMTP Settings Locked');
        $response->assertDontSee('Unlock SMTP Settings');
        $response->assertDontSee('6969');
        $response->assertDontSee('Enter your security PIN');
    }

    public function test_port_456_is_identified_as_nonstandard_and_flags_warning(): void
    {
        // 1. Service methods verify port 456 is nonstandard and a typo for 465
        $this->assertFalse(EmailSettingService::isStandardPort(456));
        $this->assertEquals(465, EmailSettingService::isTypoPort(456));
        $this->assertTrue(EmailSettingService::isStandardPort(465));
        $this->assertTrue(EmailSettingService::isStandardPort(587));

        // 2. When saved port is 456, view displays warning
        SiteSetting::updateOrCreate(['key' => 'mail_port'], ['value' => '456']);
        EmailSettingService::clearCache();

        $response = $this->actingAs($this->admin)->get(route('admin.email.settings'));
        $response->assertStatus(200);
        $response->assertSee('Non-Standard Port Detected: Port 456');
        $response->assertSee('Apply Recommended Port 465 (SSL/TLS)');
    }

    public function test_ssl_recommends_port_465_and_starttls_recommends_port_587(): void
    {
        // 2. SSL/TLS recommends port 465
        $this->assertEquals(465, EmailSettingService::recommendedPortForEncryption('ssl'));

        // 3. STARTTLS recommends port 587
        $this->assertEquals(587, EmailSettingService::recommendedPortForEncryption('tls'));

        // Inverse mapping
        $this->assertEquals('ssl', EmailSettingService::recommendedEncryptionForPort(465));
        $this->assertEquals('tls', EmailSettingService::recommendedEncryptionForPort(587));
        $this->assertEquals('tls', EmailSettingService::recommendedEncryptionForPort(2525));
        $this->assertEquals('none', EmailSettingService::recommendedEncryptionForPort(25));
    }

    public function test_automatic_mode_chooses_settings_based_on_selected_provider(): void
    {
        // 4. Automatic mode chooses settings based on selected provider
        // Save Gmail preset
        $response = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_config_mode' => 'auto',
            'mail_provider' => 'gmail',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
        ]);

        $response->assertRedirect(route('admin.email.settings'));
        $this->assertEquals('auto', EmailSettingService::get('mail_config_mode'));
        $this->assertEquals('gmail', EmailSettingService::get('mail_provider'));
        $this->assertEquals('smtp.gmail.com', EmailSettingService::get('mail_host'));
        $this->assertEquals(587, (int) EmailSettingService::get('mail_port'));
        $this->assertEquals('tls', EmailSettingService::get('mail_encryption'));

        // Save cPanel preset
        $response2 = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_config_mode' => 'auto',
            'mail_provider' => 'cpanel',
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.mama-bazar.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
        ]);

        $response2->assertRedirect(route('admin.email.settings'));
        $this->assertEquals('cpanel', EmailSettingService::get('mail_provider'));
        $this->assertEquals(465, (int) EmailSettingService::get('mail_port'));
        $this->assertEquals('ssl', EmailSettingService::get('mail_encryption'));
    }

    public function test_manual_mode_allows_supported_custom_configurations(): void
    {
        // 5. Manual mode allows supported custom configurations
        $response = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_config_mode' => 'manual',
            'mail_provider' => 'other',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.sendgrid.net',
            'mail_port' => 2525,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar Custom',
            'mail_timeout' => 20,
        ]);

        $response->assertRedirect(route('admin.email.settings'));
        $this->assertEquals('manual', EmailSettingService::get('mail_config_mode'));
        $this->assertEquals('smtp.sendgrid.net', EmailSettingService::get('mail_host'));
        $this->assertEquals(2525, (int) EmailSettingService::get('mail_port'));
        $this->assertEquals('tls', EmailSettingService::get('mail_encryption'));
        $this->assertEquals(20, (int) EmailSettingService::get('mail_timeout'));
    }

    public function test_incorrect_combinations_produce_clear_validation_message(): void
    {
        // 6. Incorrect combinations produce a clear validation message
        // Port 465 with TLS
        $response1 = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.example.com',
            'mail_port' => 465,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
        ]);
        $response1->assertSessionHasErrors('mail_port');

        // Port 587 with SSL
        $response2 = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.example.com',
            'mail_port' => 587,
            'mail_encryption' => 'ssl',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
        ]);
        $response2->assertSessionHasErrors('mail_port');

        // Port 465 with None
        $response3 = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.example.com',
            'mail_port' => 465,
            'mail_encryption' => 'none',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
        ]);
        $response3->assertSessionHasErrors('mail_port');
    }

    public function test_connection_refused_and_authentication_failures_are_reported_accurately(): void
    {
        // 7. Connection refused and authentication failures reported accurately
        $config456 = ['host' => 'mail.mama-bazar.com', 'port' => 456, 'encryption' => 'ssl'];
        $refusedDiag = EmailSettingService::diagnoseError('Connection refused (61)', $config456);
        $this->assertEquals('connection_refused', $refusedDiag['category']);
        $this->assertEquals('Connection refused', $refusedDiag['category_label']);
        $this->assertStringContainsString('465', $refusedDiag['diagnostic']);

        $configGmail = ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'test@gmail.com'];
        $authDiag = EmailSettingService::diagnoseError('535 5.7.8 Authentication failed', $configGmail);
        $this->assertEquals('auth_failure', $authDiag['category']);
        $this->assertEquals('SMTP authentication failure', $authDiag['category_label']);
        $this->assertStringContainsString('App Password', $authDiag['diagnostic']);

        $timeoutDiag = EmailSettingService::diagnoseError('Operation timed out', ['host' => 'mail.example.com', 'port' => 465]);
        $this->assertEquals('timed_out', $timeoutDiag['category']);
        $this->assertEquals('Connection timed out', $timeoutDiag['category_label']);

        $dnsDiag = EmailSettingService::diagnoseError('getaddrinfo failed for host', ['host' => 'invalid.host.com', 'port' => 465]);
        $this->assertEquals('dns_failure', $dnsDiag['category']);
        $this->assertEquals('DNS resolution failure', $dnsDiag['category_label']);

        $tlsDiag = EmailSettingService::diagnoseError('SSL handshake failed', ['host' => 'mail.example.com', 'port' => 465, 'encryption' => 'tls']);
        $this->assertEquals('tls_failure', $tlsDiag['category']);
        $this->assertEquals('TLS/SSL handshake failure', $tlsDiag['category_label']);
    }

    public function test_ssrf_protection_blocks_private_and_reserved_ips(): void
    {
        // SSRF protection blocks RFC1918 and loopback/link-local metadata
        $checkPrivate = EmailSettingService::validateHostSecurity('10.0.0.1');
        $this->assertFalse($checkPrivate['allowed']);
        $this->assertEquals('security_blocked', $checkPrivate['category']);

        $checkMetadata = EmailSettingService::validateHostSecurity('169.254.169.254');
        $this->assertFalse($checkMetadata['allowed']);

        $checkPublic = EmailSettingService::validateHostSecurity('mail.mama-bazar.com');
        $this->assertTrue($checkPublic['allowed']);
    }

    public function test_existing_saved_credentials_are_preserved(): void
    {
        // 8. Existing saved credentials are preserved when password field is blank
        EmailSettingService::updateSettings([
            'mail_password' => 'SuperSecretSmtpKey123',
            'mail_username' => 'saveduser@mama-bazar.com',
        ]);

        $this->assertEquals('SuperSecretSmtpKey123', EmailSettingService::getDecryptedPassword());

        // Update other fields with empty password
        $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.mama-bazar.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_username' => 'saveduser@mama-bazar.com',
            'mail_password' => '', // blank
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
        ]);

        // Decrypted password still preserved
        $this->assertEquals('SuperSecretSmtpKey123', EmailSettingService::getDecryptedPassword());
    }

    public function test_unauthorized_users_cannot_test_or_modify_smtp_settings(): void
    {
        // 9. Unauthorized users cannot test or modify SMTP settings
        // Unauthenticated
        $this->post(route('admin.email.settings.update'), ['mail_port' => 465])
            ->assertRedirect();
        $this->post(route('admin.email.settings.test-connection'))
            ->assertRedirect();

        // Customer
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $this->actingAs($customer)->post(route('admin.email.settings.update'), ['mail_port' => 465])
            ->assertStatus(403);
        $this->actingAs($customer)->post(route('admin.email.settings.test-connection'))
            ->assertStatus(403);
    }

    public function test_smtp_config_generates_valid_mailer_configuration(): void
    {
        // 10. Outgoing configuration generates valid settings
        EmailSettingService::updateSettings([
            'mail_host' => 'mail.mama-bazar.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_timeout' => 15,
        ]);

        $config = EmailSettingService::smtpConfig();
        $this->assertEquals('smtp', $config['transport']);
        $this->assertEquals('smtps', $config['scheme']);
        $this->assertEquals(465, $config['port']);
        $this->assertEquals(15, $config['timeout']);
        $this->assertEquals('mail.mama-bazar.com', $config['host']);

        // Switch to port 587
        EmailSettingService::updateSettings([
            'mail_port' => 587,
            'mail_encryption' => 'tls',
        ]);

        $config587 = EmailSettingService::smtpConfig();
        $this->assertEquals('smtp', $config587['scheme']);
        $this->assertEquals(587, $config587['port']);
        $this->assertTrue($config587['require_tls']);
    }

    public function test_probe_ports_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.email.settings.probe-ports'), [
            'mail_host' => 'mail.mama-bazar.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'host',
            'ports',
            'open_ports',
        ]);
    }

    public function test_password_is_stored_encrypted_and_never_exposed_in_display_or_api(): void
    {
        // 1. Password stored encrypted in database
        EmailSettingService::updateSettings([
            'mail_password' => 'MySecretPass@9988',
        ]);

        $rawRow = SiteSetting::where('key', 'mail_password')->first();
        $this->assertNotNull($rawRow);
        $this->assertNotEquals('MySecretPass@9988', $rawRow->value);
        $this->assertTrue(EmailSettingService::isCiphertextPayload($rawRow->value));
        $this->assertTrue(EmailSettingService::isEncrypted($rawRow->value));

        // 2. Server decrypts correctly
        $this->assertEquals('MySecretPass@9988', EmailSettingService::getDecryptedPassword());

        // 3. Password never exposed in forDisplay()
        $display = EmailSettingService::forDisplay();
        $this->assertArrayNotHasKey('mail_password', $display);
        $this->assertEquals('database', $display['password_source']);

        // 4. Password never rendered in HTML page
        $response = $this->actingAs($this->admin)->get(route('admin.email.settings'));
        $response->assertStatus(200);
        $response->assertDontSee('MySecretPass@9988');
    }

    public function test_show_hide_password_toggle_markup_and_accessibility(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.email.settings'));
        $response->assertStatus(200);

        // Check for show/hide toggle button with accessible labels
        $response->assertSee('showPassword ? \'Hide SMTP password\' : \'Show SMTP password\'', false);
        $response->assertSee('showPassword ? \'text\' : \'password\'', false);
        $response->assertSee('x-data="{ showPassword: false }"', false);
    }

    public function test_updating_new_password_encrypts_and_replaces_old_credential(): void
    {
        EmailSettingService::updateSettings([
            'mail_password' => 'InitialPassword1',
        ]);
        $this->assertEquals('InitialPassword1', EmailSettingService::getDecryptedPassword());

        // Update with new password
        $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.mama-bazar.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_username' => 'user@mama-bazar.com',
            'mail_password' => 'BrandNewPassword2',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
        ]);

        $this->assertEquals('BrandNewPassword2', EmailSettingService::getDecryptedPassword());
        $raw = SiteSetting::where('key', 'mail_password')->value('value');
        $this->assertNotEquals('BrandNewPassword2', $raw);
        $this->assertTrue(EmailSettingService::isEncrypted($raw));
    }

    public function test_explicit_removal_of_saved_password(): void
    {
        EmailSettingService::updateSettings([
            'mail_password' => 'ToRemovePass123',
        ]);
        $this->assertNotEmpty(SiteSetting::where('key', 'mail_password')->value('value'));

        // Removing password requires explicit clear_password checkbox
        $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.mama-bazar.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 15,
            'clear_password' => '1',
        ]);

        $this->assertNull(SiteSetting::where('key', 'mail_password')->first());
        $this->assertEquals('', EmailSettingService::getDecryptedPassword());
    }

    public function test_legacy_plaintext_password_migration_and_no_double_encryption(): void
    {
        // Simulate legacy plaintext stored in database
        SiteSetting::updateOrCreate(['key' => 'mail_password'], ['value' => 'raw_legacy_secret_456']);
        EmailSettingService::clearCache();

        $rawBefore = SiteSetting::where('key', 'mail_password')->value('value');
        $this->assertEquals('raw_legacy_secret_456', $rawBefore);
        $this->assertFalse(EmailSettingService::isCiphertextPayload($rawBefore));

        // Loading settings or decrypting migrates plaintext automatically
        $decrypted = EmailSettingService::getDecryptedPassword();
        $this->assertEquals('raw_legacy_secret_456', $decrypted);

        $rawAfter = SiteSetting::where('key', 'mail_password')->value('value');
        $this->assertNotEquals('raw_legacy_secret_456', $rawAfter);
        $this->assertTrue(EmailSettingService::isCiphertextPayload($rawAfter));
        $this->assertTrue(EmailSettingService::isEncrypted($rawAfter));

        // Re-saving with already encrypted ciphertext does NOT double-encrypt
        EmailSettingService::updateSettings([
            'mail_password' => $rawAfter,
        ]);
        $rawReSaved = SiteSetting::where('key', 'mail_password')->value('value');
        $this->assertEquals($rawAfter, $rawReSaved);
        $this->assertEquals('raw_legacy_secret_456', EmailSettingService::getDecryptedPassword());
    }

    public function test_encryption_and_decryption_survive_cache_clear_and_restart(): void
    {
        EmailSettingService::updateSettings([
            'mail_password' => 'PersistentSmtpPass!777',
        ]);

        // Clear cache and in-memory state
        EmailSettingService::clearCache();

        $this->assertEquals('PersistentSmtpPass!777', EmailSettingService::getDecryptedPassword());
        $this->assertEquals('database', EmailSettingService::passwordSource());
    }
}
