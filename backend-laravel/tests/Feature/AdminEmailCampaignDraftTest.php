<?php

namespace Tests\Feature;

use App\Models\EmailCampaign;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEmailCampaignDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_new_campaign_creates_a_draft_without_a_frozen_body(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.email.campaigns.store'), [
            'name' => 'Eid Offer',
            'subject' => 'Eid deals are live',
            'template_key' => 'campaign_announcement',
            'audience_filter' => 'consented_customers',
            'announcement_title' => 'Eid Mubarak',
            'announcement_body' => 'Enjoy festive savings on groceries.',
        ]);

        $response->assertSessionHasNoErrors();
        $campaign = EmailCampaign::sole();
        $response->assertRedirect(route('admin.email.campaigns.show', $campaign->id));
        $this->assertSame('draft', $campaign->status);
        $this->assertNull($campaign->body_html);

        $this->actingAs($admin)->get(route('admin.email.campaigns.preview', $campaign->id))
            ->assertOk()
            ->assertSee('Eid Mubarak');
    }

    public function test_duplicating_a_campaign_creates_a_new_draft(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = User::where('role', 'admin')->firstOrFail();
        $source = EmailCampaign::create([
            'name' => 'Weekly Newsletter',
            'subject' => 'This week at Mama Bazar',
            'template_key' => 'campaign_newsletter',
            'audience_filter' => 'consented_customers',
            'body_html' => '<p>Sent body</p>',
            'status' => 'completed',
        ]);

        $this->actingAs($admin)->post(route('admin.email.campaigns.duplicate', $source->id))
            ->assertSessionHas('success');

        $copy = EmailCampaign::whereKeyNot($source->id)->sole();
        $this->assertSame('Weekly Newsletter (copy)', $copy->name);
        $this->assertSame('draft', $copy->status);
        $this->assertNull($copy->body_html);
    }
}
