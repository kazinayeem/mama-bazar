import { pool } from "./db";
import { RowDataPacket } from "mysql2";

export interface PermissionDefinition {
  code: string;
  module: string;
  label: string;
  description: string;
}

export const ALL_PERMISSIONS: PermissionDefinition[] = [
  // Dashboard
  { code: "dashboard.view", module: "dashboard", label: "View Dashboard", description: "View dashboard statistics and summary cards" },

  // Catalog
  { code: "products.view", module: "catalog", label: "View Products", description: "View product catalog and details" },
  { code: "products.create", module: "catalog", label: "Create Products", description: "Add new products to catalog" },
  { code: "products.update", module: "catalog", label: "Edit Products", description: "Edit products, stock, prices, and status" },
  { code: "products.delete", module: "catalog", label: "Delete Products", description: "Delete products from catalog" },

  { code: "categories.view", module: "catalog", label: "View Categories", description: "View category hierarchy and listings" },
  { code: "categories.create", module: "catalog", label: "Create Categories", description: "Add new product categories" },
  { code: "categories.update", module: "catalog", label: "Edit Categories", description: "Update category details and hierarchy" },
  { code: "categories.delete", module: "catalog", label: "Delete Categories", description: "Delete product categories" },

  { code: "brands.view", module: "catalog", label: "View Brands", description: "View brand directory" },
  { code: "brands.create", module: "catalog", label: "Create Brands", description: "Add new brands" },
  { code: "brands.update", module: "catalog", label: "Edit Brands", description: "Update brand details and logos" },
  { code: "brands.delete", module: "catalog", label: "Delete Brands", description: "Delete brands" },

  { code: "collections.view", module: "catalog", label: "View Collections", description: "View curated collections" },
  { code: "collections.create", module: "catalog", label: "Create Collections", description: "Create curated collections" },
  { code: "collections.update", module: "catalog", label: "Edit Collections", description: "Update collections" },
  { code: "collections.delete", module: "catalog", label: "Delete Collections", description: "Delete collections" },

  { code: "colors.view", module: "catalog", label: "View Colors", description: "View color attribute options" },
  { code: "colors.create", module: "catalog", label: "Create Colors", description: "Add new colors" },
  { code: "colors.update", module: "catalog", label: "Edit Colors", description: "Edit colors" },
  { code: "colors.delete", module: "catalog", label: "Delete Colors", description: "Delete colors" },

  { code: "sizes.view", module: "catalog", label: "View Sizes", description: "View size attribute options" },
  { code: "sizes.create", module: "catalog", label: "Create Sizes", description: "Add new sizes" },
  { code: "sizes.update", module: "catalog", label: "Edit Sizes", description: "Edit sizes" },
  { code: "sizes.delete", module: "catalog", label: "Delete Sizes", description: "Delete sizes" },

  { code: "vendors.view", module: "catalog", label: "View Vendors", description: "View product vendors" },
  { code: "vendors.create", module: "catalog", label: "Create Vendors", description: "Add new vendors" },
  { code: "vendors.update", module: "catalog", label: "Edit Vendors", description: "Update vendor information" },
  { code: "vendors.delete", module: "catalog", label: "Delete Vendors", description: "Delete vendors" },

  { code: "suppliers.view", module: "catalog", label: "View Suppliers", description: "View product suppliers" },
  { code: "suppliers.create", module: "catalog", label: "Create Suppliers", description: "Add new suppliers" },
  { code: "suppliers.update", module: "catalog", label: "Edit Suppliers", description: "Update supplier information" },
  { code: "suppliers.delete", module: "catalog", label: "Delete Suppliers", description: "Delete suppliers" },

  // Sales
  { code: "orders.view", module: "sales", label: "View Orders", description: "View customer orders and history" },
  { code: "orders.update", module: "sales", label: "Update Orders", description: "Update order status, shipping, and payment status" },
  { code: "orders.delete", module: "sales", label: "Delete Orders", description: "Cancel or delete orders" },

  { code: "coupons.view", module: "sales", label: "View Coupons", description: "View promotional coupons" },
  { code: "coupons.create", module: "sales", label: "Create Coupons", description: "Create promotional coupons" },
  { code: "coupons.update", module: "sales", label: "Edit Coupons", description: "Update coupons" },
  { code: "coupons.delete", module: "sales", label: "Delete Coupons", description: "Delete coupons" },

  { code: "marketing.view", module: "sales", label: "View Marketing", description: "View marketing tracking pixels" },
  { code: "marketing.manage", module: "sales", label: "Manage Marketing", description: "Create, edit, or remove tracking integrations" },

  // Finance
  { code: "expenses.view", module: "finance", label: "View Expenses", description: "View business expenses and categories" },
  { code: "expenses.create", module: "finance", label: "Create Expenses", description: "Add business expenses" },
  { code: "expenses.update", module: "finance", label: "Edit Expenses", description: "Update expenses and categories" },
  { code: "expenses.delete", module: "finance", label: "Delete Expenses", description: "Delete expenses" },

  { code: "costs.view", module: "finance", label: "View Costs", description: "View operational costs" },
  { code: "costs.create", module: "finance", label: "Create Costs", description: "Add operational costs" },
  { code: "costs.update", module: "finance", label: "Edit Costs", description: "Update operational costs" },
  { code: "costs.delete", module: "finance", label: "Delete Costs", description: "Delete operational costs" },

  { code: "reports.view", module: "finance", label: "View Reports", description: "View financial and profit reports" },
  { code: "reports.export", module: "finance", label: "Export Reports", description: "Export financial reports to CSV/PDF" },

  // Checkout
  { code: "shipping.view", module: "checkout", label: "View Shipping Methods", description: "View shipping methods" },
  { code: "shipping.manage", module: "checkout", label: "Manage Shipping Methods", description: "Create, edit, or delete shipping methods" },

  { code: "payment_methods.view", module: "checkout", label: "View Payment Methods", description: "View payment gateways" },
  { code: "payment_methods.manage", module: "checkout", label: "Manage Payment Methods", description: "Configure payment gateways and maintenance mode" },

  { code: "checkout_notices.view", module: "checkout", label: "View Checkout Notices", description: "View checkout notice alerts" },
  { code: "checkout_notices.manage", module: "checkout", label: "Manage Checkout Notices", description: "Create, edit, or delete checkout banners" },

  // Customers
  { code: "customers.view", module: "customers", label: "View Customers", description: "View registered customer profiles" },
  { code: "customers.update", module: "customers", label: "Edit Customers", description: "Update customer details" },
  { code: "customers.delete", module: "customers", label: "Delete Customers", description: "Delete customer accounts" },

  // Content
  { code: "homepage.view", module: "content", label: "View Homepage Builder", description: "View homepage sections configuration" },
  { code: "homepage.manage", module: "content", label: "Manage Homepage Builder", description: "Edit, reorder, and save homepage layout" },

  { code: "policies.view", module: "content", label: "View Policies & Messages", description: "View policy pages and contact messages" },
  { code: "policies.manage", module: "content", label: "Manage Policies & Messages", description: "Edit policy pages and reply to messages" },

  { code: "media.view", module: "content", label: "View Media Library", description: "View uploaded media assets" },
  { code: "media.upload", module: "content", label: "Upload Media", description: "Upload media files and images" },
  { code: "media.delete", module: "content", label: "Delete Media", description: "Delete media files" },

  { code: "banners.view", module: "content", label: "View Banners", description: "View promotional banners" },
  { code: "banners.create", module: "content", label: "Create Banners", description: "Create promotional banners" },
  { code: "banners.update", module: "content", label: "Edit Banners", description: "Update promotional banners" },
  { code: "banners.delete", module: "content", label: "Delete Banners", description: "Delete promotional banners" },

  // Insights & Operations
  { code: "analytics.view", module: "insights", label: "View Analytics", description: "View sales analytics and visitor insights" },
  { code: "inventory.view", module: "insights", label: "View Inventory", description: "View product inventory and low stock alerts" },
  { code: "inventory.manage", module: "insights", label: "Manage Inventory", description: "Adjust product stock levels" },

  { code: "bookings.view", module: "insights", label: "View Bookings", description: "View bookings" },
  { code: "bookings.create", module: "insights", label: "Create Bookings", description: "Create bookings" },
  { code: "bookings.update", module: "insights", label: "Edit Bookings", description: "Update bookings" },
  { code: "bookings.delete", module: "insights", label: "Delete Bookings", description: "Delete bookings" },

  { code: "rentals.view", module: "insights", label: "View Rentals", description: "View item rentals" },
  { code: "rentals.create", module: "insights", label: "Create Rentals", description: "Create item rentals" },
  { code: "rentals.update", module: "insights", label: "Edit Rentals", description: "Update item rentals" },
  { code: "rentals.delete", module: "insights", label: "Delete Rentals", description: "Delete item rentals" },

  { code: "memos.view", module: "insights", label: "View Memos", description: "View transaction memos" },
  { code: "memos.upload", module: "insights", label: "Upload Memos", description: "Upload transaction memos" },
  { code: "memos.delete", module: "insights", label: "Delete Memos", description: "Delete transaction memos" },

  // System
  { code: "settings.view", module: "system", label: "View Settings", description: "View site settings" },
  { code: "settings.manage", module: "system", label: "Manage Settings", description: "Update site settings and SEO" },

  // Administration
  { code: "members.view", module: "administration", label: "View Team Members", description: "View admin members and roles" },
  { code: "members.create", module: "administration", label: "Create Members", description: "Add new admin/team members" },
  { code: "members.update", module: "administration", label: "Edit Members", description: "Update member roles and permissions" },
  { code: "members.delete", module: "administration", label: "Delete Members", description: "Deactivate or remove team members" },

  // Backup & Restore
  { code: "backup.view", module: "backup", label: "View Backups", description: "View backup history and status" },
  { code: "backup.create", module: "backup", label: "Create Backup", description: "Create complete database backup archive" },
  { code: "backup.restore", module: "backup", label: "Restore Backup", description: "Restore database from backup archive" },
];

