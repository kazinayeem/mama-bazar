<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registers the payment gateway permissions. The ADMIN preset receives
 * configure/test; activating Live mode stays with SUPER_ADMIN ("*") or an
 * explicit per-member grant.
 */
return new class extends Migration
{
    /**
     * @var list<array{code: string, label: string, description: string}>
     */
    private array $permissions = [
        ['code' => 'payment_methods.configure', 'label' => 'Configure Payment Gateway', 'description' => 'Unlock and edit SSLCOMMERZ credentials and gateway settings'],
        ['code' => 'payment_methods.test', 'label' => 'Test Payment Gateway', 'description' => 'Run a connectivity test against the payment gateway'],
        ['code' => 'payment_methods.enable_live', 'label' => 'Activate Live Payments', 'description' => 'Switch the payment gateway to Live mode and enable it for real customers'],
    ];

    /**
     * @var list<string>
     */
    private array $adminCodes = ['payment_methods.configure', 'payment_methods.test'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_permissions')) {
            foreach ($this->permissions as $perm) {
                DB::table('admin_permissions')->updateOrInsert(
                    ['code' => $perm['code']],
                    ['module' => 'checkout', 'label' => $perm['label'], 'description' => $perm['description']]
                );
            }
        }

        if (Schema::hasTable('role_permissions')) {
            foreach ($this->adminCodes as $code) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_name' => 'ADMIN', 'permission_code' => $code],
                    []
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = array_column($this->permissions, 'code');

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->whereIn('permission_code', $codes)->delete();
        }

        if (Schema::hasTable('admin_permissions')) {
            DB::table('admin_permissions')->whereIn('code', $codes)->delete();
        }
    }
};
