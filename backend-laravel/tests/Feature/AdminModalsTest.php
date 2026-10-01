<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModalsTest extends TestCase
{
    use RefreshDatabase;

    protected function getAdminUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin_test@mamabazar.com'],
            [
                'name' => 'Admin Test',
                'phone' => '01700000999',
                'role' => 'admin',
                'password' => bcrypt('secret123'),
                'status' => 'active',
            ]
        );
    }

    public function test_brands_page_loads_with_modals(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.brands.index'));
        $response->assertStatus(200);
        $response->assertSee('x-show="editOpen"', false);
        $response->assertSee('x-show="createOpen"', false);
        $response->assertDontSee('<details id="edit-', false);
    }

    public function test_colors_page_loads_with_modals_and_color_preview(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.colors.index'));
        $response->assertStatus(200);
        $response->assertSee('editOpen');
        $response->assertDontSee('<details id="edit-', false);
    }

    public function test_categories_page_loads_with_edit_modal(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertSee('editOpen');
        $response->assertSee('Edit Category');
    }

    public function test_shipping_page_loads_with_edit_modal(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.shipping.index'));
        $response->assertStatus(200);
        $response->assertSee('editOpen');
        $response->assertSee('Edit Shipping Method');
    }

    public function test_coupons_page_loads_with_edit_modal(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
        $response->assertSee('editOpen');
        $response->assertSee('Edit Coupon');
    }

    public function test_policies_page_loads_with_modals(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.policies.index'));
        $response->assertStatus(200);
        $response->assertSee('editOpen');
        $response->assertSee('createOpen');
    }

    public function test_members_page_loads_with_modals(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.members.index'));
        $response->assertStatus(200);
        $response->assertSee('createModalOpen');
        $response->assertSee('editModalOpen');
        $response->assertSee('Edit Member');
    }

    public function test_expenses_page_loads_with_modals(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.expenses.index'));
        $response->assertStatus(200);
        $response->assertSee('createModalOpen');
        $response->assertSee('editModalOpen');
        $response->assertSee('Edit Expense');
    }

    public function test_banners_page_loads_with_edit_modal(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.banners.index'));
        $response->assertStatus(200);
        $response->assertSee('editModalOpen');
        $response->assertSee('Edit Banner');
    }

    public function test_marketing_page_loads_with_edit_modal(): void
    {
        $admin = $this->getAdminUser();
        $response = $this->actingAs($admin)->get(route('admin.marketing.index'));
        $response->assertStatus(200);
        $response->assertSee('editModalOpen');
        $response->assertSee('Edit Integration');
    }
}
