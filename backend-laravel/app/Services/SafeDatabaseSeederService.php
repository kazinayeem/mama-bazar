<?php

namespace App\Services;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PolicyPage;
use App\Models\Product;
use App\Models\RolePermission;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SafeDatabaseSeederService
{
    /**
     * Required core tables to verify before attempting any seeding.
     *
     * @var array<string>
     */
    protected array $requiredTables = [
        'users',
        'admin_roles',
        'admin_permissions',
        'role_permissions',
        'site_settings',
        'policy_pages',
        'payment_methods',
        'shipping_methods',
    ];

    /**
     * Validate the target database connection and environment.
     * Never exposes credentials, passwords, or secrets.
     *
     * @return array{
     *     valid: bool,
     *     environment: string,
     *     connection: string,
     *     database: string,
     *     host: string,
     *     missing_tables: array<string>,
     *     error: ?string
     * }
     */
    public function validateConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $connectionName = DB::getDefaultConnection();
            $config = config("database.connections.{$connectionName}", []);

            $dbHost = $config['host'] ?? 'unknown';
            $dbName = $config['database'] ?? 'unknown';
            $env = app()->environment();

            $missingTables = [];
            foreach ($this->requiredTables as $table) {
                if (! Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            return [
                'valid' => empty($missingTables),
                'environment' => (string) $env,
                'connection' => (string) $connectionName,
                'database' => (string) $dbName,
                'host' => (string) $dbHost,
                'missing_tables' => $missingTables,
                'error' => empty($missingTables) ? null : 'Missing core tables: '.implode(', ', $missingTables),
            ];
        } catch (Throwable $e) {
            return [
                'valid' => false,
                'environment' => (string) app()->environment(),
                'connection' => (string) DB::getDefaultConnection(),
                'database' => 'unreachable',
                'host' => 'unreachable',
                'missing_tables' => [],
                'error' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Inspect all domains and identify existing vs missing records.
     * Performs ZERO write operations.
     *
     * @return array<string, mixed>
     */
    public function inspect(): array
    {
        $adminAudit = $this->inspectAdmins();
        $roleAudit = $this->inspectRoles();
        $permissionAudit = $this->inspectPermissions();
        $rolePermissionAudit = $this->inspectRolePermissions();
        $settingAudit = $this->inspectSettings();
        $policyAudit = $this->inspectPolicyPages();
        $paymentAudit = $this->inspectPaymentMethods();
        $shippingAudit = $this->inspectShippingMethods();
        $storefrontAudit = $this->inspectStorefrontPreservation();

        return [
            'admins' => $adminAudit,
            'roles' => $roleAudit,
            'permissions' => $permissionAudit,
            'role_permissions' => $rolePermissionAudit,
            'site_settings' => $settingAudit,
            'policy_pages' => $policyAudit,
            'payment_methods' => $paymentAudit,
            'shipping_methods' => $shippingAudit,
            'storefront_preservation' => $storefrontAudit,
        ];
    }

    /**
     * Safely seed missing data. If dry-run is true, performs NO write operations.
     *
     * @return array<string, mixed>
     */
    public function execute(bool $dryRun = false): array
    {
        $inspection = $this->inspect();

        if ($dryRun) {
            return [
                'dry_run' => true,
                'executed' => false,
                'summary' => $this->buildSummaryFromInspection($inspection, true),
                'details' => $inspection,
            ];
        }

        // Execute writes in separate protected transactions
        $results = [];

        // 1. Roles
        $results['roles'] = $this->seedMissingRoles($inspection['roles']['missing_items']);

        // 2. Permissions
        $results['permissions'] = $this->seedMissingPermissions($inspection['permissions']['missing_items']);

        // 3. Role Permissions
        $results['role_permissions'] = $this->seedMissingRolePermissions($inspection['role_permissions']['missing_items']);

        // 4. Site Settings
        $results['site_settings'] = $this->seedMissingSettings($inspection['site_settings']['missing_items']);

        // 5. Policy Pages
        $results['policy_pages'] = $this->seedMissingPolicyPages($inspection['policy_pages']['missing_items']);

        // 6. Payment Methods
        $results['payment_methods'] = $this->seedMissingPaymentMethods($inspection['payment_methods']['missing_items']);

        // 7. Shipping Methods
        $results['shipping_methods'] = $this->seedMissingShippingMethods($inspection['shipping_methods']['missing_items']);

        // 8. Admins (Insert-only check: skipped if any admin exists)
        $results['admins'] = [
            'existing' => $inspection['admins']['existing_count'],
            'inserted' => 0,
            'skipped' => $inspection['admins']['existing_count'],
            'status' => $inspection['admins']['action_note'],
        ];

        // 9. Storefront Data (Always preserved, 0 touched)
        $results['storefront_preservation'] = [
            'existing' => $inspection['storefront_preservation']['total_records'],
            'inserted' => 0,
            'skipped' => $inspection['storefront_preservation']['total_records'],
            'status' => 'Untouched & Preserved',
        ];

        // Clear relevant application caches
        $this->clearCaches();

        return [
            'dry_run' => false,
            'executed' => true,
            'summary' => $results,
            'details' => $inspection,
        ];
    }

    /**
     * Inspect admin accounts to ensure credentials are never overwritten.
     *
     * @return array<string, mixed>
     */
    protected function inspectAdmins(): array
    {
        $existingAdmins = User::whereIn('role', ['admin', 'super_admin'])
            ->select(['id', 'name', 'email', 'role'])
            ->get();

        $count = $existingAdmins->count();

        return [
            'table' => 'users (admins)',
            'existing_count' => $count,
            'missing_count' => 0,
            'can_seed' => $count === 0,
            'action_note' => $count > 0
                ? "{$count} admin accounts found. Skipping admin creation to protect existing credentials."
                : 'No admin accounts found. A default admin may be seeded safely.',
            'existing_admins' => $existingAdmins->map(fn ($u) => ['id' => $u->id, 'email' => $u->email, 'role' => $u->role])->toArray(),
        ];
    }

    /**
     * Inspect admin roles.
     *
     * @return array<string, mixed>
     */
    protected function inspectRoles(): array
    {
        $existing = AdminRole::pluck('name')->toArray();
        $presets = RbacService::getRolePresets();

        $missing = [];
        foreach ($presets as $name => $preset) {
            if (! in_array($name, $existing, true)) {
                $missing[] = [
                    'name' => $name,
                    'display_name' => $preset['displayName'],
                    'description' => $preset['description'],
                    'is_system' => true,
                ];
            }
        }

        return [
            'table' => 'admin_roles',
            'existing_count' => count($existing),
            'missing_count' => count($missing),
            'missing_items' => $missing,
        ];
    }

    /**
     * Inspect admin permissions against RbacService::ALL_PERMISSIONS.
     *
     * @return array<string, mixed>
     */
    protected function inspectPermissions(): array
    {
        $existing = AdminPermission::pluck('code')->toArray();
        $all = RbacService::ALL_PERMISSIONS;

        $missing = [];
        foreach ($all as $item) {
            if (! in_array($item['code'], $existing, true)) {
                $missing[] = $item;
            }
        }

        return [
            'table' => 'admin_permissions',
            'existing_count' => count($existing),
            'missing_count' => count($missing),
            'missing_items' => $missing,
        ];
    }

    /**
     * Inspect role-permission mappings.
     *
     * @return array<string, mixed>
     */
    protected function inspectRolePermissions(): array
    {
        $existingRows = RolePermission::all();
        $existingSet = [];
        foreach ($existingRows as $row) {
            $existingSet["{$row->role_name}:{$row->permission_code}"] = true;
        }

        $allPermissionCodes = array_column(RbacService::ALL_PERMISSIONS, 'code');
        $presets = RbacService::getRolePresets();

        $missing = [];
        foreach ($presets as $roleName => $preset) {
            $codes = $preset['permissions'];
            if (in_array('*', $codes, true)) {
                $codes = $allPermissionCodes;
            }

            foreach ($codes as $code) {
                $key = "{$roleName}:{$code}";
                if (! isset($existingSet[$key])) {
                    $missing[] = [
                        'role_name' => $roleName,
                        'permission_code' => $code,
                    ];
                }
            }
        }

        return [
            'table' => 'role_permissions',
            'existing_count' => count($existingSet),
            'missing_count' => count($missing),
            'missing_items' => $missing,
        ];
    }

    /**
     * Inspect site settings against business and email defaults.
     *
     * @return array<string, mixed>
     */
    protected function inspectSettings(): array
    {
        $existingKeys = SiteSetting::pluck('key')->toArray();

        $businessDefaults = BusinessSettingService::defaults();
        $emailDefaults = EmailSettingService::defaults();

        $allDefaults = array_merge($businessDefaults, $emailDefaults);

        $missing = [];
        foreach ($allDefaults as $key => $val) {
            if (! in_array($key, $existingKeys, true)) {
                $missing[] = [
                    'key' => $key,
                    'value' => is_bool($val) ? ($val ? '1' : '0') : (string) $val,
                ];
            }
        }

        return [
            'table' => 'site_settings',
            'existing_count' => count($existingKeys),
            'missing_count' => count($missing),
            'missing_items' => $missing,
        ];
    }

    /**
     * Inspect core policy pages.
     *
     * @return array<string, mixed>
     */
    protected function inspectPolicyPages(): array
    {
        $existingSlugs = PolicyPage::pluck('slug')->toArray();

        $defaultPages = [
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'content' => 'Welcome to Mama Bazar. By accessing or using our services, you agree to be bound by these terms.',
                'status' => 'published',
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => 'Your privacy is important to us. We handle your personal data with utmost security and respect.',
                'status' => 'published',
            ],
            [
                'slug' => 'return-refund',
                'title' => 'Return & Refund Policy',
                'content' => 'Products can be returned within 7 days of receipt in original, unused condition.',
                'status' => 'published',
            ],
            [
                'slug' => 'shipping-policy',
                'title' => 'Shipping Policy',
                'content' => 'We deliver across Bangladesh. Delivery within Dhaka takes 24-48 hours, outside Dhaka 3-5 business days.',
                'status' => 'published',
            ],
        ];

        $missing = [];
        foreach ($defaultPages as $page) {
            if (! in_array($page['slug'], $existingSlugs, true)) {
                $missing[] = $page;
            }
        }

        return [
            'table' => 'policy_pages',
            'existing_count' => count($existingSlugs),
            'missing_count' => count($missing),
            'missing_items' => $missing,
        ];
    }

    /**
     * Inspect payment methods.
     *
     * @return array<string, mixed>
     */
    protected function inspectPaymentMethods(): array
    {
        $existingCodes = PaymentMethod::pluck('code')->toArray();
        $defaultRows = PaymentMethod::defaultSeedRows();

        $missing = [];
        foreach ($defaultRows as $row) {
            if (! in_array($row['code'], $existingCodes, true)) {
                $missing[] = $row;
            }
        }

        return [
            'table' => 'payment_methods',
            'existing_count' => count($existingCodes),
            'missing_count' => count($missing),
            'missing_items' => $missing,
        ];
    }

    /**
     * Inspect shipping methods.
     *
     * @return array<string, mixed>
     */
    protected function inspectShippingMethods(): array
    {
        $existingNames = ShippingMethod::pluck('name')->toArray();

        $defaultMethods = [
            [
                'name' => 'Inside Dhaka (Standard)',
                'charge' => 60.00,
                'min_order' => 0.00,
                'priority' => 1,
                'status' => 'active',
                'cod_available' => true,
                'applicable_city' => 'Dhaka',
            ],
            [
                'name' => 'Inside Dhaka (Express Same-Day)',
                'charge' => 120.00,
                'min_order' => 0.00,
                'priority' => 2,
                'status' => 'active',
                'cod_available' => true,
                'applicable_city' => 'Dhaka',
            ],
            [
                'name' => 'Outside Dhaka (Standard Courier)',
                'charge' => 130.00,
                'min_order' => 0.00,
                'priority' => 3,
                'status' => 'active',
                'cod_available' => true,
                'applicable_city' => 'All Bangladesh',
            ],
        ];

        $missing = [];
        foreach ($defaultMethods as $method) {
            if (! in_array($method['name'], $existingNames, true)) {
                $missing[] = $method;
            }
        }

        return [
            'table' => 'shipping_methods',
            'existing_count' => count($existingNames),
            'missing_count' => count($missing),
            'missing_items' => $missing,
        ];
    }

    /**
     * Inspect and verify preservation of live storefront data.
     *
     * @return array<string, mixed>
     */
    protected function inspectStorefrontPreservation(): array
    {
        $counts = [
            'products' => Product::count(),
            'categories' => Category::count(),
            'brands' => Brand::count(),
            'orders' => Order::count(),
            'users' => User::count(),
        ];

        return [
            'counts' => $counts,
            'total_records' => array_sum($counts),
            'policy' => '100% Read-Only & Preserved. No deletes, overwrites, or modifications.',
        ];
    }

    /**
     * Seed missing roles.
     *
     * @param  array<array<string, mixed>>  $missing
     * @return array{existing: int, inserted: int, skipped: int}
     */
    protected function seedMissingRoles(array $missing): array
    {
        $inserted = 0;
        $existing = AdminRole::count();

        DB::transaction(function () use ($missing, &$inserted) {
            foreach ($missing as $row) {
                AdminRole::firstOrCreate(
                    ['name' => $row['name']],
                    [
                        'display_name' => $row['display_name'],
                        'description' => $row['description'],
                        'is_system' => $row['is_system'] ?? true,
                    ]
                );
                $inserted++;
            }
        });

        return [
            'existing' => $existing,
            'inserted' => $inserted,
            'skipped' => $existing,
        ];
    }

    /**
     * Seed missing permissions.
     *
     * @param  array<array<string, mixed>>  $missing
     * @return array{existing: int, inserted: int, skipped: int}
     */
    protected function seedMissingPermissions(array $missing): array
    {
        $inserted = 0;
        $existing = AdminPermission::count();

        DB::transaction(function () use ($missing, &$inserted) {
            foreach ($missing as $row) {
                AdminPermission::firstOrCreate(
                    ['code' => $row['code']],
                    [
                        'module' => $row['module'],
                        'label' => $row['label'],
                        'description' => $row['description'],
                    ]
                );
                $inserted++;
            }
        });

        return [
            'existing' => $existing,
            'inserted' => $inserted,
            'skipped' => $existing,
        ];
    }

    /**
     * Seed missing role permissions.
     *
     * @param  array<array<string, mixed>>  $missing
     * @return array{existing: int, inserted: int, skipped: int}
     */
    protected function seedMissingRolePermissions(array $missing): array
    {
        $inserted = 0;
        $existing = RolePermission::count();

        DB::transaction(function () use ($missing, &$inserted) {
            foreach ($missing as $row) {
                RolePermission::firstOrCreate(
                    [
                        'role_name' => $row['role_name'],
                        'permission_code' => $row['permission_code'],
                    ]
                );
                $inserted++;
            }
        });

        return [
            'existing' => $existing,
            'inserted' => $inserted,
            'skipped' => $existing,
        ];
    }

    /**
     * Seed missing site settings.
     *
     * @param  array<array<string, mixed>>  $missing
     * @return array{existing: int, inserted: int, skipped: int}
     */
    protected function seedMissingSettings(array $missing): array
    {
        $inserted = 0;
        $existing = SiteSetting::count();

        DB::transaction(function () use ($missing, &$inserted) {
            foreach ($missing as $row) {
                SiteSetting::firstOrCreate(
                    ['key' => $row['key']],
                    ['value' => $row['value']]
                );
                $inserted++;
            }
        });

        return [
            'existing' => $existing,
            'inserted' => $inserted,
            'skipped' => $existing,
        ];
    }

    /**
     * Seed missing policy pages.
     *
     * @param  array<array<string, mixed>>  $missing
     * @return array{existing: int, inserted: int, skipped: int}
     */
    protected function seedMissingPolicyPages(array $missing): array
    {
        $inserted = 0;
        $existing = PolicyPage::count();

        DB::transaction(function () use ($missing, &$inserted) {
            foreach ($missing as $row) {
                PolicyPage::firstOrCreate(
                    ['slug' => $row['slug']],
                    [
                        'title' => $row['title'],
                        'content' => $row['content'],
                        'status' => $row['status'],
                        'last_updated' => time(),
                    ]
                );
                $inserted++;
            }
        });

        return [
            'existing' => $existing,
            'inserted' => $inserted,
            'skipped' => $existing,
        ];
    }

    /**
     * Seed missing payment methods.
     *
     * @param  array<array<string, mixed>>  $missing
     * @return array{existing: int, inserted: int, skipped: int}
     */
    protected function seedMissingPaymentMethods(array $missing): array
    {
        $inserted = 0;
        $existing = PaymentMethod::count();

        DB::transaction(function () use ($missing, &$inserted) {
            foreach ($missing as $row) {
                PaymentMethod::firstOrCreate(
                    ['code' => $row['code']],
                    $row
                );
                $inserted++;
            }
        });

        return [
            'existing' => $existing,
            'inserted' => $inserted,
            'skipped' => $existing,
        ];
    }

    /**
     * Seed missing shipping methods.
     *
     * @param  array<array<string, mixed>>  $missing
     * @return array{existing: int, inserted: int, skipped: int}
     */
    protected function seedMissingShippingMethods(array $missing): array
    {
        $inserted = 0;
        $existing = ShippingMethod::count();

        DB::transaction(function () use ($missing, &$inserted) {
            foreach ($missing as $row) {
                ShippingMethod::firstOrCreate(
                    ['name' => $row['name']],
                    $row
                );
                $inserted++;
            }
        });

        return [
            'existing' => $existing,
            'inserted' => $inserted,
            'skipped' => $existing,
        ];
    }

    /**
     * Build dry-run summary.
     *
     * @param  array<string, mixed>  $inspection
     * @return array<string, array{existing: int, inserted: int, skipped: int}>
     */
    protected function buildSummaryFromInspection(array $inspection, bool $dryRun): array
    {
        $tables = [
            'admin_roles' => 'roles',
            'admin_permissions' => 'permissions',
            'role_permissions' => 'role_permissions',
            'site_settings' => 'site_settings',
            'policy_pages' => 'policy_pages',
            'payment_methods' => 'payment_methods',
            'shipping_methods' => 'shipping_methods',
        ];

        $summary = [];
        foreach ($tables as $dbTable => $key) {
            $data = $inspection[$key];
            $summary[$dbTable] = [
                'existing' => $data['existing_count'],
                'inserted' => $dryRun ? 0 : $data['missing_count'],
                'planned_insert' => $data['missing_count'],
                'skipped' => $data['existing_count'],
            ];
        }

        $summary['users (admins)'] = [
            'existing' => $inspection['admins']['existing_count'],
            'inserted' => 0,
            'planned_insert' => 0,
            'skipped' => $inspection['admins']['existing_count'],
        ];

        return $summary;
    }

    /**
     * Clear caches affected by new settings or permissions.
     */
    protected function clearCaches(): void
    {
        Cache::forget(BusinessSettingService::CACHE_KEY);
        Cache::forget(EmailSettingService::CACHE_KEY);
    }
}
