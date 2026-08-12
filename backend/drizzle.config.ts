import type { Config } from "drizzle-kit";
import dotenv from "dotenv";

dotenv.config();

export default {
  schema: "./src/config/schema.ts",
  out: "./drizzle",
  dialect: "mysql",

  tablesFilter: [
    "banners",
    "bookings",
    "brands",
    "categories",
    "checkout_notices",
    "collections",
    "colors",
    "contact_messages",
    "costs",
    "coupons",
    "expense_categories",
    "expenses",
    "marketing_integrations",
    "media_assets",
    "memos",
    "newsletters",
    "order_items",
    "order_status_history",
    "orders",
    "payment_methods",
    "policy_pages",
    "product_relations",
    "product_specs",
    "product_variants",
    "products",
    "rentals",
    "reviews",
    "shipping_methods",
    "site_settings",
    "sizes",
    "suppliers",
    "tracking_logs",
    "user_addresses",
    "users",
    "vendors",
  ],

  dbCredentials: {
    url: process.env.DATABASE_URL! + '?ssl={"rejectUnauthorized":true}',
  },
} satisfies Config;