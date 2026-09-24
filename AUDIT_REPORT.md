# Comprehensive Audit Report: Full-Stack Migration to Laravel

**Date**: September 24, 2026  
**Source of Truth (UI / UX)**: `/frontend` (React + TypeScript + Tailwind CSS + Framer Motion + RTK Query)  
**Source of Truth (API & Business Logic)**: `/backend` (Node.js + Express + Drizzle ORM)  
**Destination**: `/backend-laravel` (Laravel 11 + Blade + Tailwind CSS + Alpine.js + SQLite + Local Storage)

---

## 1. Executive Summary

This audit provides an exhaustive, code-verified inventory of every route, page, component, API endpoint, database table, permission, and design token across the Mama Bazar application.

The migration will replace the Node.js backend with Laravel 11 while fulfilling three distinct consumption tiers from a single unified codebase:
1. **REST API Tier**: 100% parity with Node.js Express endpoints so the existing React SPA can continue operating uninterrupted without frontend code changes.
2. **User-Facing Website (Blade)**: A faithful, pixel-perfect recreation of the React storefront (Homepage, Shop, Product Details, Cart, Checkout, Order Tracking, User Dashboard, Policy Pages, Auth) using Blade + Tailwind CSS + Alpine.js.
3. **Admin Panel (Blade)**: A recreation of the React Admin Panel, replicating the exact 10-section sidebar, topbar, dashboard, product management (already completed and verified), order management, catalog management, checkout configuration, financial expenses, customer management, RBAC, backups, and settings.
4. **Shared Service Layer**: Single source of truth in Laravel Service classes (`ProductService`, `OrderService`, `CategoryService`, `HomepageService`, etc.) powering REST controllers, Public Blade controllers, and Admin Blade controllers.
5. **Local Storage & SQLite**: Complete removal of external cloud providers (Cloudinary/S3). All media stored locally in `storage/app/public` and exposed via `public/storage`. Database runs on SQLite (`database/database.sqlite`).

---

## 2. Route Audit

### A. Public & User-Facing Storefront Routes

