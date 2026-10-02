<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SmtpLockService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSmtpLockTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();
    }

    public function test_smtp_settings_page_is_locked_by_default(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.email.settings'));

        $response->assertStatus(200);
        $response->assertSee('SMTP Settings Locked');
        $response->assertSee('Unlock SMTP Settings');
        $response->assertSee('contact@bornosoft.bd');
        $this->assertFalse(SmtpLockService::isUnlocked());
    }

    public function test_direct_mutations_without_unlock_are_forbidden(): void
    {
        SmtpLockService::lock();

        $update = $this->actingAs($this->admin)->post(route('admin.email.settings.update'), [
            'mail_mailer' => 'log',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_timeout' => 30,
            'mail_encryption' => 'tls',
        ]);
        $update->assertStatus(403);

        $testConn = $this->actingAs($this->admin)->post(route('admin.email.settings.test-connection'));
        $testConn->assertStatus(403);

        $sendTest = $this->actingAs($this->admin)->post(route('admin.email.settings.send-test'), [
            'test_email' => 'admin@example.com',
        ]);
        $sendTest->assertStatus(403);

        $checkDns = $this->actingAs($this->admin)->post(route('admin.email.settings.check-dns'));
        $checkDns->assertStatus(403);
    }

    public function test_unlock_requires_pin(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => '',
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_unlock_fails_with_incorrect_pin(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => '0000',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid security PIN. Access denied.',
        ]);
        $this->assertFalse(SmtpLockService::isUnlocked());
    }

    public function test_unlock_succeeds_with_default_pin_6969(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => '6969',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'SMTP Settings unlocked successfully.',
        ]);
        $this->assertTrue(SmtpLockService::isUnlocked());
    }

    public function test_unlocked_session_allows_smtp_mutations(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => '6969',
            ]);

        $this->assertTrue(SmtpLockService::isUnlocked());

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.test-connection'));

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_manual_locking_clears_unlocked_state(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => '6969',
            ]);
        $this->assertTrue(SmtpLockService::isUnlocked());

        $lockRes = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.lock'));

        $lockRes->assertStatus(200);
        $lockRes->assertJson(['success' => true]);
        $this->assertFalse(SmtpLockService::isUnlocked());

        $testConn = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.test-connection'));
        $testConn->assertStatus(403);
    }

    public function test_reloading_settings_page_returns_to_locked_state(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => '6969',
            ]);
        $this->assertTrue(SmtpLockService::isUnlocked());

        $response = $this->actingAs($this->admin)->get(route('admin.email.settings'));
        $response->assertStatus(200);
        $this->assertFalse(SmtpLockService::isUnlocked());
    }

    public function test_session_expiration_locks_smtp_settings(): void
    {
        session([SmtpLockService::SESSION_KEY => now()->subMinutes(16)->timestamp]);

        $this->assertFalse(SmtpLockService::isUnlocked());

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.test-connection'));
        $response->assertStatus(403);
    }

    public function test_hashed_pin_support(): void
    {
        config(['email_system.smtp_pin' => Hash::make('secret-9999')]);

        $wrong = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => '6969',
            ]);
        $wrong->assertStatus(422);
        $this->assertFalse(SmtpLockService::isUnlocked());

        $correct = $this->actingAs($this->admin)
            ->postJson(route('admin.email.settings.unlock'), [
                'pin' => 'secret-9999',
            ]);
        $correct->assertStatus(200);
        $this->assertTrue(SmtpLockService::isUnlocked());
    }
}
