<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_authorized_admin_can_update_smtp_settings_directly(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'log',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@mamabazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 30,
        ]);

        $response->assertRedirect(route('admin.email.settings'));
        $response->assertSessionHas('success');
    }

    public function test_connection_testing_is_accessible_to_authorized_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.email.settings'))
            ->post(route('admin.email.settings.test-connection'));

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertRedirect(route('admin.email.settings'));
    }

    public function test_dns_checking_is_accessible_to_authorized_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.email.settings'))
            ->post(route('admin.email.settings.check-dns'));

        $this->assertNotEquals(403, $response->getStatusCode());
        $response->assertRedirect(route('admin.email.settings'));
    }

    public function test_unauthenticated_users_cannot_access_smtp_settings(): void
    {
        $response = $this->get(route('admin.email.settings'));

        $this->assertTrue(in_array($response->getStatusCode(), [302, 401]));
        $response->assertRedirect();
    }

    public function test_unauthorized_non_admin_cannot_access_smtp_settings(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($customer)->get(route('admin.email.settings'));

        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
    }
}
