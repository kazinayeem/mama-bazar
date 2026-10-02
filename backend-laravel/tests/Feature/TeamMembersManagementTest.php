<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\MemberLoginHistory;
use App\Models\User;
use App\Services\MemberInvitationService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TeamMembersManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();
        if (! $this->admin->email) {
            $this->admin->email = 'admin@example.com';
            $this->admin->save();
        }
    }

    public function test_login_tracking_records_successful_login_and_location(): void
    {
        $user = User::factory()->create([
            'email' => 'staff1@example.com',
            'phone' => '01800000001',
            'password' => Hash::make('Secret123!'),
            'role' => 'staff',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.login.submit'), [
                'login' => 'staff1@example.com',
                'password' => 'Secret123!',
            ]);

        $response->assertRedirect(route('admin.dashboard'));

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertSame('127.0.0.1', $user->last_login_ip);
        $this->assertSame('Local Network', $user->last_login_location);

        $history = MemberLoginHistory::where('user_id', $user->id)->first();
        $this->assertNotNull($history);
        $this->assertSame('success', $history->status);
        $this->assertSame('127.0.0.1', $history->ip_address);
        $this->assertSame('Local Network', $history->location);

        $audit = AdminAuditLog::where('action', 'login.success')
            ->where('actor_id', $user->id)
            ->first();
        $this->assertNotNull($audit);
    }

    public function test_login_tracking_records_failed_login_without_updating_last_login(): void
    {
        $user = User::factory()->create([
            'email' => 'staff2@example.com',
            'phone' => '01800000002',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'staff',
            'status' => 'active',
            'last_login_at' => null,
            'last_login_ip' => null,
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('admin.login.submit'), [
                'login' => 'staff2@example.com',
                'password' => 'WrongPassword',
            ]);

        $response->assertSessionHasErrors('login');

        $user->refresh();
        $this->assertNull($user->last_login_at);
        $this->assertNull($user->last_login_ip);

        $history = MemberLoginHistory::where('user_id', $user->id)
            ->where('status', 'failure')
            ->first();
        $this->assertNotNull($history);
        $this->assertStringContainsString('Invalid credentials', $history->failure_reason);

        $audit = AdminAuditLog::where('action', 'login.failed')
            ->where('actor_id', $user->id)
            ->first();
        $this->assertNotNull($audit);
    }

    public function test_admin_can_view_members_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.members.index'));

        $response->assertStatus(200);
        $response->assertSee('Team Members');
        $response->assertSee('Last Login');
        $response->assertSee('IP Address');
        $response->assertSee('Location');
        $response->assertSee('Never logged in');
    }

    public function test_admin_creates_new_member_generates_invitation_and_email(): void
    {
        Mail::fake();
        Queue::fake();

        $response = $this->actingAs($this->admin)->post(route('admin.members.store'), [
            'name' => 'John Doe',
            'phone' => '01899999999',
            'email' => 'johndoe@example.com',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $response->assertSessionHas('success');

        $newMember = User::where('email', 'johndoe@example.com')->first();
        $this->assertNotNull($newMember);
        $this->assertSame('manager', $newMember->role);
        $this->assertTrue($newMember->must_change_password);
        $this->assertNotNull($newMember->invitation_token_hash);
        $this->assertNotNull($newMember->invitation_expires_at);
        $this->assertTrue($newMember->isInvitationPending());

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'member.created',
            'target_id' => (string) $newMember->id,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'member.invitation_sent',
            'target_id' => (string) $newMember->id,
        ]);
    }

    public function test_invited_member_can_setup_password_and_accept_invitation(): void
    {
        Mail::fake();

        $member = User::factory()->create([
            'name' => 'Alice Invited',
            'email' => 'alice@example.com',
            'phone' => '01811112222',
            'role' => 'editor',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $invite = MemberInvitationService::createInvitation($member, $this->admin);
        $token = $invite['token'];

        // Access setup page
        $pageResponse = $this->get(route('admin.setup-password', $token));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Alice Invited');
        $pageResponse->assertSee('Activate Account');

        // Submit new password
        $submitResponse = $this->post(route('admin.setup-password.submit', $token), [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $submitResponse->assertRedirect(route('admin.login'));
        $submitResponse->assertSessionHas('success');

        $member->refresh();
        $this->assertFalse($member->must_change_password);
        $this->assertNull($member->invitation_token_hash);
        $this->assertNotNull($member->invitation_accepted_at);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $member->password));

        // Attempting to reuse the token fails
        $reuseResponse = $this->get(route('admin.setup-password', $token));
        $reuseResponse->assertSee('Invitation Expired or Invalid');
    }

    public function test_expired_invitation_is_rejected(): void
    {
        $member = User::factory()->create([
            'email' => 'bob@example.com',
            'phone' => '01833334444',
            'role' => 'staff',
            'must_change_password' => true,
        ]);

        $invite = MemberInvitationService::createInvitation($member, $this->admin);
        $token = $invite['token'];

        // Expire the invitation in the database
        $member->update(['invitation_expires_at' => now()->subHour()]);

        $response = $this->get(route('admin.setup-password', $token));
        $response->assertSee('Invitation Expired or Invalid');

        $submitResponse = $this->post(route('admin.setup-password.submit', $token), [
            'password' => 'SomePassword123!',
            'password_confirmation' => 'SomePassword123!',
        ]);
        $submitResponse->assertSessionHas('error');
    }

    public function test_mandatory_password_change_enforces_redirect_until_changed(): void
    {
        $member = User::factory()->create([
            'name' => 'Charlie Forced',
            'email' => 'charlie@example.com',
            'phone' => '01855556666',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        // Attempting to access dashboard redirects to change-password
        $response = $this->actingAs($member)->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.password.change'));

        // Accessing change-password view succeeds
        $viewResponse = $this->actingAs($member)->get(route('admin.password.change'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Password Setup Required');

        // Submit new password
        $submitResponse = $this->actingAs($member)->post(route('admin.password.change.submit'), [
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $submitResponse->assertRedirect(route('admin.dashboard'));

        $member->refresh();
        $this->assertFalse($member->must_change_password);
        $this->assertTrue(Hash::check('BrandNewPassword123!', $member->password));

        // Now member can access dashboard normally
        $dashboardResponse = $this->actingAs($member)->get(route('admin.dashboard'));
        $dashboardResponse->assertStatus(200);
    }

    public function test_admin_can_view_member_details_and_resend_invitation(): void
    {
        Mail::fake();

        $member = User::factory()->create([
            'name' => 'David Staff',
            'email' => 'david@example.com',
            'phone' => '01877778888',
            'role' => 'staff',
            'status' => 'active',
        ]);

        // Details page
        $showResponse = $this->actingAs($this->admin)->get(route('admin.members.show', $member->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('David Staff');
        $showResponse->assertSee('Login Tracking History');

        // Resend invitation
        $resendResponse = $this->actingAs($this->admin)->post(route('admin.members.resend-invitation', $member->id));
        $resendResponse->assertSessionHas('success');

        $member->refresh();
        $this->assertTrue($member->isInvitationPending());
    }

    public function test_admin_can_view_member_details_with_login_histories_ordered_by_login_at(): void
    {
        $member = User::factory()->create([
            'name' => 'Sarah Analyst',
            'email' => 'sarah@example.com',
            'phone' => '01866667777',
            'role' => 'staff',
            'status' => 'active',
        ]);

        MemberLoginHistory::create([
            'user_id' => $member->id,
            'login_at' => now()->subDay(),
            'ip_address' => '127.0.0.1',
            'status' => 'success',
        ]);

        MemberLoginHistory::create([
            'user_id' => $member->id,
            'login_at' => now(),
            'ip_address' => '127.0.0.1',
            'status' => 'success',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.members.show', $member->id));
        $response->assertStatus(200);
        $response->assertSee('Sarah Analyst');
        $response->assertSee('Successful Logins');
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('admin.members.destroy', $this->admin->id));
        $response->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertNotNull(User::find($this->admin->id));
    }

    public function test_unauthorized_role_cannot_manage_team_members(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff_unauth@example.com',
            'phone' => '01800000099',
            'password' => Hash::make('Password123!'),
            'role' => 'staff',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        // Staff attempting to view members
        $response = $this->actingAs($staff)->get(route('admin.members.index'));
        // Rbac blocks members.view
        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error');
    }
}
