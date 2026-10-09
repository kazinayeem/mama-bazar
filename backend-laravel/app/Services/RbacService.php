<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPermission;
use App\Support\AdminNav;
use App\Support\FinancialDataAccess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RbacService
{
    public const ALL_PERMISSIONS = [
        // Dashboard
        ['code' => 'dashboard.view', 'module' => 'dashboard', 'label' => 'View Dashboard', 'description' => 'View dashboard statistics and summary cards'],

        // Catalog
        ['code' => 'products.view', 'module' => 'catalog', 'label' => 'View Products', 'description' => 'View product catalog and details'],
        ['code' => 'products.create', 'module' => 'catalog', 'label' => 'Create Products', 'description' => 'Add new products to catalog'],
        ['code' => 'products.update', 'module' => 'catalog', 'label' => 'Edit Products', 'description' => 'Edit products, stock, prices, and status'],
        ['code' => 'products.delete', 'module' => 'catalog', 'label' => 'Delete Products', 'description' => 'Delete products from catalog'],
        ['code' => 'products.export', 'module' => 'catalog', 'label' => 'Export Products', 'description' => 'Export product catalog to CSV'],
        ['code' => 'products.import', 'module' => 'catalog', 'label' => 'Import Products', 'description' => 'Bulk import products from CSV'],

        ['code' => 'categories.view', 'module' => 'catalog', 'label' => 'View Categories', 'description' => 'View category hierarchy and listings'],
        ['code' => 'categories.create', 'module' => 'catalog', 'label' => 'Create Categories', 'description' => 'Add new product categories'],
        ['code' => 'categories.update', 'module' => 'catalog', 'label' => 'Edit Categories', 'description' => 'Update category details and hierarchy'],
        ['code' => 'categories.delete', 'module' => 'catalog', 'label' => 'Delete Categories', 'description' => 'Delete product categories'],
        ['code' => 'categories.export', 'module' => 'catalog', 'label' => 'Export Categories', 'description' => 'Export category listings'],

        ['code' => 'brands.view', 'module' => 'catalog', 'label' => 'View Brands', 'description' => 'View brand directory'],
        ['code' => 'brands.create', 'module' => 'catalog', 'label' => 'Create Brands', 'description' => 'Add new brands'],
        ['code' => 'brands.update', 'module' => 'catalog', 'label' => 'Edit Brands', 'description' => 'Update brand details and logos'],
        ['code' => 'brands.delete', 'module' => 'catalog', 'label' => 'Delete Brands', 'description' => 'Delete brands'],

        ['code' => 'collections.view', 'module' => 'catalog', 'label' => 'View Collections', 'description' => 'View curated collections'],
        ['code' => 'collections.create', 'module' => 'catalog', 'label' => 'Create Collections', 'description' => 'Create curated collections'],
        ['code' => 'collections.update', 'module' => 'catalog', 'label' => 'Edit Collections', 'description' => 'Update collections'],
        ['code' => 'collections.delete', 'module' => 'catalog', 'label' => 'Delete Collections', 'description' => 'Delete collections'],

        ['code' => 'colors.view', 'module' => 'catalog', 'label' => 'View Colors', 'description' => 'View color attribute options'],
        ['code' => 'colors.create', 'module' => 'catalog', 'label' => 'Create Colors', 'description' => 'Add new colors'],
        ['code' => 'colors.update', 'module' => 'catalog', 'label' => 'Edit Colors', 'description' => 'Edit colors'],
        ['code' => 'colors.delete', 'module' => 'catalog', 'label' => 'Delete Colors', 'description' => 'Delete colors'],

        ['code' => 'sizes.view', 'module' => 'catalog', 'label' => 'View Sizes', 'description' => 'View size attribute options'],
        ['code' => 'sizes.create', 'module' => 'catalog', 'label' => 'Create Sizes', 'description' => 'Add new sizes'],
        ['code' => 'sizes.update', 'module' => 'catalog', 'label' => 'Edit Sizes', 'description' => 'Edit sizes'],
        ['code' => 'sizes.delete', 'module' => 'catalog', 'label' => 'Delete Sizes', 'description' => 'Delete sizes'],

        ['code' => 'vendors.view', 'module' => 'catalog', 'label' => 'View Vendors', 'description' => 'View product vendors'],
        ['code' => 'vendors.create', 'module' => 'catalog', 'label' => 'Create Vendors', 'description' => 'Add new vendors'],
        ['code' => 'vendors.update', 'module' => 'catalog', 'label' => 'Edit Vendors', 'description' => 'Update vendor information'],
        ['code' => 'vendors.delete', 'module' => 'catalog', 'label' => 'Delete Vendors', 'description' => 'Delete vendors'],

        ['code' => 'suppliers.view', 'module' => 'catalog', 'label' => 'View Suppliers', 'description' => 'View product suppliers'],
        ['code' => 'suppliers.create', 'module' => 'catalog', 'label' => 'Create Suppliers', 'description' => 'Add new suppliers'],
        ['code' => 'suppliers.update', 'module' => 'catalog', 'label' => 'Edit Suppliers', 'description' => 'Update supplier information'],
        ['code' => 'suppliers.delete', 'module' => 'catalog', 'label' => 'Delete Suppliers', 'description' => 'Delete suppliers'],

        // Sales
        ['code' => 'orders.view', 'module' => 'sales', 'label' => 'View Orders', 'description' => 'View customer orders and history'],
        ['code' => 'orders.create', 'module' => 'sales', 'label' => 'Create Orders', 'description' => 'Manually create orders'],
        ['code' => 'orders.update', 'module' => 'sales', 'label' => 'Update Order Status', 'description' => 'Update order status and payment status'],
        ['code' => 'orders.edit', 'module' => 'sales', 'label' => 'Edit Orders', 'description' => 'General editing of order details'],
        ['code' => 'orders.edit_customer', 'module' => 'sales', 'label' => 'Edit Customer Info', 'description' => 'Correct customer name, phone, email, and address'],
        ['code' => 'orders.edit_shipping', 'module' => 'sales', 'label' => 'Edit Shipping Info', 'description' => 'Correct shipping method, delivery instructions, and area'],
        ['code' => 'orders.edit_items', 'module' => 'sales', 'label' => 'Edit Order Items', 'description' => 'Modify, add, or remove order line items and quantities'],
        ['code' => 'orders.adjust_financials', 'module' => 'sales', 'label' => 'Adjust Order Financials', 'description' => 'Override unit prices, discounts, and shipping charges'],
        ['code' => 'orders.delete', 'module' => 'sales', 'label' => 'Delete Orders', 'description' => 'Cancel or delete orders'],
        ['code' => 'orders.export', 'module' => 'sales', 'label' => 'Export Orders', 'description' => 'Export order history or invoices to CSV/PDF'],
        ['code' => 'orders.approve', 'module' => 'sales', 'label' => 'Approve Orders', 'description' => 'Approve or confirm sensitive orders'],
        ['code' => 'incomplete_orders.view', 'module' => 'sales', 'label' => 'View Incomplete Orders', 'description' => 'View abandoned checkout sessions and funnel analytics'],
        ['code' => 'incomplete_orders.export', 'module' => 'sales', 'label' => 'Export Incomplete Orders', 'description' => 'Export abandoned checkout sessions to CSV'],
        ['code' => 'incomplete_orders.view_ip', 'module' => 'sales', 'label' => 'View Incomplete Checkout IPs', 'description' => 'View client IP addresses for incomplete checkouts'],
        ['code' => 'incomplete_orders.manage_retention', 'module' => 'sales', 'label' => 'Manage Checkout Retention', 'description' => 'Prune and cleanup expired checkout session data'],

        ['code' => 'coupons.view', 'module' => 'sales', 'label' => 'View Coupons', 'description' => 'View promotional coupons'],
        ['code' => 'coupons.create', 'module' => 'sales', 'label' => 'Create Coupons', 'description' => 'Create promotional coupons'],
        ['code' => 'coupons.update', 'module' => 'sales', 'label' => 'Edit Coupons', 'description' => 'Update coupons'],
        ['code' => 'coupons.delete', 'module' => 'sales', 'label' => 'Delete Coupons', 'description' => 'Delete coupons'],

        ['code' => 'reviews.view', 'module' => 'sales', 'label' => 'View Reviews', 'description' => 'View customer reviews and ratings'],
        ['code' => 'reviews.update', 'module' => 'sales', 'label' => 'Moderate Reviews', 'description' => 'Approve, reject, or feature reviews'],
        ['code' => 'reviews.delete', 'module' => 'sales', 'label' => 'Delete Reviews', 'description' => 'Permanently delete reviews'],

        ['code' => 'marketing.view', 'module' => 'sales', 'label' => 'View Marketing', 'description' => 'View marketing tracking pixels'],
        ['code' => 'marketing.manage', 'module' => 'sales', 'label' => 'Manage Marketing', 'description' => 'Create, edit, or remove tracking integrations'],

        // Finance
        ['code' => 'expenses.view', 'module' => 'finance', 'label' => 'View Expenses', 'description' => 'View business expenses and categories'],
        ['code' => 'expenses.create', 'module' => 'finance', 'label' => 'Create Expenses', 'description' => 'Add business expenses'],
        ['code' => 'expenses.update', 'module' => 'finance', 'label' => 'Edit Expenses', 'description' => 'Update expenses and categories'],
        ['code' => 'expenses.delete', 'module' => 'finance', 'label' => 'Delete Expenses', 'description' => 'Delete expenses'],

        ['code' => 'costs.view', 'module' => 'finance', 'label' => 'View Costs', 'description' => 'View operational costs'],
        ['code' => 'costs.create', 'module' => 'finance', 'label' => 'Create Costs', 'description' => 'Add operational costs'],
        ['code' => 'costs.update', 'module' => 'finance', 'label' => 'Edit Costs', 'description' => 'Update operational costs'],
        ['code' => 'costs.delete', 'module' => 'finance', 'label' => 'Delete Costs', 'description' => 'Delete operational costs'],

        ['code' => 'reports.view', 'module' => 'finance', 'label' => 'View Reports', 'description' => 'View financial and profit reports'],
        ['code' => 'reports.export', 'module' => 'finance', 'label' => 'Export Reports', 'description' => 'Export financial reports to CSV/PDF'],

        // Financial & Cost Data (sensitive — never implied by product, analytics, or report access)
        ['code' => FinancialDataAccess::VIEW_COST_PRICE, 'module' => 'financial', 'label' => 'View Buying Price', 'description' => 'See product and variant buying (purchase) prices'],
        ['code' => FinancialDataAccess::EDIT_COST_PRICE, 'module' => 'financial', 'label' => 'Edit Buying Price', 'description' => 'Set or change buying price and product profit margin'],
        ['code' => FinancialDataAccess::VIEW_PROFIT_MARGIN, 'module' => 'financial', 'label' => 'View Profit Margin', 'description' => 'See gross profit, COGS, and margin figures in reports'],
        ['code' => FinancialDataAccess::VIEW_COST_VALUATION, 'module' => 'financial', 'label' => 'View Inventory Valuation at Cost', 'description' => 'See stock value calculated at buying price'],
        ['code' => FinancialDataAccess::VIEW_SUPPLIER_COST, 'module' => 'financial', 'label' => 'View Supplier Purchase Prices', 'description' => 'See amounts paid on supplier-linked cost records'],
        ['code' => FinancialDataAccess::VIEW_COST_HISTORY, 'module' => 'financial', 'label' => 'View Purchase History & Cost Records', 'description' => 'See product purchase costs and buying-price change history'],
        ['code' => FinancialDataAccess::EXPORT_COST_REPORTS, 'module' => 'financial', 'label' => 'Export Cost & Profit Reports', 'description' => 'Include cost and profit columns in CSV/PDF exports'],

        // Checkout
        ['code' => 'shipping.view', 'module' => 'checkout', 'label' => 'View Shipping Methods', 'description' => 'View shipping methods'],
        ['code' => 'shipping.manage', 'module' => 'checkout', 'label' => 'Manage Shipping Methods', 'description' => 'Create, edit, or delete shipping methods'],

        ['code' => 'payment_methods.view', 'module' => 'checkout', 'label' => 'View Payment Methods', 'description' => 'View payment gateways'],
        ['code' => 'payment_methods.manage', 'module' => 'checkout', 'label' => 'Manage Payment Methods', 'description' => 'Configure payment gateways and maintenance mode'],

        ['code' => 'checkout_notices.view', 'module' => 'checkout', 'label' => 'View Checkout Notices', 'description' => 'View checkout notice alerts'],
        ['code' => 'checkout_notices.manage', 'module' => 'checkout', 'label' => 'Manage Checkout Notices', 'description' => 'Create, edit, or delete checkout banners'],

        // Customers
        ['code' => 'customers.view', 'module' => 'customers', 'label' => 'View Customers', 'description' => 'View registered customer profiles'],
        ['code' => 'customers.create', 'module' => 'customers', 'label' => 'Create Customers', 'description' => 'Add customer accounts or addresses'],
        ['code' => 'customers.update', 'module' => 'customers', 'label' => 'Edit Customers', 'description' => 'Update customer details and notes'],
        ['code' => 'customers.delete', 'module' => 'customers', 'label' => 'Delete Customers', 'description' => 'Delete customer accounts or addresses'],
        ['code' => 'customers.export', 'module' => 'customers', 'label' => 'Export Customers', 'description' => 'Export customer directory or report to CSV/PDF'],

        // Content
        ['code' => 'homepage.view', 'module' => 'content', 'label' => 'View Homepage Builder', 'description' => 'View homepage sections configuration'],
        ['code' => 'homepage.manage', 'module' => 'content', 'label' => 'Manage Homepage Builder', 'description' => 'Edit, reorder, and save homepage layout'],

        ['code' => 'team.view', 'module' => 'content', 'label' => 'View Team Showcase', 'description' => 'View public team members showcase'],
        ['code' => 'team.manage', 'module' => 'content', 'label' => 'Manage Team Showcase', 'description' => 'Add, edit, or delete public team profiles'],

        ['code' => 'policies.view', 'module' => 'content', 'label' => 'View Policies & Messages', 'description' => 'View policy pages and contact messages'],
        ['code' => 'policies.manage', 'module' => 'content', 'label' => 'Manage Policies & Messages', 'description' => 'Edit policy pages and reply to messages'],

        ['code' => 'media.view', 'module' => 'content', 'label' => 'View Media Library', 'description' => 'View uploaded media assets'],
        ['code' => 'media.upload', 'module' => 'content', 'label' => 'Upload Media', 'description' => 'Upload media files and images'],
        ['code' => 'media.delete', 'module' => 'content', 'label' => 'Delete Media', 'description' => 'Delete media files'],

        ['code' => 'banners.view', 'module' => 'content', 'label' => 'View Banners', 'description' => 'View promotional banners'],
        ['code' => 'banners.create', 'module' => 'content', 'label' => 'Create Banners', 'description' => 'Create promotional banners'],
        ['code' => 'banners.update', 'module' => 'content', 'label' => 'Edit Banners', 'description' => 'Update promotional banners'],
        ['code' => 'banners.delete', 'module' => 'content', 'label' => 'Delete Banners', 'description' => 'Delete promotional banners'],

        // Insights & Operations
        ['code' => 'analytics.view', 'module' => 'insights', 'label' => 'View Analytics', 'description' => 'View sales analytics and visitor insights'],
        ['code' => 'seo.view', 'module' => 'insights', 'label' => 'View SEO Optimization', 'description' => 'View SEO metrics and metadata'],
        ['code' => 'seo.manage', 'module' => 'insights', 'label' => 'Manage SEO Settings', 'description' => 'Update SEO routes, meta tags, and sitemaps'],

        ['code' => 'inventory.view', 'module' => 'insights', 'label' => 'View Inventory', 'description' => 'View product inventory and low stock alerts'],
        ['code' => 'inventory.manage', 'module' => 'insights', 'label' => 'Manage Inventory', 'description' => 'Adjust product stock levels'],

        // System
        ['code' => 'settings.view', 'module' => 'system', 'label' => 'View Settings', 'description' => 'View site settings and business info'],
        ['code' => 'settings.manage', 'module' => 'system', 'label' => 'Manage Settings', 'description' => 'Update site settings, business info, and configurations'],

        // Email Management
        ['code' => 'email.view', 'module' => 'email', 'label' => 'View Email Dashboard', 'description' => 'View email statistics, automation status and campaign progress'],
        ['code' => 'email.settings.manage', 'module' => 'email', 'label' => 'Manage SMTP Settings', 'description' => 'Configure SMTP credentials, sender identity and automation rules'],
        ['code' => 'email.templates.manage', 'module' => 'email', 'label' => 'Manage Email Templates', 'description' => 'Edit, preview and test email templates'],
        ['code' => 'email.campaigns.manage', 'module' => 'email', 'label' => 'Manage Campaigns', 'description' => 'Create, edit and test email campaigns'],
        ['code' => 'email.campaigns.send', 'module' => 'email', 'label' => 'Send Campaigns', 'description' => 'Launch, schedule, pause and cancel bulk campaigns'],
        ['code' => 'email.logs.view', 'module' => 'email', 'label' => 'View Email Logs', 'description' => 'View delivery logs, retry failed emails and manage suppressions'],

        // Administration
        ['code' => 'members.view', 'module' => 'administration', 'label' => 'View Team Members', 'description' => 'View admin members and roles'],
        ['code' => 'members.create', 'module' => 'administration', 'label' => 'Create Members', 'description' => 'Add new admin/team members'],
        ['code' => 'members.update', 'module' => 'administration', 'label' => 'Edit Members', 'description' => 'Update member roles and permissions'],
        ['code' => 'members.delete', 'module' => 'administration', 'label' => 'Delete Members', 'description' => 'Deactivate or remove team members'],
        ['code' => 'members.export', 'module' => 'administration', 'label' => 'Export Team Members', 'description' => 'Export team directory'],
        ['code' => 'activity.view', 'module' => 'administration', 'label' => 'View Activity Monitor & Audit Log', 'description' => 'View advanced activity tracking dashboard and timelines'],
        ['code' => 'activity.export', 'module' => 'administration', 'label' => 'Export Activity Reports', 'description' => 'Export PDF, Excel, and CSV audit reports'],
        ['code' => 'activity.manage', 'module' => 'administration', 'label' => 'Manage Scheduled Reports', 'description' => 'Configure automated scheduled activity reports'],

        // Backup & Restore
        ['code' => 'backup.view', 'module' => 'backup', 'label' => 'View Backups', 'description' => 'View backup history and status'],
        ['code' => 'backup.create', 'module' => 'backup', 'label' => 'Create Backup', 'description' => 'Create complete database backup archive'],
        ['code' => 'backup.restore', 'module' => 'backup', 'label' => 'Restore Backup', 'description' => 'Restore database from backup archive'],
    ];

    public static function getRolePresets(): array
    {
        $allCodes = array_column(self::ALL_PERMISSIONS, 'code');

        return [
            'SUPER_ADMIN' => [
                'displayName' => 'Super Admin',
                'description' => 'Complete unrestricted access to all features, settings, team members, and full backup/restore.',
                'permissions' => ['*'],
            ],
            'ADMIN' => [
                'displayName' => 'Admin',
                'description' => 'Full administrative access to manage store catalog, sales, finance, content, settings, and team members.',
                'permissions' => array_values(array_filter($allCodes, fn ($code) => $code !== 'backup.restore')),
            ],
            'MANAGER' => [
                'displayName' => 'Store Manager',
                'description' => 'Manage catalog products, orders, coupons, inventory, expenses, reports, and reviews.',
                'permissions' => [
                    'dashboard.view',
                    'products.view', 'products.create', 'products.update', 'products.export',
                    'categories.view', 'categories.create', 'categories.update', 'categories.export',
                    'brands.view', 'brands.create', 'brands.update',
                    'collections.view', 'collections.create', 'collections.update',
                    'colors.view', 'colors.create', 'colors.update',
                    'sizes.view', 'sizes.create', 'sizes.update',
                    'vendors.view', 'vendors.create', 'vendors.update',
                    'suppliers.view', 'suppliers.create', 'suppliers.update',
                    'orders.view', 'orders.create', 'orders.update', 'orders.edit', 'orders.edit_customer', 'orders.edit_shipping', 'orders.edit_items', 'orders.adjust_financials', 'orders.export',
                    'incomplete_orders.view', 'incomplete_orders.export',
                    'coupons.view', 'coupons.create', 'coupons.update',
                    'reviews.view', 'reviews.update',
                    'expenses.view', 'expenses.create', 'expenses.update',
                    'costs.view', 'costs.create', 'costs.update',
                    'reports.view', 'reports.export',
                    'shipping.view',
                    'payment_methods.view',
                    'checkout_notices.view',
                    'customers.view', 'customers.export',
                    'inventory.view', 'inventory.manage',
                    'media.view', 'media.upload',
                    'banners.view', 'banners.create', 'banners.update',
                    'analytics.view',
                    'email.view', 'email.logs.view',
                ],
            ],
            'EDITOR' => [
                'displayName' => 'Content Editor',
                'description' => 'Create and edit catalog products, categories, collections, homepage sections, banners, and media.',
                'permissions' => [
                    'dashboard.view',
                    'products.view', 'products.create', 'products.update',
                    'categories.view', 'categories.create', 'categories.update',
                    'brands.view', 'brands.create', 'brands.update',
                    'collections.view', 'collections.create', 'collections.update',
                    'homepage.view', 'homepage.manage',
                    'policies.view', 'policies.manage',
                    'media.view', 'media.upload',
                    'banners.view', 'banners.create', 'banners.update',
                ],
            ],
            'STAFF' => [
                'displayName' => 'Sales & Support Staff',
                'description' => 'View and process customer orders, view inventory stock, and view customer directory.',
                'permissions' => [
                    'dashboard.view',
                    'orders.view', 'orders.create', 'orders.update', 'orders.edit_customer', 'orders.edit_shipping',
                    'inventory.view',
                    'customers.view',
                    'products.view',
                ],
            ],
            'CUSTOM' => [
                'displayName' => 'Custom Permissions',
                'description' => 'Granular permissions explicitly customized per member.',
                'permissions' => [],
            ],
        ];
    }

    /**
     * Display metadata for the member permission matrix, in sidebar order.
     * Keys are permission codes without their trailing action segment
     * (e.g. "orders", "email.templates") and are never used for display.
     *
     * @var array<string, array{label: string, group: string}>
     */
    public const MATRIX_MODULES = [
        'dashboard' => ['label' => 'Dashboard', 'group' => 'Overview'],
        'products' => ['label' => 'Products', 'group' => 'Catalog'],
        'categories' => ['label' => 'Categories', 'group' => 'Catalog'],
        'brands' => ['label' => 'Brands', 'group' => 'Catalog'],
        'collections' => ['label' => 'Collections', 'group' => 'Catalog'],
        'colors' => ['label' => 'Colors', 'group' => 'Catalog'],
        'sizes' => ['label' => 'Sizes', 'group' => 'Catalog'],
        'vendors' => ['label' => 'Vendors', 'group' => 'Catalog'],
        'suppliers' => ['label' => 'Suppliers', 'group' => 'Catalog'],
        'orders' => ['label' => 'Orders', 'group' => 'Sales'],
        'incomplete_orders' => ['label' => 'Incomplete Orders', 'group' => 'Sales'],
        'coupons' => ['label' => 'Coupons', 'group' => 'Sales'],
        'marketing' => ['label' => 'Marketing', 'group' => 'Sales'],
        'reviews' => ['label' => 'Reviews', 'group' => 'Sales'],
        'expenses' => ['label' => 'Expenses', 'group' => 'Finance'],
        'costs' => ['label' => 'Operational Costs', 'group' => 'Finance'],
        'reports' => ['label' => 'Financial Reports', 'group' => 'Finance'],
        'shipping' => ['label' => 'Shipping Methods', 'group' => 'Checkout'],
        'payment_methods' => ['label' => 'Payment Methods', 'group' => 'Checkout'],
        'checkout_notices' => ['label' => 'Checkout Notices', 'group' => 'Checkout'],
        'customers' => ['label' => 'Customers', 'group' => 'Customers'],
        'homepage' => ['label' => 'Homepage Builder', 'group' => 'Content'],
        'team' => ['label' => 'Team Management', 'group' => 'Content'],
        'policies' => ['label' => 'Policies & Messages', 'group' => 'Content'],
        'media' => ['label' => 'Media Library', 'group' => 'Content'],
        'banners' => ['label' => 'Banners', 'group' => 'Content'],
        'analytics' => ['label' => 'Analytics', 'group' => 'Insights'],
        'seo' => ['label' => 'SEO Optimization', 'group' => 'Insights'],
        'email' => ['label' => 'Email Dashboard', 'group' => 'Email Management'],
        'email.settings' => ['label' => 'SMTP Settings', 'group' => 'Email Management'],
        'email.templates' => ['label' => 'Email Templates', 'group' => 'Email Management'],
        'email.campaigns' => ['label' => 'Email Campaigns', 'group' => 'Email Management'],
        'email.logs' => ['label' => 'Email Logs', 'group' => 'Email Management'],
        'members' => ['label' => 'Team Members', 'group' => 'Security & Access'],
        'activity' => ['label' => 'Activity Monitor', 'group' => 'Security & Access'],
        'backup' => ['label' => 'Backup & Restore', 'group' => 'Security & Access'],
        'inventory' => ['label' => 'Inventory', 'group' => 'System'],
        'settings' => ['label' => 'Settings', 'group' => 'System'],
    ];

    /**
     * Matrix columns and the permission actions each one may hold, in priority order.
     * Actions that do not fit a column are listed as additional actions.
     *
     * @var array<string, list<string>>
     */
    public const MATRIX_COLUMNS = [
        'view' => ['view'],
        'create' => ['create', 'upload'],
        'update' => ['update', 'manage'],
        'delete' => ['delete'],
        'export' => ['export'],
        'import' => ['import'],
        'approve' => ['approve', 'restore', 'send'],
    ];

    /**
     * Permission matrix for the Add/Edit member UI, derived from ALL_PERMISSIONS
     * so every grantable code appears exactly once. Financial cost permissions
     * are excluded (they have their own section).
     *
     * @return list<array{key: string, label: string, group: string, pages: list<string>, permissions: array<string, array{code: string, label: string, description: string}>, additional: list<array{code: string, label: string, description: string}>}>
     */
    public static function getPermissionMatrix(): array
    {
        $actionsByModule = [];
        foreach (self::ALL_PERMISSIONS as $perm) {
            if ($perm['module'] === 'financial') {
                continue;
            }
            $separator = strrpos($perm['code'], '.');
            $moduleKey = substr($perm['code'], 0, $separator);
            $action = substr($perm['code'], $separator + 1);
            $actionsByModule[$moduleKey][$action] = [
                'code' => $perm['code'],
                'label' => $perm['label'],
                'description' => $perm['description'],
            ];
        }

        $pagesByModule = self::sidebarPagesByModule($actionsByModule);
        $orderedKeys = array_values(array_unique(array_merge(
            array_keys(array_intersect_key(self::MATRIX_MODULES, $actionsByModule)),
            array_keys($actionsByModule)
        )));

        $matrix = [];
        foreach ($orderedKeys as $moduleKey) {
            $remaining = $actionsByModule[$moduleKey];
            $columns = [];
            foreach (self::MATRIX_COLUMNS as $column => $candidates) {
                foreach ($candidates as $action) {
                    if (isset($remaining[$action])) {
                        $columns[$column] = $remaining[$action];
                        unset($remaining[$action]);
                        break;
                    }
                }
            }

            $matrix[] = [
                'key' => $moduleKey,
                'label' => self::MATRIX_MODULES[$moduleKey]['label'] ?? Str::headline(str_replace('.', ' ', $moduleKey)),
                'group' => self::MATRIX_MODULES[$moduleKey]['group'] ?? 'Other',
                'pages' => $pagesByModule[$moduleKey] ?? [],
                'permissions' => $columns,
                'additional' => array_values($remaining),
            ];
        }

        return $matrix;
    }

    /**
     * Sidebar page labels controlled by each matrix module, from AdminNav permission strings.
     *
     * @param  array<string, array<string, mixed>>  $actionsByModule
     * @return array<string, list<string>>
     */
    private static function sidebarPagesByModule(array $actionsByModule): array
    {
        $knownCodes = array_flip(array_column(self::ALL_PERMISSIONS, 'code'));
        $pages = [];
        foreach (AdminNav::rawSections() as $section) {
            foreach ($section['items'] as $item) {
                foreach (explode('|', (string) ($item['permission'] ?? '')) as $code) {
                    $separator = strrpos($code, '.');
                    if (! isset($knownCodes[$code]) || $separator === false) {
                        continue;
                    }
                    $moduleKey = substr($code, 0, $separator);
                    if (isset($actionsByModule[$moduleKey]) && ! in_array($item['label'], $pages[$moduleKey] ?? [], true)) {
                        $pages[$moduleKey][] = $item['label'];
                    }
                }
            }
        }

        return $pages;
    }

    /**
     * Financial & Cost Data permissions for the member editor, kept out of the
     * generic matrix so "Select All Actions" never grants cost visibility.
     *
     * @return list<array{code: string, label: string, description: string}>
     */
    public static function getFinancialPermissionGroup(): array
    {
        return array_values(array_map(
            fn (array $perm): array => [
                'code' => $perm['code'],
                'label' => $perm['label'],
                'description' => $perm['description'],
            ],
            array_filter(self::ALL_PERMISSIONS, fn (array $perm): bool => $perm['module'] === 'financial')
        ));
    }

    public static function isSuperAdmin(?object $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) ($user->id ?? 0) === 240011) {
            return true;
        }

        $customRole = strtoupper((string) ($user->custom_role ?? ''));
        if ($customRole === 'SUPER_ADMIN') {
            return true;
        }

        // Explicit non-super roles
        if (in_array($customRole, ['CUSTOM', 'MANAGER', 'EDITOR', 'STAFF', 'ADMIN'], true)) {
            return false;
        }

        // If explicitly custom permission mode, not super admin
        if (($user->permission_mode ?? '') === 'custom') {
            return false;
        }

        // Legacy root admin with no custom role and no restricted permissions
        if (($user->role ?? '') === 'super_admin' || ($user->role ?? '') === 'admin') {
            return true;
        }

        return false;
    }

    public static function countActiveSuperAdmins(): int
    {
        return User::where('status', 'active')->get()->filter(fn ($u) => self::isSuperAdmin($u))->count();
    }

    public static function invalidateUserPermissionCache(int $userId): void
    {
        Cache::forget("user_perm_{$userId}");
    }

    public static function resolveUserPermissions(int $userId, string $role, ?string $customRoleFromToken = null): array
    {
        $cacheKey = "user_perm_{$userId}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $dbUser = User::find($userId);

        if ($dbUser && self::isSuperAdmin($dbUser)) {
            $result = ['permissions' => ['*'], 'customRole' => 'SUPER_ADMIN'];
            Cache::put($cacheKey, $result, 120);

            return $result;
        }

        $activeRole = strtoupper((string) ($dbUser?->custom_role ?: ($customRoleFromToken ?: ($role === 'manager' ? 'MANAGER' : ($role === 'admin' ? 'ADMIN' : 'STAFF')))));

        if ($activeRole === 'SUPER_ADMIN' || $userId === 240011) {
            $result = ['permissions' => ['*'], 'customRole' => 'SUPER_ADMIN'];
            Cache::put($cacheKey, $result, 120);

            return $result;
        }

        $isCustomMode = ($dbUser?->permission_mode === 'custom') || ($activeRole === 'CUSTOM');

        $presets = self::getRolePresets();
        $preset = $presets[$activeRole] ?? ($presets[strtoupper($activeRole)] ?? null);

        // If custom mode, start empty. If role-based, start with preset.
        $permSet = $isCustomMode ? [] : array_flip($preset ? $preset['permissions'] : []);

        if ($dbUser?->permissions_json) {
            $parsed = is_array($dbUser->permissions_json)
                ? $dbUser->permissions_json
                : json_decode($dbUser->permissions_json, true);

            if (is_array($parsed)) {
                foreach ($parsed as $p) {
                    if (is_string($p)) {
                        $permSet[$p] = true;
                    }
                }
            }
        }

        $directPerms = UserPermission::where('user_id', $userId)->get();
        foreach ($directPerms as $dp) {
            if ($dp->granted) {
                $permSet[$dp->permission_code] = true;
            } else {
                unset($permSet[$dp->permission_code]);
            }
        }

        $result = [
            'permissions' => array_keys($permSet),
            'customRole' => $activeRole,
        ];

        Cache::put($cacheKey, $result, 120);

        return $result;
    }

    public static function hasPermission(array $user, string $permission): bool
    {
        $perms = $user['permissions'] ?? [];
        if (in_array('*', $perms, true)
            || ($user['customRole'] ?? '') === 'SUPER_ADMIN'
            || ($user['role'] ?? '') === 'super_admin'
            || ($user['id'] ?? 0) === 240011) {
            return true;
        }

        if (str_contains($permission, '|')) {
            foreach (explode('|', $permission) as $p) {
                if (in_array(trim($p), $perms, true)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($permission, $perms, true);
    }
}
