<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registers the Financial & Cost Data permissions. Only the ADMIN preset row
 * is granted them (SUPER_ADMIN already holds "*"); custom-mode members and the
 * MANAGER / EDITOR / STAFF presets are intentionally left without cost access.
 */
return new class extends Migration
{
    /**
     * @var list<array{code: string, label: string, description: string}>
     */
    private array $permissions = [
        ['code' => 'products.view_cost_price', 'label' => 'View Buying Price', 'description' => 'See product and variant buying (purchase) prices'],
        ['code' => 'products.edit_cost_price', 'label' => 'Edit Buying Price', 'description' => 'Set or change buying price and product profit margin'],
        ['code' => 'analytics.view_profit_margin', 'label' => 'View Profit Margin', 'description' => 'See gross profit, COGS, and margin figures in reports'],
        ['code' => 'inventory.view_cost_valuation', 'label' => 'View Inventory Valuation at Cost', 'description' => 'See stock value calculated at buying price'],
        ['code' => 'suppliers.view_purchase_cost', 'label' => 'View Supplier Purchase Prices', 'description' => 'See amounts paid on supplier-linked cost records'],
        ['code' => 'purchases.view_cost_history', 'label' => 'View Purchase History & Cost Records', 'description' => 'See product purchase costs and buying-price change history'],
        ['code' => 'analytics.export_cost_reports', 'label' => 'Export Cost & Profit Reports', 'description' => 'Include cost and profit columns in CSV/PDF exports'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_permissions')) {
            foreach ($this->permissions as $perm) {
                DB::table('admin_permissions')->updateOrInsert(
                    ['code' => $perm['code']],
                    ['module' => 'financial', 'label' => $perm['label'], 'description' => $perm['description']]
                );
            }
        }

        if (Schema::hasTable('role_permissions')) {
            foreach ($this->permissions as $perm) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_name' => 'ADMIN', 'permission_code' => $perm['code']],
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
