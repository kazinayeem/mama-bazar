<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPermission;
use App\Services\RbacService;
use App\Support\FinancialDataAccess;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberPermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_matrix_module_has_a_stable_key_and_readable_label(): void
    {
        $matrix = RbacService::getPermissionMatrix();

        $this->assertNotEmpty($matrix);

        foreach ($matrix as $module) {
            $this->assertNotSame('', trim((string) $module['key']));
            $this->assertNotSame('', trim((string) $module['label']), "Module [{$module['key']}] has no label.");
            $this->assertNotSame('', trim((string) $module['group']));
            $this->assertNotEmpty(
                array_merge(array_values($module['permissions']), $module['additional']),
                "Module [{$module['key']}] has no permissions."
            );
        }

        $labelsByKey = array_column($matrix, 'label', 'key');
        $this->assertCount(count($matrix), $labelsByKey, 'Module keys must be unique.');

        foreach ([
            'dashboard' => 'Dashboard',
            'products' => 'Products',
            'vendors' => 'Vendors',
            'suppliers' => 'Suppliers',
            'orders' => 'Orders',
            'shipping' => 'Shipping Methods',
            'payment_methods' => 'Payment Methods',
            'email' => 'Email Dashboard',
            'email.settings' => 'SMTP Settings',
            'email.templates' => 'Email Templates',
            'email.campaigns' => 'Email Campaigns',
            'members' => 'Team Members',
            'backup' => 'Backup & Restore',
            'inventory' => 'Inventory',
            'settings' => 'Settings',
            'incomplete_orders' => 'Incomplete Orders',
        ] as $key => $label) {
            $this->assertSame($label, $labelsByKey[$key] ?? null, "Module [{$key}] label mismatch.");
        }
    }

    public function test_matrix_covers_every_non_financial_permission_exactly_once(): void
    {
        $matrixCodes = [];
        foreach (RbacService::getPermissionMatrix() as $module) {
            foreach ($module['permissions'] as $permission) {
                $matrixCodes[] = $permission['code'];
            }
            foreach ($module['additional'] as $permission) {
                $matrixCodes[] = $permission['code'];
            }
        }

        $registryCodes = collect(RbacService::ALL_PERMISSIONS)
            ->reject(fn (array $permission): bool => $permission['module'] === 'financial')
            ->pluck('code')
            ->all();

        $this->assertSame(count($matrixCodes), count(array_unique($matrixCodes)), 'A permission code is rendered more than once.');
        $this->assertEqualsCanonicalizing($registryCodes, $matrixCodes);

        foreach (FinancialDataAccess::ALL_CODES as $financialCode) {
            $this->assertNotContains($financialCode, $matrixCodes);
        }
    }

    public function test_members_page_renders_module_labels_and_pages(): void
    {
        $this->seed(AdminSeeder::class);
        $superAdmin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($superAdmin)->get(route('admin.members.index'));

        $response->assertOk();
        $response->assertViewHas('permissionMatrix', function (array $matrix): bool {
            $orders = collect($matrix)->firstWhere('key', 'orders');
            $settings = collect($matrix)->firstWhere('key', 'settings');

            return $orders['label'] === 'Orders'
                && in_array('Returns & Refunds', $orders['pages'], true)
                && in_array('Business Information', $settings['pages'], true);
        });
        $response->assertSee('x-text="mod.label"', false);
        $response->assertDontSee('mod.title', false);
    }

    public function test_saving_custom_permissions_keeps_exact_codes(): void
    {
        $this->seed(AdminSeeder::class);
        $superAdmin = User::where('role', 'admin')->firstOrFail();

        $member = User::factory()->create([
            'email' => 'matrix_member@example.com',
            'phone' => '01733445566',
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'role',
        ]);

        $selectedCodes = [
            'orders.view',
            'orders.edit_items',
            'email.templates.manage',
            'email.campaigns.send',
            'incomplete_orders.manage_retention',
            FinancialDataAccess::VIEW_COST_PRICE,
        ];

        $this->actingAs($superAdmin)->put(route('admin.members.update', $member->id), [
            'name' => 'Matrix Member',
            'phone' => '01733445566',
            'email' => 'matrix_member@example.com',
            'role' => 'staff',
            'status' => 'active',
            'permission_mode' => 'custom',
            'permissions' => $selectedCodes,
            'sidebar_access' => ['Orders'],
        ])->assertSessionHas('success');

        $member->refresh();

        $this->assertEqualsCanonicalizing($selectedCodes, $member->permissions_json);
        $this->assertEqualsCanonicalizing(
            $selectedCodes,
            UserPermission::where('user_id', $member->id)->pluck('permission_code')->all()
        );
    }
}
