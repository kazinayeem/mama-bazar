<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();
    }

    public function test_authenticated_admin_can_view_change_password_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.profile.password'));

        $response->assertStatus(200);
        $response->assertSee('Change Admin Password');
        $response->assertSee('Current Password');
        $response->assertSee('New Password');
        $response->assertSee('Confirm New Password');
    }

    public function test_unauthenticated_user_cannot_access_change_password_form(): void
    {
        $response = $this->get(route('admin.profile.password'));

        $response->assertRedirect(route('login'));
    }

    public function test_correct_current_password_allows_password_to_change(): void
    {
        // Admin default password in AdminSeeder is 'ChangeMe123!' or seeded password
        // Let's set a known password for predictability
        $this->admin->forceFill([
            'password' => Hash::make('CurrentSecret123!'),
        ])->save();

        $response = $this->actingAs($this->admin)->post(route('admin.profile.password.update'), [
            'current_password' => 'CurrentSecret123!',
            'password' => 'BrandNewSecret2026!',
            'password_confirmation' => 'BrandNewSecret2026!',
        ]);

        $response->assertRedirect(route('admin.profile.password'));
        $response->assertSessionHas('success', 'Password changed successfully.');

        $this->admin->refresh();
        $this->assertTrue(Hash::check('BrandNewSecret2026!', $this->admin->password));
        $this->assertFalse(Hash::check('CurrentSecret123!', $this->admin->password));
    }

    public function test_incorrect_current_password_is_rejected(): void
    {
        $this->admin->forceFill([
            'password' => Hash::make('CurrentSecret123!'),
        ])->save();

        $response = $this->actingAs($this->admin)->from(route('admin.profile.password'))->post(route('admin.profile.password.update'), [
            'current_password' => 'WrongPassword999!',
            'password' => 'BrandNewSecret2026!',
            'password_confirmation' => 'BrandNewSecret2026!',
        ]);

        $response->assertRedirect(route('admin.profile.password'));
        $response->assertSessionHasErrors('current_password');

        $this->admin->refresh();
        $this->assertTrue(Hash::check('CurrentSecret123!', $this->admin->password));
    }

    public function test_mismatched_new_passwords_are_rejected(): void
    {
        $this->admin->forceFill([
            'password' => Hash::make('CurrentSecret123!'),
        ])->save();

        $response = $this->actingAs($this->admin)->from(route('admin.profile.password'))->post(route('admin.profile.password.update'), [
            'current_password' => 'CurrentSecret123!',
            'password' => 'BrandNewSecret2026!',
            'password_confirmation' => 'DifferentConfirmation999!',
        ]);

        $response->assertRedirect(route('admin.profile.password'));
        $response->assertSessionHasErrors('password');

        $this->admin->refresh();
        $this->assertTrue(Hash::check('CurrentSecret123!', $this->admin->password));
    }

    public function test_weak_passwords_under_8_characters_are_rejected(): void
    {
        $this->admin->forceFill([
            'password' => Hash::make('CurrentSecret123!'),
        ])->save();

        $response = $this->actingAs($this->admin)->from(route('admin.profile.password'))->post(route('admin.profile.password.update'), [
            'current_password' => 'CurrentSecret123!',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ]);

        $response->assertRedirect(route('admin.profile.password'));
        $response->assertSessionHasErrors('password');

        $this->admin->refresh();
        $this->assertTrue(Hash::check('CurrentSecret123!', $this->admin->password));
    }

    public function test_setting_same_password_as_current_is_rejected(): void
    {
        $this->admin->forceFill([
            'password' => Hash::make('CurrentSecret123!'),
        ])->save();

        $response = $this->actingAs($this->admin)->from(route('admin.profile.password'))->post(route('admin.profile.password.update'), [
            'current_password' => 'CurrentSecret123!',
            'password' => 'CurrentSecret123!',
            'password_confirmation' => 'CurrentSecret123!',
        ]);

        $response->assertRedirect(route('admin.profile.password'));
        $response->assertSessionHasErrors('password');
    }

    public function test_new_password_works_for_subsequent_login_and_old_password_fails(): void
    {
        $this->admin->forceFill([
            'password' => Hash::make('CurrentSecret123!'),
        ])->save();

        // 1. Change password
        $this->actingAs($this->admin)->post(route('admin.profile.password.update'), [
            'current_password' => 'CurrentSecret123!',
            'password' => 'BrandNewSecret2026!',
            'password_confirmation' => 'BrandNewSecret2026!',
        ]);

        // 2. Logout
        $this->post(route('admin.logout'));

        $loginIdentifier = $this->admin->email ?: $this->admin->phone;

        // 3. Attempt login with old password -> must fail
        $failedLogin = $this->post(route('admin.login.submit'), [
            'login' => $loginIdentifier,
            'password' => 'CurrentSecret123!',
        ]);
        $failedLogin->assertSessionHasErrors('login');

        // 4. Attempt login with new password -> must succeed
        $successfulLogin = $this->post(route('admin.login.submit'), [
            'login' => $loginIdentifier,
            'password' => 'BrandNewSecret2026!',
        ]);
        $successfulLogin->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_one_admin_cannot_change_another_admins_password(): void
    {
        $otherAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('OtherAdminSecret123!'),
        ]);

        $this->admin->forceFill([
            'password' => Hash::make('CurrentSecret123!'),
        ])->save();

        // The endpoint does not accept target user ID; it strictly updates auth()->user()
        $this->actingAs($this->admin)->post(route('admin.profile.password.update'), [
            'current_password' => 'CurrentSecret123!',
            'password' => 'NewSecretForAdminOne!',
            'password_confirmation' => 'NewSecretForAdminOne!',
            'user_id' => $otherAdmin->id, // Malicious attempt to inject another user ID
        ]);

        $otherAdmin->refresh();
        $this->admin->refresh();

        // Other admin password is unchanged
        $this->assertTrue(Hash::check('OtherAdminSecret123!', $otherAdmin->password));
        $this->assertFalse(Hash::check('NewSecretForAdminOne!', $otherAdmin->password));

        // Current admin password is updated
        $this->assertTrue(Hash::check('NewSecretForAdminOne!', $this->admin->password));
    }
}
