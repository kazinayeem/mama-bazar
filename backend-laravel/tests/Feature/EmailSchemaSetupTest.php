<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EmailSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailSchemaSetupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        EmailSchema::flush();

        $this->admin = User::create([
            'name' => 'Admin',
            'phone' => '01700000001',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_email_dashboard_shows_setup_instructions_when_migrations_are_pending(): void
    {
        Schema::drop('email_suppressions');

        $this->actingAs($this->admin)
            ->get(route('admin.email.dashboard'))
            ->assertStatus(503)
            ->assertSee('php artisan migrate --force')
            ->assertSee('table email_suppressions');
    }

    public function test_email_dashboard_loads_when_schema_is_ready(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.email.dashboard'))
            ->assertOk()
            ->assertDontSee('php artisan migrate --force');
    }
}
