<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\RbacService;
use App\Support\AdminNav;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminRbacSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);

        $this->superAdmin = User::where('role', 'admin')->first();
        if (! $this->superAdmin->email) {
            $this->superAdmin->email = 'admin@example.com';
            $this->superAdmin->save();
        }
    }

    /** 1. Super admin can create and edit member permissions */
    public function test_super_admin_can_create_member_with_custom_permissions(): void
    {
        $payload = [
            'name' => 'Custom Staff Member',
            'phone' => '01711998877',
            'email' => 'custom_staff@example.com',
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions' => ['orders.view', 'orders.update'],
            'sidebar_access' => ['Orders'],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.members.store'), $payload);

        $response->assertSessionHas('success');

        $created = User::where('email', 'custom_staff@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('custom', $created->permission_mode);
        $this->assertSame('CUSTOM', $created->custom_role);
        $this->assertContains('orders.view', $created->permissions_json);
        $this->assertContains('orders.update', $created->permissions_json);
        $this->assertContains('Orders', $created->sidebar_access_json);

        // Verify user_permissions table rows
        $this->assertTrue(UserPermission::where('user_id', $created->id)->where('permission_code', 'orders.view')->exists());
        $this->assertTrue(UserPermission::where('user_id', $created->id)->where('permission_code', 'orders.update')->exists());
    }

    public function test_super_admin_can_edit_member_permissions(): void
    {
        $member = User::factory()->create([
            'email' => 'edit_target@example.com',
            'phone' => '01722883344',
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'role',
        ]);

        $updatePayload = [
            'name' => 'Edit Target Updated',
            'phone' => '01722883344',
            'email' => 'edit_target@example.com',
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions' => ['products.view', 'products.export'],
            'sidebar_access' => ['Products'],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.members.update', $member->id), $updatePayload);

        $response->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('custom', $member->permission_mode);
        $this->assertContains('products.view', $member->permissions_json);
        $this->assertContains('products.export', $member->permissions_json);
        $this->assertContains('Products', $member->sidebar_access_json);
    }

    /** 2 & 8. Member with Orders-only access sees only authorized sidebar navigation */
    public function test_orders_only_member_sees_only_authorized_sidebar(): void
    {
        $nayeem = User::factory()->create([
            'name' => 'Nayeem',
            'email' => 'nayeem_orders@example.com',
            'phone' => '01733445566',
            'role' => 'staff',
            'custom_role' => 'CUSTOM',
            'permission_mode' => 'custom',
            'permissions_json' => ['orders.view', 'orders.update'],
            'sidebar_access_json' => ['Orders'],
            'status' => 'active',
        ]);

        $this->actingAs($nayeem);

        $navSections = AdminNav::sections();
        $allVisibleLabels = [];
        foreach ($navSections as $sec) {
            foreach ($sec['items'] as $item) {
                $allVisibleLabels[] = $item['label'];
            }
        }

        $this->assertContains('Orders', $allVisibleLabels);
        $this->assertNotContains('Products', $allVisibleLabels);
        $this->assertNotContains('Expenses', $allVisibleLabels);
        $this->assertNotContains('Team Members', $allVisibleLabels);
        $this->assertNotContains('SMTP Settings', $allVisibleLabels);
    }

    /** 3 & 4. Member can view and edit orders, but unauthorized actions (delete, export) return 403 and are hidden */
    public function test_orders_permissions_granularity_and_button_visibility(): void
    {
        $order = Order::create([
            'order_id' => 'ORD-RBAC-101',
            'customer_name' => 'Jane Doe',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'subtotal' => 2500,
            'shipping_cost' => 0,
            'total_price' => 2500,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        // Member has view and update permissions, but NOT delete or export
        $orderClerk = User::factory()->create([
            'email' => 'clerk@example.com',
            'phone' => '01744556677',
            'role' => 'staff',
            'custom_role' => 'CUSTOM',
            'permission_mode' => 'custom',
            'permissions_json' => ['orders.view', 'orders.update'],
            'sidebar_access_json' => ['Orders'],
            'status' => 'active',
        ]);

        // 1. Can view orders index and show
        $viewIndex = $this->actingAs($orderClerk)->get(route('admin.orders.index'));
        $viewIndex->assertOk();

        $viewShow = $this->actingAs($orderClerk)->get(route('admin.orders.show', $order->id));
        $viewShow->assertOk();

        // 2. Can update status
        $updateResp = $this->actingAs($orderClerk)->post(route('admin.orders.status', $order->id), [
            'status' => 'confirmed',
        ]);
        $updateResp->assertSessionHas('success');
        $this->assertSame('confirmed', $order->fresh()->status);

        // 3. Cannot export invoice PDF (returns 403)
        $exportResp = $this->actingAs($orderClerk)->get(route('admin.orders.invoice.download', $order->id));
        $exportResp->assertForbidden();

        // 4. Download PDF is hidden on show blade
        $viewShow->assertDontSee(route('admin.orders.invoice.download', $order->id));
    }

    /** 5 & 6. Direct URL access to restricted module returns HTTP 403 Forbidden */
    public function test_direct_url_access_returns_403_for_restricted_modules(): void
    {
        $restrictedMember = User::factory()->create([
            'email' => 'restricted@example.com',
            'phone' => '01755667788',
            'role' => 'staff',
            'custom_role' => 'CUSTOM',
            'permission_mode' => 'custom',
            'permissions_json' => ['orders.view'],
            'sidebar_access_json' => ['Orders'],
            'status' => 'active',
        ]);

        // Attempting to access Products module directly
        $this->actingAs($restrictedMember)
            ->get(route('admin.products.index'))
            ->assertForbidden();

        // Attempting to access Expenses module directly
        $this->actingAs($restrictedMember)
            ->get(route('admin.expenses.index'))
            ->assertForbidden();

        // Attempting to access Team Members module directly
        $this->actingAs($restrictedMember)
            ->get(route('admin.members.index'))
            ->assertForbidden();

        // Attempting to access SMTP settings directly
        $this->actingAs($restrictedMember)
            ->get(route('admin.email.settings'))
            ->assertForbidden();
    }

    /** 7. Export and import permissions are enforced on endpoints */
    public function test_export_and_import_endpoints_require_explicit_permissions(): void
    {
        $noExportMember = User::factory()->create([
            'email' => 'no_export@example.com',
            'phone' => '01766778899',
            'role' => 'staff',
            'custom_role' => 'CUSTOM',
            'permission_mode' => 'custom',
            'permissions_json' => ['products.view'], // only view, no export/import
            'status' => 'active',
        ]);

        // Export products blocked
        $this->actingAs($noExportMember)
            ->get(route('admin.products.export'))
            ->assertForbidden();

        // Import products blocked
        $this->actingAs($noExportMember)
            ->post(route('admin.products.import'))
            ->assertForbidden();
    }

    /** 9. Role defaults and custom overrides behave correctly */
    public function test_role_defaults_and_custom_overrides(): void
    {
        // Standard Manager role inherits manager preset
        $manager = User::factory()->create([
            'email' => 'manager_preset@example.com',
            'phone' => '01777889900',
            'role' => 'manager',
            'permission_mode' => 'role',
            'status' => 'active',
        ]);

        $this->assertTrue($manager->canAdmin('products.view'));
        $this->assertTrue($manager->canAdmin('orders.view'));
        $this->assertFalse($manager->canAdmin('smtp.manage'));

        // Manager overridden to custom with only smtp.manage
        $manager->permission_mode = 'custom';
        $manager->custom_role = 'CUSTOM';
        $manager->permissions_json = ['smtp.manage'];
        $manager->save();
        RbacService::invalidateUserPermissionCache($manager->id);

        $this->assertTrue($manager->canAdmin('smtp.manage'));
        $this->assertFalse($manager->canAdmin('products.view'));
        $this->assertFalse($manager->canAdmin('orders.view'));
    }

    /** 10. Permission changes invalidate relevant caches */
    public function test_permission_changes_invalidate_cache(): void
    {
        $member = User::factory()->create([
            'email' => 'cache_user@example.com',
            'phone' => '01788990011',
            'role' => 'staff',
            'custom_role' => 'CUSTOM',
            'permission_mode' => 'custom',
            'permissions_json' => ['orders.view'],
            'status' => 'active',
        ]);

        // Resolve and cache
        $firstCheck = $member->canAdmin('products.view');
        $this->assertFalse($firstCheck);
        $this->assertTrue(Cache::has("user_perm_{$member->id}"));

        // Grant products.view via service/controller
        $member->permissions_json = ['orders.view', 'products.view'];
        $member->save();
        RbacService::invalidateUserPermissionCache($member->id);

        $this->assertFalse(Cache::has("user_perm_{$member->id}"));
        $this->assertTrue($member->canAdmin('products.view'));
    }

    /** 11. Existing admin accounts remain usable after migration */
    public function test_existing_admin_accounts_remain_fully_authorized(): void
    {
        $existingAdmin = User::factory()->create([
            'email' => 'legacy_admin@example.com',
            'phone' => '01799001122',
            'role' => 'admin',
            'status' => 'active',
            'permission_mode' => null,
            'permissions_json' => null,
            'sidebar_access_json' => null,
        ]);

        $this->assertTrue($existingAdmin->isSuperAdmin());
        $this->assertTrue($existingAdmin->canAdmin('orders.view'));
        $this->assertTrue($existingAdmin->canAdmin('products.delete'));
        $this->assertTrue($existingAdmin->canAdmin('smtp.manage'));
        $this->assertTrue($existingAdmin->hasSidebarAccess('Orders'));
        $this->assertTrue($existingAdmin->hasSidebarAccess('SMTP Settings'));
    }

    /** 12. A normal admin cannot grant themselves SUPER_ADMIN access or elevate roles */
    public function test_non_super_admin_cannot_escalate_privileges(): void
    {
        $staffUser = User::factory()->create([
            'email' => 'staff_attacker@example.com',
            'phone' => '01811223344',
            'role' => 'staff',
            'status' => 'active',
        ]);

        // Attempting to elevate itself or another user to admin
        $target = User::factory()->create([
            'email' => 'target_elevate@example.com',
            'phone' => '01822334455',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->actingAs($staffUser)->put(route('admin.members.update', $target->id), [
            'name' => 'Target Elevate',
            'phone' => '01822334455',
            'email' => 'target_elevate@example.com',
            'role' => 'admin',
        ]);

        // Non-super admin is either 403 blocked by members.update route or rejected by controller
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertNotSame('admin', $target->fresh()->role);
    }

    /** 13. The final active super administrator cannot be accidentally removed or demoted */
    public function test_final_active_super_admin_cannot_be_deleted_or_demoted(): void
    {
        // Deactivate any other super admin so $this->superAdmin is the final active super admin in test DB
        User::where('id', '!=', $this->superAdmin->id)->where('role', 'admin')->update(['status' => 'inactive']);
        $this->assertSame(1, RbacService::countActiveSuperAdmins());

        // Attempting to deactivate or demote
        $demoteResp = $this->actingAs($this->superAdmin)->put(route('admin.members.update', $this->superAdmin->id), [
            'name' => $this->superAdmin->name,
            'phone' => $this->superAdmin->phone,
            'email' => $this->superAdmin->email,
            'role' => 'staff', // Demote
            'status' => 'inactive', // Deactivate
        ]);

        $demoteResp->assertSessionHas('error');
        $this->superAdmin->refresh();
        $this->assertSame('admin', $this->superAdmin->role);
        $this->assertSame('active', $this->superAdmin->status);

        // Attempting to delete
        $delResp = $this->actingAs($this->superAdmin)->delete(route('admin.members.destroy', $this->superAdmin->id));
        $delResp->assertSessionHas('error');
        $this->assertNotNull(User::find($this->superAdmin->id));
    }

    /** 14. Permission changes are recorded in the audit log */
    public function test_permission_changes_are_recorded_in_audit_log(): void
    {
        $member = User::factory()->create([
            'email' => 'audit_target@example.com',
            'phone' => '01833445566',
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->actingAs($this->superAdmin)->put(route('admin.members.update', $member->id), [
            'name' => 'Audit Target',
            'phone' => '01833445566',
            'email' => 'audit_target@example.com',
            'role' => 'staff',
            'permission_mode' => 'custom',
            'permissions' => ['orders.view', 'orders.create'],
            'sidebar_access' => ['Orders'],
        ]);

        $audit = AdminAuditLog::where('target_id', (string) $member->id)
            ->where('action', 'member.permissions_updated')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame((int) $this->superAdmin->id, (int) $audit->actor_id);
        $this->assertStringContainsString('orders.view', (string) $audit->details);
    }
}