| Path | React Component | Layout | Purpose | Authentication |
| :--- | :--- | :--- | :--- | :--- |
| `/` | `HomePage.tsx` | `MainLayout` | Storefront landing page with 15 dynamic sections | Public |
| `/shop` | `ShopPage.tsx` | `MainLayout` | Product catalog with 7 sort options, category, brand, price, rating, sale, and stock filters | Public |
| `/products/:slug` | `ProductDetailsPage.tsx` | `MainLayout` | Product details, variant selector, specs, image zoom gallery, reviews, related products | Public |
| `/cart` | `CartPage.tsx` | `MainLayout` | Cart item management, quantities, order summary, clear cart | Public |
| `/checkout` | `CheckoutPage.tsx` | `MainLayout` | Checkout form with Bangladesh location cascades, shipping methods, coupon validator, payment methods (COD, bKash, Nagad, Rocket), notices | Public |
| `/track` | `OrderTrackingPage.tsx` | `MainLayout` | Order tracking by Order ID or phone with visual 9-step timeline flow | Public |
| `/order/success` | `OrderSuccessPage.tsx` | `MainLayout` | Order placement confirmation, summary, next steps | Public |
| `/about` | `AboutPage.tsx` | `MainLayout` | About Mama Bazar, company mission, team section | Public |
| `/faq` | `FaqPage.tsx` | `MainLayout` | Frequently asked questions accordion grouped by categories | Public |
| `/contact` | `ContactPage.tsx` | `MainLayout` | Contact info, business hours, interactive contact message submission form | Public |
| `/refund-policy` | `PolicyPage.tsx` (`return-refund`) | `MainLayout` | Returns and refunds policy document | Public |
| `/return-refund-policy` | `PolicyPage.tsx` (`return-refund`) | `MainLayout` | Alias for return-refund policy | Public |
| `/shipping-policy` | `PolicyPage.tsx` (`shipping`) | `MainLayout` | Shipping and delivery times/rates policy | Public |
| `/privacy-policy` | `PrivacyPolicyPage.tsx` | `MainLayout` | Customer data privacy policy | Public |
| `/terms-and-conditions` | `TermsAndConditionsPage.tsx`| `MainLayout` | Terms and conditions of service | Public |
| `/cookie-policy` | `CookiePolicyPage.tsx` | `MainLayout` | Browser cookie policy | Public |
| `/payment-policy` | `PolicyPage.tsx` (`payment`) | `MainLayout` | Payment terms and security policy | Public |
| `/cancellation-policy`| `PolicyPage.tsx` (`cancellation`)| `MainLayout` | Order cancellation rules | Public |
| `/warranty-policy` | `PolicyPage.tsx` (`warranty`) | `MainLayout` | Warranty claims and duration guidelines | Public |
| `/auth/login` | `LoginPage.tsx` | `AuthLayout` | Customer login with phone/email and password, dev login button | Guest |
| `/auth/register` | `RegisterPage.tsx` | `AuthLayout` | Customer registration with validation | Guest |
| `/auth/forgot-password`| `ForgotPasswordPage.tsx` | `AuthLayout` | Password reset request with phone | Guest |
| `/dashboard` | Redirect to `/dashboard/overview`| `UserDashboardLayout` | User dashboard entry point | Customer (`user`) |
| `/dashboard/overview` | `DashboardOverviewPage.tsx` | `UserDashboardLayout` | Recent orders, profile quick cards, stats | Customer (`user`) |
| `/dashboard/orders` | `DashboardOrdersPage.tsx` | `UserDashboardLayout` | Customer order history, item breakdown, status pills, tracking links | Customer (`user`) |
| `/dashboard/profile` | `DashboardProfilePage.tsx` | `UserDashboardLayout` | Name, phone, shipping area, address updater | Customer (`user`) |
| `/dashboard/addresses`| `DashboardAddressesPage.tsx`| `UserDashboardLayout` | Multiple address book management (Create, Edit, Delete, Default toggle) | Customer (`user`) |
| `/dashboard/security` | `DashboardSecurityPage.tsx` | `UserDashboardLayout` | Change account password form | Customer (`user`) |
| `/dashboard/legacy` | `UserDashboardPage.tsx` | `UserDashboardLayout` | Legacy dashboard fallback | Customer (`user`) |

---

### B. Admin Panel Routes (`/admin`)

Guarded by `AdminRoute` (`allowedRoles: ['admin', 'manager']`) and `PermissionRoute`:

