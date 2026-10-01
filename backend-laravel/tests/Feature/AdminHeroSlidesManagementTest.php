<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HomepageService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHeroSlidesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->admin = User::where('role', 'admin')->first();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.homepage.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_admin_can_view_homepage_builder_and_hero_slides_tab(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.homepage.index'));

        $response->assertStatus(200);
        $response->assertSee('Homepage Builder');
        $response->assertSee('Hero Slides');
        $response->assertSee('Hero Carousel Slides');
        $response->assertSee('Add Hero Slide');
        $response->assertSee('slideValidationError');
        $response->assertSee('activeHeroSlidesCount');
    }

    public function test_saving_homepage_hero_slides_persists_and_updates_configuration(): void
    {
        $testSlides = [
            [
                'id' => 'slide-test-1',
                'title' => 'Summer Mega Sale',
                'subtitle' => 'Up to 50% off top gadgets',
                'badge' => 'Limited Deal',
                'desktopImage' => '/storage/banners/test-banner.jpg',
                'tabletImage' => '/storage/banners/test-tab.jpg',
                'mobileImage' => '/storage/banners/test-mob.jpg',
                'primaryButtonText' => 'Shop Deals',
                'primaryButtonUrl' => '/shop',
                'status' => 'active',
                'priority' => 1,
                'alignment' => 'left',
                'overlay' => true,
                'overlayOpacity' => 0.5,
                'backgroundColor' => '#0f172a',
            ],
            [
                'id' => 'slide-test-2',
                'title' => 'New Electronics Arrival',
                'subtitle' => 'Brand new smartphones and audio',
                'badge' => 'New In',
                'desktopImage' => '/storage/banners/test-electronics.jpg',
                'primaryButtonText' => 'View Catalog',
                'primaryButtonUrl' => '/shop?category=electronics',
                'status' => 'inactive',
                'priority' => 2,
                'alignment' => 'center',
                'overlay' => false,
                'overlayOpacity' => 0.55,
                'backgroundColor' => '#1e293b',
            ],
        ];

        $currentConfig = HomepageService::getConfig();
        $currentConfig['heroSlides'] = $testSlides;

        $response = $this->actingAs($this->admin)->post(route('admin.homepage.save'), [
            'config_json' => json_encode($currentConfig),
        ]);

        $response->assertSessionHas('success');

        $saved = HomepageService::getConfig();
        $this->assertCount(2, $saved['heroSlides']);
        $this->assertEquals('Summer Mega Sale', $saved['heroSlides'][0]['title']);
        $this->assertEquals('New Electronics Arrival', $saved['heroSlides'][1]['title']);
        $this->assertEquals('active', $saved['heroSlides'][0]['status']);
        $this->assertEquals('inactive', $saved['heroSlides'][1]['status']);
    }

    public function test_storefront_renders_active_hero_slide_properly(): void
    {
        $testSlides = [
            [
                'id' => 'slide-storefront-test',
                'title' => 'Festive Holiday Sale',
                'subtitle' => 'Special gifts for everyone',
                'badge' => 'Exclusive',
                'desktopImage' => '/storage/banners/holiday.jpg',
                'primaryButtonText' => 'Shop Holiday',
                'primaryButtonUrl' => '/shop',
                'status' => 'active',
                'priority' => 1,
                'alignment' => 'left',
                'overlay' => true,
                'overlayOpacity' => 0.5,
            ],
        ];

        $currentConfig = HomepageService::getConfig();
        $currentConfig['heroSlides'] = $testSlides;
        HomepageService::saveConfig($currentConfig);

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Festive Holiday Sale');
        $response->assertSee('Shop Holiday');
    }
}