export const ROLE_PRESETS: Record<string, { displayName: string; description: string; permissions: string[] }> = {
  SUPER_ADMIN: {
    displayName: "Super Admin",
    description: "Complete unrestricted access to all features, settings, team members, and full backup/restore.",
    permissions: ["*"],
  },
  ADMIN: {
    displayName: "Admin",
    description: "Full administrative access to manage store catalog, sales, finance, content, settings, and team members.",
    permissions: ALL_PERMISSIONS.map((p) => p.code).filter((code) => code !== "backup.restore"),
  },
  MANAGER: {
    displayName: "Store Manager",
    description: "Manage catalog products, orders, coupons, inventory, expenses, reports, bookings, and rentals.",
    permissions: [
      "dashboard.view",
      "products.view", "products.create", "products.update",
      "categories.view", "categories.create", "categories.update",
      "brands.view", "brands.create", "brands.update",
      "collections.view", "collections.create", "collections.update",
      "colors.view", "colors.create", "colors.update",
      "sizes.view", "sizes.create", "sizes.update",
      "vendors.view", "vendors.create", "vendors.update",
      "suppliers.view", "suppliers.create", "suppliers.update",
      "orders.view", "orders.update",
      "coupons.view", "coupons.create", "coupons.update",
      "expenses.view", "expenses.create", "expenses.update",
      "costs.view", "costs.create", "costs.update",
      "reports.view", "reports.export",
      "shipping.view",
      "payment_methods.view",
      "checkout_notices.view",
      "customers.view",
      "inventory.view", "inventory.manage",
      "bookings.view", "bookings.create", "bookings.update",
      "rentals.view", "rentals.create", "rentals.update",
      "memos.view", "memos.upload",
      "media.view", "media.upload",
      "banners.view", "banners.create", "banners.update",
      "analytics.view",
    ],
  },
  EDITOR: {
    displayName: "Content Editor",
    description: "Create and edit catalog products, categories, collections, homepage sections, banners, and media.",
    permissions: [
      "dashboard.view",
      "products.view", "products.create", "products.update",
      "categories.view", "categories.create", "categories.update",
      "brands.view", "brands.create", "brands.update",
      "collections.view", "collections.create", "collections.update",
      "homepage.view", "homepage.manage",
      "policies.view", "policies.manage",
      "media.view", "media.upload",
      "banners.view", "banners.create", "banners.update",
    ],
  },
  STAFF: {
    displayName: "Sales & Support Staff",
    description: "View and process customer orders, view inventory stock, and view customer directory.",
    permissions: [
      "dashboard.view",
      "orders.view", "orders.update",
      "inventory.view",
      "customers.view",
      "products.view",
    ],
  },
  CUSTOM: {
    displayName: "Custom Role",
    description: "Granular permissions explicitly customized per member.",
    permissions: [],
  },
};

