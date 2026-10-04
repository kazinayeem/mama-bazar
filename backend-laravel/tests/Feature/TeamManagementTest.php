<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();
        if (! $this->admin->email) {
            $this->admin->email = 'admin@mama-bazar.com';
            $this->admin->save();
        }
    }

    public function test_public_team_page_renders_active_and_public_members(): void
    {
        $activeMember = TeamMember::factory()->create([
            'name' => 'Alice Developer',
            'position' => 'Senior Backend Engineer',
            'is_active' => true,
            'is_public' => true,
            'display_order' => 1,
            'show_email_publicly' => true,
            'email' => 'alice@mama-bazar.com',
        ]);

        $inactiveMember = TeamMember::factory()->create([
            'name' => 'Bob Inactive',
            'position' => 'Former Specialist',
            'is_active' => false,
            'is_public' => true,
        ]);

        $hiddenMember = TeamMember::factory()->create([
            'name' => 'Charlie Hidden',
            'position' => 'Internal Auditor',
            'is_active' => true,
            'is_public' => false,
        ]);

        $response = $this->get(route('team'));

        $response->assertStatus(200);
        $response->assertSee('Alice Developer');
        $response->assertSee('Senior Backend Engineer');
        $response->assertSee('alice@mama-bazar.com');
        $response->assertDontSee('Bob Inactive');
        $response->assertDontSee('Charlie Hidden');
    }

    public function test_public_team_page_search_filtering(): void
    {
        TeamMember::factory()->create([
            'name' => 'Fahim Architect',
            'position' => 'Chief Technology Officer',
            'is_active' => true,
            'is_public' => true,
        ]);

        TeamMember::factory()->create([
            'name' => 'Sadia Marketer',
            'position' => 'Head of Brand Marketing',
            'is_active' => true,
            'is_public' => true,
        ]);

        $response = $this->get(route('team', ['q' => 'Fahim']));

        $response->assertStatus(200);
        $response->assertSee('Fahim Architect');
        $response->assertDontSee('Sadia Marketer');
    }

    public function test_footer_displays_selected_team_members_when_enabled(): void
    {
        SiteSetting::updateOrCreate(['key' => 'footer_team_enabled'], ['value' => '1']);
        SiteSetting::updateOrCreate(['key' => 'footer_team_title'], ['value' => 'Leadership & Core Team']);

        $footerMember = TeamMember::factory()->create([
            'name' => 'Zubair Founder',
            'position' => 'Managing Director',
            'is_active' => true,
            'is_public' => true,
            'show_in_footer' => true,
        ]);

        $nonFooterMember = TeamMember::factory()->create([
            'name' => 'Non Footer Staff',
            'position' => 'Junior Engineer',
            'is_active' => true,
            'is_public' => true,
            'show_in_footer' => false,
        ]);

        $response = $this->get(route('about'));

        $response->assertStatus(200);
        $response->assertSee('Leadership & Core Team');
        $response->assertSee('Zubair Founder');
        $response->assertSee(route('team'));
    }

    public function test_footer_hides_team_section_when_disabled(): void
    {
        SiteSetting::updateOrCreate(['key' => 'footer_team_enabled'], ['value' => '0']);

        TeamMember::factory()->create([
            'name' => 'Hidden Leadership Person',
            'position' => 'Chief Officer',
            'is_active' => true,
            'is_public' => true,
            'show_in_footer' => true,
        ]);

        $response = $this->get(route('about'));

        $response->assertStatus(200);
        $response->assertDontSee('Hidden Leadership Person');
    }

    public function test_admin_can_view_team_management_dashboard(): void
    {
        TeamMember::factory()->create(['name' => 'Existing Staff']);

        $response = $this->actingAs($this->admin)->get(route('admin.team.index'));

        $response->assertStatus(200);
        $response->assertSee('Team Management');
        $response->assertSee('Existing Staff');
    }

    public function test_admin_can_create_team_member_with_image_upload(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('profile.webp', 300, 300);

        $response = $this->actingAs($this->admin)->post(route('admin.team.store'), [
            'name' => 'Nayeem Founder',
            'email' => 'founder@mama-bazar.com',
            'position' => 'Founder & CEO',
            'bio' => 'Founder of Mama Bazar leading modern retail.',
            'is_active' => 1,
            'is_public' => 1,
            'show_in_footer' => 1,
            'show_email_publicly' => 1,
            'social_linkedin' => 'https://linkedin.com/in/founder',
            'image' => $image,
        ]);

        $response->assertRedirect(route('admin.team.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('team_members', [
            'name' => 'Nayeem Founder',
            'email' => 'founder@mama-bazar.com',
            'position' => 'Founder & CEO',
            'is_active' => 1,
            'show_in_footer' => 1,
            'show_email_publicly' => 1,
        ]);

        $created = TeamMember::where('email', 'founder@mama-bazar.com')->first();
        $this->assertNotNull($created->image);
        $this->assertSame('https://linkedin.com/in/founder', $created->social_links['linkedin'] ?? null);
    }

    public function test_admin_can_update_team_member(): void
    {
        $member = TeamMember::factory()->create([
            'name' => 'Old Name',
            'position' => 'Junior Role',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.team.update', $member->id), [
            'name' => 'Updated Name',
            'email' => $member->email,
            'position' => 'Senior Vice President',
            'is_active' => 0,
            'is_public' => 1,
            'show_in_footer' => 1,
        ]);

        $response->assertRedirect(route('admin.team.index'));

        $member->refresh();
        $this->assertSame('Updated Name', $member->name);
        $this->assertSame('Senior Vice President', $member->position);
        $this->assertFalse($member->is_active);
        $this->assertTrue($member->show_in_footer);
    }

    public function test_admin_can_delete_team_member(): void
    {
        $member = TeamMember::factory()->create(['name' => 'Member To Delete']);

        $response = $this->actingAs($this->admin)->delete(route('admin.team.destroy', $member->id));

        $response->assertRedirect(route('admin.team.index'));
        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
    }

    public function test_admin_can_reorder_team_members_with_drag_and_drop(): void
    {
        $member1 = TeamMember::factory()->create(['display_order' => 1]);
        $member2 = TeamMember::factory()->create(['display_order' => 2]);
        $member3 = TeamMember::factory()->create(['display_order' => 3]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.team.reorder'), [
            'order' => [$member3->id, $member1->id, $member2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $member3->refresh();
        $member1->refresh();
        $member2->refresh();

        $this->assertSame(1, $member3->display_order);
        $this->assertSame(2, $member1->display_order);
        $this->assertSame(3, $member2->display_order);
    }

    public function test_admin_can_toggle_member_status_and_footer(): void
    {
        $member = TeamMember::factory()->create([
            'is_active' => true,
            'show_in_footer' => false,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.team.toggle-status', $member->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);
        $this->assertFalse($member->fresh()->is_active);

        $response = $this->actingAs($this->admin)->postJson(route('admin.team.toggle-footer', $member->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'show_in_footer' => true]);
        $this->assertTrue($member->fresh()->show_in_footer);
    }

    public function test_unauthenticated_user_cannot_access_admin_team(): void
    {
        $response = $this->get(route('admin.team.index'));
        $response->assertRedirect(route('login'));

        $postResponse = $this->post(route('admin.team.store'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'position' => 'Boss',
        ]);
        $postResponse->assertRedirect(route('login'));
    }

    public function test_public_api_endpoint_returns_team_members(): void
    {
        TeamMember::factory()->create([
            'name' => 'API Member',
            'is_active' => true,
            'is_public' => true,
        ]);

        $response = $this->getJson('/api/team');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'count',
            'data' => [
                '*' => ['id', 'name', 'position', 'email'],
            ],
        ]);
    }
}