| Route Path | React Page Component | Permission Required | Purpose & Key Features |
| :--- | :--- | :--- | :--- |
| `/admin/dashboard` | `AdminDashboardPage.tsx` | `dashboard.view` | Metric cards (Revenue, Orders, Products, Customers), sales chart, recent orders table, low stock alert list |
| `/admin/products` | `AdminProductListPage.tsx` | `products.view` | Master product table with 10 filters, bulk actions bar, featured toggle, CSV export/import |
| `/admin/products/create` | `AdminProductCreatePage.tsx` | `products.create` | 8-section product builder (Basic, Pricing, Inventory, Media, Variants, Specs, SEO, Badges) |
| `/admin/products/:id` | `AdminProductViewPage.tsx` | `products.view` | Detailed product summary, image gallery, specs, variants list, SEO card |
| `/admin/products/:id/edit` | `AdminProductEditPage.tsx` | `products.update` | 8-section product editor preloaded with variants, specs, and relationships |
| `/admin/categories` | `AdminCategoriesPage.tsx` | `categories.view` | Category tree, image upload, parent-child cascades, delete with move-products modal |
| `/admin/brands` | `AdminBrandsPage.tsx` | `brands.view` | Brand directory, logos, featured brand toggles, product counts |
| `/admin/collections` | `AdminCollectionsPage.tsx` | `collections.view` | Curated collections table, banner uploads, status toggles |
| `/admin/colors` | `AdminColorsPage.tsx` | `colors.view` | Catalog color swatches, hex picker, sort order |
| `/admin/sizes` | `AdminSizesPage.tsx` | `sizes.view` | Catalog size options, sort order, code |
| `/admin/vendors` | `AdminVendorsPage.tsx` | `vendors.view` | Vendor directory, contact details, product counts |
| `/admin/suppliers` | `AdminSuppliersPage.tsx` | `suppliers.view` | Supplier registry, contact numbers, warehouse addresses |
| `/admin/orders` | `AdminOrdersPage.tsx` | `orders.view` | Order list with status tabs, customer details, courier tracking input, status updater |
| `/admin/orders/:id/invoice`| `AdminOrderInvoicePage.tsx` | `orders.view` | Printable thermal / A4 customer invoice |
| `/admin/shipping` | `AdminShippingPage.tsx` | `shipping.view` | Shipping methods, delivery charges, free shipping thresholds, status |
| `/admin/payment-methods` | `AdminPaymentMethodsPage.tsx`| `payment_methods.view` | Payment gateway toggles (bKash, Nagad, COD, etc.), merchant numbers, instructions |
| `/admin/checkout-notices` | `AdminCheckoutNoticesPage.tsx`| `checkout_notices.view` | Promotional and emergency notices shown on checkout page |
| `/admin/coupons` | `AdminCouponsPage.tsx` | `coupons.view` | Discount coupons (percent/fixed), minimum spend, expiration, usage limits |
| `/admin/marketing` | `AdminMarketingPage.tsx` | `marketing.view` | Tracking pixels (Facebook Pixel, Google Tag Manager, TikTok Pixel) |
| `/admin/expenses` | `AdminExpensesPage.tsx` | `expenses.view` | Operational expenses, receipts, cost tracking |
| `/admin/expenses/categories`| `AdminExpenseCategoriesPage.tsx`| `expenses.view` | Expense category taxonomy |
| `/admin/expenses/reports` | `AdminExpenseReportsPage.tsx` | `reports.view` | Monthly/yearly expense reports, category breakdown, profit overview |
| `/admin/customers` | `AdminCustomersPage.tsx` | `customers.view` | Registered customer roster, order history counts, status toggles |
| `/admin/homepage` | `AdminHomepagePage.tsx` | `homepage.view` | Homepage visual section builder, enable/disable switches, reordering |
| `/admin/policies` | `AdminPoliciesPage.tsx` | `policies.view` | Legal policy editor (HTML/Markdown) & contact message inbox |
| `/admin/media` | `AdminMediaPage.tsx` | `media.view` | Media asset gallery, folder grouping, uploads, alt text editor |
| `/admin/banners` | `AdminBannersPage.tsx` | `banners.view` | Promotional banners, target links, active dates |
| `/admin/analytics` | `AdminAnalyticsPage.tsx` | `analytics.view` | Business analytics, customer acquisition, product performance |
| `/admin/members` | `AdminMembersPage.tsx` | `members.view` | Admin users, role assignments (SUPER_ADMIN, MANAGER, etc.), custom permission matrices |
| `/admin/backup` | `AdminBackupPage.tsx` | `backup.view` | Database backups list, download, create backup, PIN-protected restore |
| `/admin/inventory` | `AdminInventoryPage.tsx` | `inventory.view` | Quick stock count and low stock threshold updater table |
| `/admin/settings` | `AdminSettingsPage.tsx` | `settings.manage` | Store information (name, logo, phone, address), currency settings, social links |

---

## 3. Homepage In-Depth Audit

### A. Structure & Sections
The React homepage is driven by `HomePage.tsx` -> `HomepageSections.tsx` -> `SectionRenderer.tsx`.
Configuration is retrieved from `/api/homepage/config` (with default fallback to `DEFAULT_HOMEPAGE_SECTIONS`):

1. **Hero (`HeroCarousel.tsx`)**:
   - Multi-slide carousel with auto-play (4.5s intervals), pause on hover, keyboard navigation.
   - High-contrast typography with overlay controls, subtitle, primary/secondary CTA pill buttons.
   - Popular searches quick pill tags bar (`/shop?search=...`).
