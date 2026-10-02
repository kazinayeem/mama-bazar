<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailSettingService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmtpDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = User::where('role', 'admin')->first();
    }

    public function test_normalize_encryption_rules(): void
    {
        // Port 465 must always normalize to implicit SSL (unless explicitly 'none')
        $this->assertEquals('ssl', EmailSettingService::normalizeEncryption('ssl', 465));
        $this->assertEquals('ssl', EmailSettingService::normalizeEncryption('tls', 465));
        $this->assertEquals('ssl', EmailSettingService::normalizeEncryption('', 465));
        $this->assertEquals('none', EmailSettingService::normalizeEncryption('none', 465));

        // Port 587 must always normalize to STARTTLS (unless explicitly 'none')
        $this->assertEquals('tls', EmailSettingService::normalizeEncryption('tls', 587));
        $this->assertEquals('tls', EmailSettingService::normalizeEncryption('ssl', 587));
        $this->assertEquals('tls', EmailSettingService::normalizeEncryption('', 587));
        $this->assertEquals('none', EmailSettingService::normalizeEncryption('none', 587));

        // Custom ports
        $this->assertEquals('tls', EmailSettingService::normalizeEncryption('tls', 2525));
        $this->assertEquals('none', EmailSettingService::normalizeEncryption('', 25));
    }

    public function test_diagnose_command_executes_successfully(): void
    {
        $this->artisan('smtp:diagnose', ['--timeout' => 2])
            ->assertExitCode(0)
            ->expectsOutputToContain('MAMA BAZAR PRODUCTION SMTP CONNECTIVITY DIAGNOSTICS')
            ->expectsOutputToContain('ACTIVE CONFIGURATION OVERVIEW')
            ->expectsOutputToContain('PHP ENVIRONMENT & SOCKET CAPABILITIES');
    }

    public function test_test_connection_handles_log_and_sendmail_drivers(): void
    {
        EmailSettingService::updateSettings(['mail_mailer' => 'log']);
        $logResult = EmailSettingService::testConnection();
        $this->assertTrue($logResult['success']);
        $this->assertStringContainsString('log', $logResult['message']);

        EmailSettingService::updateSettings(['mail_mailer' => 'sendmail']);
        $sendmailResult = EmailSettingService::testConnection();
        // Returns result based on system sendmail availability
        $this->assertArrayHasKey('success', $sendmailResult);
        $this->assertArrayHasKey('message', $sendmailResult);
    }

    public function test_diagnose_error_identifies_cpanel_restrictions_and_gmail_passwords(): void
    {
        $config = ['host' => 'smtp.gmail.com', 'port' => 465, 'username' => 'test@gmail.com'];

        $refusedDiag = EmailSettingService::diagnoseError('Connection refused (111)', $config);
        $this->assertEquals('connection_refused', $refusedDiag['type']);
        $this->assertStringContainsString('SMTP Restrictions', $refusedDiag['diagnostic']);
        $this->assertStringContainsString('sendmail', $refusedDiag['diagnostic']);

        $authDiag = EmailSettingService::diagnoseError('535 5.7.8 Authentication failed', $config);
        $this->assertEquals('auth_failure', $authDiag['type']);
        $this->assertStringContainsString('App Password', $authDiag['diagnostic']);
    }
}