export async function initializeRbac() {
  const connection = await pool.getConnection();
  try {
    // 1. Ensure columns exist on `users` table
    const [cols] = await connection.query<RowDataPacket[]>(
      "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
    );
    const existingCols = new Set(cols.map((c) => c.COLUMN_NAME));

    if (!existingCols.has("custom_role")) {
      await connection.query("ALTER TABLE `users` ADD COLUMN `custom_role` VARCHAR(50) NULL AFTER `role`");
    }
    if (!existingCols.has("permissions_json")) {
      await connection.query("ALTER TABLE `users` ADD COLUMN `permissions_json` TEXT NULL AFTER `custom_role`");
    }
    if (!existingCols.has("last_login_at")) {
      await connection.query("ALTER TABLE `users` ADD COLUMN `last_login_at` TIMESTAMP NULL AFTER `status`");
    }

    // 2. Create `admin_roles`
    await connection.query(`
      CREATE TABLE IF NOT EXISTS \`admin_roles\` (
        \`id\` INT AUTO_INCREMENT PRIMARY KEY,
        \`name\` VARCHAR(50) NOT NULL UNIQUE,
        \`display_name\` VARCHAR(100) NOT NULL,
        \`description\` TEXT NULL,
        \`is_system\` BOOLEAN NOT NULL DEFAULT FALSE,
        \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 3. Create `admin_permissions`
    await connection.query(`
      CREATE TABLE IF NOT EXISTS \`admin_permissions\` (
        \`id\` INT AUTO_INCREMENT PRIMARY KEY,
        \`code\` VARCHAR(100) NOT NULL UNIQUE,
        \`module\` VARCHAR(50) NOT NULL,
        \`label\` VARCHAR(150) NOT NULL,
        \`description\` TEXT NULL,
        \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 4. Create `role_permissions`
    await connection.query(`
      CREATE TABLE IF NOT EXISTS \`role_permissions\` (
        \`id\` INT AUTO_INCREMENT PRIMARY KEY,
        \`role_name\` VARCHAR(50) NOT NULL,
        \`permission_code\` VARCHAR(100) NOT NULL,
        \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY \`idx_role_permission\` (\`role_name\`, \`permission_code\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 5. Create `user_permissions`
    await connection.query(`
      CREATE TABLE IF NOT EXISTS \`user_permissions\` (
        \`id\` INT AUTO_INCREMENT PRIMARY KEY,
        \`user_id\` INT NOT NULL,
        \`permission_code\` VARCHAR(100) NOT NULL,
        \`granted\` BOOLEAN NOT NULL DEFAULT TRUE,
        \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY \`idx_user_permission\` (\`user_id\`, \`permission_code\`),
        CONSTRAINT \`fk_user_permissions_user\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 6. Create `admin_audit_logs`
    await connection.query(`
      CREATE TABLE IF NOT EXISTS \`admin_audit_logs\` (
        \`id\` INT AUTO_INCREMENT PRIMARY KEY,
        \`actor_id\` INT NULL,
        \`actor_name\` VARCHAR(255) NOT NULL,
        \`actor_email\` VARCHAR(255) NULL,
        \`action\` VARCHAR(100) NOT NULL,
        \`target_type\` VARCHAR(50) NULL,
        \`target_id\` VARCHAR(100) NULL,
        \`details\` TEXT NULL,
        \`ip_address\` VARCHAR(100) NULL,
        \`user_agent\` VARCHAR(500) NULL,
        \`status\` ENUM('success', 'failure') NOT NULL DEFAULT 'success',
        \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_audit_actor\` (\`actor_id\`),
        INDEX \`idx_audit_action\` (\`action\`),
        INDEX \`idx_audit_created\` (\`created_at\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 7. Create `admin_backups`
    await connection.query(`
      CREATE TABLE IF NOT EXISTS \`admin_backups\` (
        \`id\` INT AUTO_INCREMENT PRIMARY KEY,
        \`filename\` VARCHAR(255) NOT NULL UNIQUE,
        \`filepath\` VARCHAR(500) NOT NULL,
        \`size\` INT NOT NULL DEFAULT 0,
        \`type\` ENUM('manual', 'safety_auto') NOT NULL DEFAULT 'manual',
        \`table_count\` INT NOT NULL DEFAULT 0,
        \`record_count\` INT NOT NULL DEFAULT 0,
        \`created_by_id\` INT NULL,
        \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_backup_created\` (\`created_at\`),
        INDEX \`idx_backup_type\` (\`type\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 8. Seed/Update permission catalog
    for (const perm of ALL_PERMISSIONS) {
      await connection.query(
        "INSERT INTO `admin_permissions` (`code`, `module`, `label`, `description`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `module` = VALUES(`module`), `label` = VALUES(`label`), `description` = VALUES(`description`)",
        [perm.code, perm.module, perm.label, perm.description]
      );
    }

    // 9. Seed/Update roles & role permissions
    for (const [roleName, preset] of Object.entries(ROLE_PRESETS)) {
      await connection.query(
        "INSERT INTO `admin_roles` (`name`, `display_name`, `description`, `is_system`) VALUES (?, ?, ?, TRUE) ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`), `description` = VALUES(`description`)",
        [roleName, preset.displayName, preset.description]
      );

      for (const permCode of preset.permissions) {
        await connection.query(
          "INSERT IGNORE INTO `role_permissions` (`role_name`, `permission_code`) VALUES (?, ?)",
          [roleName, permCode]
        );
      }
    }

    // 10. Update existing admin users with customRole = 'SUPER_ADMIN' if not set
    await connection.query(
      "UPDATE `users` SET `custom_role` = 'SUPER_ADMIN' WHERE (`role` = 'admin' OR `id` = 240011) AND (`custom_role` IS NULL OR `custom_role` = '')"
    );
    await connection.query(
      "UPDATE `users` SET `custom_role` = 'MANAGER' WHERE `role` = 'manager' AND (`custom_role` IS NULL OR `custom_role` = '')"
    );

    console.log("RBAC, Audit Logs, and Backup tables initialized successfully.");
  } finally {
    connection.release();
  }
}