2. **Trust Strip (`TrustStrip.tsx`)**:
   - 4 confidence badges: Fast Delivery Across Bangladesh, 100% Authentic Products, Easy Returns & Refunds, 24/7 Customer Support.
3. **Categories (`CategoryGrid.tsx`)**:
   - Horizontal snap-scroll rail with left/right circular buttons.
   - Rounded category chips with circular thumbnail and category title.
4. **New Arrivals (`ProductCarousel.tsx`)**:
   - Product rail displaying products flagged with `isNewArrival` or label `new_arrival`.
5. **Promo Banner 1 (`PromoBanner.tsx`)**:
   - Wide promotional image banner with call-to-action button.
6. **Featured Products (`ProductCarousel.tsx`)**:
   - Product rail displaying products flagged with `isFeatured` or label `featured`.
7. **Brands Row (`BrandRow.tsx`)**:
   - Horizontal scrolling list of authentic brand logos linking to `/shop?brand=:slug`.
8. **Promo Banner 2 (`PromoBanner.tsx`)**:
   - Secondary full-width promotional banner.
9. **Collections Tiles (`CollectionTiles.tsx`)**:
   - Curated product lifestyle bundles (6 tiles with cover images and direct links).
10. **Flash Deals (`FlashDeals.tsx`)**:
    - High-urgency section with animated flame icon, live countdown timer (Hours:Minutes:Seconds), discounted pricing, and product rail.
11. **Best Sellers (`ProductCarousel.tsx`)**:
    - Product rail displaying products flagged with `isBestSeller` or label `best_seller`.
12. **Trending Right Now (`ProductCarousel.tsx`)**:
    - Products flagged with `isTrending` or label `trending`.
13. **Customer Reviews (`ReviewsSection.tsx`)**:
    - Real verified customer testimonials with star ratings, avatar initials, and review comment.
14. **Why Choose Us (`WhyChooseUs.tsx`)**:
    - Value proposition cards with icons, bold titles, and descriptions.
15. **Newsletter (`NewsletterBlock.tsx`)**:
    - Email subscription card with discount incentive text and instant AJAX submission to `/api/homepage/newsletter/subscribe`.

### B. Product Card Component (`ProductCard.tsx`)
The universal storefront card contains:
- Image frame (fixed height, white background, `object-contain`, smooth hover scale 1.06).
- Badges with strict priority: Discount % badge (`-X%` orange) > Flash Sale badge > New Arrival badge > Best Seller badge.
- Hover quick action buttons: **Quick View** (opens modal) and **Wishlist** (heart icon toggle).
- Out of stock overlay (if `stock <= 0`).
- Brand name uppercase small pill and star rating with review count.
- 2-line clamped product title.
- Price row: Effective sale price (bold), original crossed-out price, and low stock warning (`Only X left`).
- Interactive Color swatches (circular color dots with active ring) and Size pills.
- Add to Cart button (turns green with checkmark upon click for 900ms feedback).

---

## 4. Admin Panel & Sidebar Audit

### A. Sidebar Navigation (`adminNav.ts`)
The sidebar features a collapsible drawer with 10 categorized groups:

1. **Overview**
   - Dashboard (`LayoutDashboard`, `/admin/dashboard`) [permission: `dashboard.view`]
2. **Catalog**
   - Products (`Package`, `/admin/products`) [permission: `products.view`]
   - Categories (`Tags`, `/admin/categories`) [permission: `categories.view`]
   - Brands (`Stamp`, `/admin/brands`) [permission: `brands.view`]
   - Collections (`FolderOpen`, `/admin/collections`) [permission: `collections.view`]
   - Colors (`Palette`, `/admin/colors`) [permission: `colors.view`]
   - Sizes (`Ruler`, `/admin/sizes`) [permission: `sizes.view`]
   - Vendors (`Store`, `/admin/vendors`) [permission: `vendors.view`]
   - Suppliers (`Truck`, `/admin/suppliers`) [permission: `suppliers.view`]
3. **Sales**
   - Orders (`ShoppingCart`, `/admin/orders`) [permission: `orders.view`]
   - Coupons (`TicketPercent`, `/admin/coupons`) [permission: `coupons.view`]
   - Marketing (`Megaphone`, `/admin/marketing`) [permission: `marketing.view`]
4. **Finance**
   - Expenses (`ReceiptText`, `/admin/expenses`) [permission: `expenses.view`]
   - Expense Categories (`ListOrdered`, `/admin/expenses/categories`) [permission: `expenses.view`]
   - Expense Reports (`ChartPie`, `/admin/expenses/reports`) [permission: `reports.view`]
   - Profit Overview (`Wallet`, `/admin/expenses/reports?tab=profit`) [permission: `reports.view`]
5. **Checkout**
   - Shipping Methods (`MapPin`, `/admin/shipping`) [permission: `shipping.view`]
   - Payment Methods (`CreditCard`, `/admin/payment-methods`) [permission: `payment_methods.view`]
   - Checkout Notices (`BellRing`, `/admin/checkout-notices`) [permission: `checkout_notices.view`]
6. **Customers**
   - Customers (`Users`, `/admin/customers`) [permission: `customers.view`]
7. **Content**
   - Homepage Builder (`PanelsTopLeft`, `/admin/homepage`) [permission: `homepage.view`]
   - Policies & Messages (`FileText`, `/admin/policies`) [permission: `policies.view`]
   - Media Library (`Image`, `/admin/media`) [permission: `media.view`]
   - Banners (`Megaphone`, `/admin/banners`) [permission: `banners.view`]
8. **Insights**
   - Analytics (`BarChart3`, `/admin/analytics`) [permission: `analytics.view`]
9. **Security & Access**
   - Team Members (`UserCheck`, `/admin/members`) [permission: `members.view`]
   - Backup & Restore (`DatabaseBackup`, `/admin/backup`) [permission: `backup.view`]
10. **System**
    - Inventory (`Boxes`, `/admin/inventory`) [permission: `inventory.view`]
    - Settings (`Settings`, `/admin/settings`) [permission: `settings.manage`]

### B. Admin Topbar (`AdminTopbar.tsx`)
- Sidebar collapse/expand toggle button.
- Breadcrumb indicator displaying Section > Current Page.
- Search button triggering `CommandPalette` (`⌘K`).
- Notifications bell with live badge count (new pending orders, low stock items).
- User avatar with initials and dropdown (Profile, Settings, Logout).

---

## 5. Design System Audit

- **Color Palette**:
  - `brand-green-500`: `#176B3A` (Primary brand color, header, navigation, trust marks).
  - `brand-green-600`: `#0F4D2C` (Deep green accent, announcements, footer elements).
  - `brand-green-50`: `#EAF6EF` (Soft green background for chips, badges, icons).
  - `brand-orange-500`: `#F47B20` (CTA buttons, Add to Cart, discounts, sale alerts).
  - `brand-orange-50`: `#FFF1E6` (Soft orange background for deal pills).
  - `canvas-light`: `#FFFFFF` (Card surfaces, inputs, modal backgrounds).
  - `canvas-cream`: `#F8FAF8` (Storefront background).
  - Slate scales (`slate-900`, `slate-700`, `slate-500`, `slate-200`, `slate-100`).
- **Typography**:
  - Latin Display & Headline: Inter (`400`, `500`, `600`, `700`).
  - Bengali Script: Noto Sans Bengali (`400`, `600`, `700`) with natural line-height to prevent clipped marks.
- **Components & Borders**:
  - `rounded-2xl` (16px) for cards, modals, section shells.
  - `rounded-full` (9999px) for buttons, chips, avatar rings.
  - Hover effects: GPU-composited `translateY(-4px)` with opacity fade on pre-painted `::after` shadow.

---

## 6. Node.js Backend & API Audit

The Node.js backend (`/backend/src/app.ts`) routes requests across 30 modules:
- `/api/users`: Registration, JWT login, profile, password reset, address book, admin user CRUD.
- `/api/categories`: Tree hierarchy, slug lookup, product counts, admin category CRUD.
- `/api/products`: Full catalog filtering, slug lookup, variant generation, bulk actions, CSV export/import.
- `/api/order`: Order creation, public tracking by phone/ID, admin status updates, courier tracking, invoice generation.
- `/api/coupons`: Coupon validation, CRUD.
- `/api/settings`: Store information, hero slides, dynamic settings dictionary.
- `/api/tracking`: Marketing pixels (Facebook, TikTok, Google Analytics).
- `/api/analytics`: Revenue, order stats, top products.
- `/api/brands`: Brand directory, slug lookup, CRUD.
- `/api/banners`: Promotional banners CRUD.
- `/api/media`: Local media asset uploads, folder management, alt text.
- `/api/colors`, `/api/sizes`, `/api/collections`, `/api/vendors`, `/api/suppliers`: Master catalog taxonomy CRUD.
- `/api/shipping-methods`: Delivery zone rules and pricing.
- `/api/payment-methods`: Gateway configuration and toggle switches.
- `/api/checkout-notices`: Dynamic checkout banners.
- `/api/reviews`: Product ratings and reviews.
- `/api/homepage`: Homepage configuration, newsletter subscriptions.
- `/api/pages`: Static pages and contact messages.
- `/api/expenses`: Expense tracking, categories, monthly reports, profit analysis.
- `/api/costs`, `/api/bookings`, `/api/rentals`, `/api/memos`: Auxiliary management modules.
- `/api/members`: Admin team RBAC, roles, permissions, audit logs.
- `/api/backup`: Backup creation, verification PIN, restore, history.

---

## 7. Database Entity & Schema Audit

All 41 tables defined in Drizzle schema (`backend/src/config/schema.ts`) are mapped 1-to-1 to Eloquent models in Laravel:

1. `users`: Customers and admin accounts (phone, name, email, password, role, custom_role, status).
2. `user_addresses`: Customer address book with default selection flags.
3. `categories`: Nested category hierarchy with `parent_id`, images, SEO fields.
4. `products`: Core product entity with pricing, stock thresholds, flags, JSON options.
5. `product_variants`: Size/Color SKU variants with independent pricing and stock.
6. `product_specs`: Key-value specification attributes.
7. `product_relations`: Cross-sells and related product links.
8. `colors`: Color swatches with hex codes.
9. `sizes`: Size labels and display orders.
10. `collections`: Product bundles and collections.
11. `brands`: Product brands and manufacturers.
12. `vendors`: Retail vendors.
13. `suppliers`: Wholesale suppliers.
14. `orders`: Customer orders, subtotal, shipping charge, discount, payment status, order status, courier info.
15. `order_items`: Line items with product, variant, quantity, unit price.
16. `order_status_history`: Audit trail of order status changes with notes.
17. `coupons`: Promotional discount codes.
18. `banners`: Promotional header and section banners.
19. `media_assets`: Uploaded media library assets with folders.
20. `site_settings`: Global key-value store configurations.
21. `policy_pages`: CMS policy documents (slug, title, markdown/HTML content).
22. `contact_messages`: Inquiries submitted via Contact Us form.
23. `marketing_integrations`: Pixel and script tracking tags.
24. `tracking_logs`: Event tracking logs.
25. `shipping_methods`: Shipping rate rules and delivery zones.
26. `payment_methods`: Configured payment gateways and mobile banking accounts.
27. `checkout_notices`: Checkout announcement banners.
28. `reviews`: Customer reviews and star ratings.
29. `newsletters`: Newsletter email subscriber list.
30. `expense_categories`: Expense taxonomy.
31. `expenses`: Operational business expenditures.
32. `costs`: Direct cost ledger.
33. `bookings`: Service reservations.
34. `rentals`: Equipment rental bookings.
35. `memos`: Attached document memos.
36. `admin_roles`: System and custom RBAC roles.
37. `admin_permissions`: Granular capability codes.
38. `role_permissions`: Role-to-permission mappings.
39. `user_permissions`: Individual user permission overrides.
40. `admin_audit_logs`: Activity log recording administrative actions.
41. `admin_backups`: Database snapshot records with restore metadata.
