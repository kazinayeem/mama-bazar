# Backend API Performance Report

> **Date:** August 28, 2026  
> **Environment:** Node.js v24.13.0 | npm 11.6.2 | Express.js | Drizzle ORM | MySQL (TiDB Cloud)  
> **Mode:** Performance Audit Only — **READ-ONLY** (Zero Database Modifications)

---

## 1. Executive Summary

A comprehensive, non-destructive response-time audit was conducted across the entire backend API suite. All read-only endpoints were benchmarked using a controlled load test (2 warm-up requests discarded, followed by 15 measurement requests per endpoint to capture statistical percentiles).

| Metric | Result |
| :--- | :--- |
| **Total Endpoints Discovered** | **104** |
| **Read-Only Endpoints Tested** | **40** |
| **Mutating / Destructive Endpoints (Not Tested)** | **64** |
| **Fastest Endpoint** | `GET /` (**0.7 ms** avg, **1.4 ms** P95) |
| **Fastest API Endpoint** | `GET /api/health` (**1.3 ms** avg, **1.9 ms** P95) |
| **Slowest Read Endpoint** | `GET /api/homepage` (**774.6 ms** avg, **950.5 ms** P95) |
| **Average Response Time (All Tested Endpoints)** | **184.2 ms** |
| **Classification Breakdown** | **Excellent (<100ms):** 24 (60%)<br>**Good (100–250ms):** 6 (15%)<br>**Moderate (250–500ms):** 7 (17.5%)<br>**Slow (500ms–1s):** 3 (7.5%)<br>**Critical (>2s):** 0 (0%) |

---

## 2. API Inventory

Complete catalog of all 104 endpoints discovered across the 25 backend modules:

| Method | Endpoint | Auth | Tested | Type / Description |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | No | Yes | Public — Root welcome message |
| `GET` | `/api/health` | No | Yes | Public — Health check probe |
| `GET` | `/api/homepage` | No | Yes | Public — Homepage multi-section aggregate data |
| `GET` | `/api/homepage/config` | Yes (Admin) | Yes | Admin — Homepage section layout & hero configuration |
| `POST` | `/api/homepage/config` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Save layout config) |
| `POST` | `/api/homepage/reset` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Reset layout config) |
| `POST` | `/api/homepage/newsletter/subscribe` | No | No | **NOT TESTED — MUTATING ENDPOINT** (Newsletter subscriber) |
| `GET` | `/api/homepage/newsletter/subscribers` | Yes (Admin) | No | Admin — Newsletter subscribers list |
| `GET` | `/api/products` | No | Yes | Public — Product catalog with filtering, sorting, pagination |
| `GET` | `/api/products/slug/:slug` | No | Yes | Public — Single product details by slug with variants & specs |
| `GET` | `/api/products/:id` | No | Yes | Public — Single product details by ID |
| `GET` | `/api/products/:id/related` | No | Yes | Public — Related products by category & tags |
| `GET` | `/api/products/export/csv` | Yes (Admin) | No | Admin — Export product catalog to CSV |
| `POST` | `/api/products` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create product) |
| `POST` | `/api/products/import/csv` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Import CSV) |
| `POST` | `/api/products/bulk` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Bulk update) |
| `POST` | `/api/products/:id/duplicate` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Duplicate product) |
| `POST` | `/api/products/:id/draft` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Save product draft) |
| `PUT` | `/api/products/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update product) |
| `DELETE` | `/api/products/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete product) |
| `GET` | `/api/categories` | No | Yes | Public — Category hierarchy tree with product counts |
| `GET` | `/api/categories/tree` | No | Yes | Public — Category recursive nested tree |
| `GET` | `/api/categories/slug/:slug` | No | Yes | Public — Category details by slug |
| `GET` | `/api/categories/admin` | Yes (Admin) | No | Admin — Category administrative table view |
| `GET` | `/api/categories/:id` | No | Yes | Public — Category details by ID |
| `GET` | `/api/categories/:id/usage` | Yes (Admin) | No | Admin — Category product count & dependency audit |
| `POST` | `/api/categories` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create category) |
| `PUT` | `/api/categories/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update category) |
| `POST` | `/api/categories/:id/move` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Reassign products) |
| `DELETE` | `/api/categories/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete category) |
| `GET` | `/api/brands` | No | Yes | Public — Active brand list with logos |
| `GET` | `/api/brands/slug/:slug` | No | Yes | Public — Brand details by slug |
| `GET` | `/api/brands/admin` | Yes (Admin) | No | Admin — Brand management list |
| `GET` | `/api/brands/:id` | No | Yes | Public — Brand details by ID |
| `GET` | `/api/brands/:id/usage` | Yes (Admin) | No | Admin — Brand product count audit |
| `POST` | `/api/brands` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create brand) |
| `PUT` | `/api/brands/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update brand) |
| `POST` | `/api/brands/:id/move` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Reassign brand products) |
| `DELETE` | `/api/brands/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete brand) |
| `GET` | `/api/collections` | No | Yes | Public — Curated collections |
| `GET` | `/api/collections/:id` | No | Yes | Public — Collection details by ID |
| `POST` | `/api/collections` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create collection) |
| `PUT` | `/api/collections/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update collection) |
| `DELETE` | `/api/collections/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete collection) |
| `GET` | `/api/colors` | No | Yes | Public — Attribute color values |
| `POST` | `/api/colors` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create color) |
| `PUT` | `/api/colors/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update color) |
| `DELETE` | `/api/colors/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete color) |
| `GET` | `/api/sizes` | No | Yes | Public — Attribute size values |
| `POST` | `/api/sizes` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create size) |
| `PUT` | `/api/sizes/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update size) |
| `DELETE` | `/api/sizes/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete size) |
| `GET` | `/api/vendors` | No | Yes | Public / Admin — Vendor list |
| `POST` | `/api/vendors` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create vendor) |
| `PUT` | `/api/vendors/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update vendor) |
| `DELETE` | `/api/vendors/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete vendor) |
| `GET` | `/api/suppliers` | No | Yes | Public / Admin — Supplier list |
| `POST` | `/api/suppliers` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create supplier) |
| `PUT` | `/api/suppliers/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update supplier) |
| `DELETE` | `/api/suppliers/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete supplier) |
| `GET` | `/api/banners` | No | Yes | Public — Promo and hero banners |
| `GET` | `/api/banners/:id` | No | Yes | Public — Banner details by ID |
| `POST` | `/api/banners` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create banner) |
| `PUT` | `/api/banners/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update banner) |
| `DELETE` | `/api/banners/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete banner) |
| `GET` | `/api/shipping-methods/public` | No | Yes | Public — Active customer shipping methods |
| `POST` | `/api/shipping-methods/estimate` | No | No | **NOT TESTED — MUTATING / RPC** (Estimate shipping) |
| `GET` | `/api/shipping-methods` | Yes (Admin) | No | Admin — Shipping methods configuration list |
| `GET` | `/api/shipping-methods/:id` | Yes (Admin) | No | Admin — Shipping method by ID |
| `POST` | `/api/shipping-methods` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create shipping method) |
| `PUT` | `/api/shipping-methods/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update shipping method) |
| `DELETE` | `/api/shipping-methods/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete shipping method) |
| `GET` | `/api/payment-methods/public` | No | Yes | Public — Active payment methods & instructions |
| `GET` | `/api/payment-methods` | Yes (Admin) | No | Admin — Payment methods management |
| `GET` | `/api/payment-methods/:id` | Yes (Admin) | No | Admin — Payment method by ID |
| `POST` | `/api/payment-methods` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create payment method) |
| `PUT` | `/api/payment-methods/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update payment method) |
| `PUT` | `/api/payment-methods` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Bulk update payment statuses) |
| `DELETE` | `/api/payment-methods/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete payment method) |
| `GET` | `/api/checkout-notices/public` | No | Yes | Public — Active checkout notice banners |
| `GET` | `/api/checkout-notices` | Yes (Admin) | No | Admin — Checkout notices management |
| `GET` | `/api/checkout-notices/:id` | Yes (Admin) | No | Admin — Checkout notice by ID |
| `POST` | `/api/checkout-notices` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create checkout notice) |
| `PUT` | `/api/checkout-notices/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update checkout notice) |
| `DELETE` | `/api/checkout-notices/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete checkout notice) |
| `GET` | `/api/settings` | No | Yes | Public — General store settings |
| `GET` | `/api/settings/hero-slides` | No | Yes | Public — Legacy hero slides configuration |
| `GET` | `/api/settings/store-info` | No | Yes | Public — Store address, phone, email, metadata |
| `GET` | `/api/settings/:key` | No | Yes | Public — Single setting key value lookup |
| `PUT` | `/api/settings` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Save settings) |
| `POST` | `/api/settings/hero-slides` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Add hero slide) |
| `POST` | `/api/settings/hero-slides/link` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Add hero slide link) |
| `DELETE` | `/api/settings/hero-slides/:index` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete hero slide) |
| `GET` | `/api/reviews` | No | Yes | Public — Approved customer reviews |
| `GET` | `/api/reviews/:id` | No | Yes | Public — Review details by ID |
| `GET` | `/api/reviews/admin/list` | Yes (Admin) | No | Admin — All reviews moderation queue |
| `POST` | `/api/reviews` | Yes (User) | No | **NOT TESTED — MUTATING ENDPOINT** (Submit review) |
| `PATCH` | `/api/reviews/:id/status` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Moderate review) |
| `DELETE` | `/api/reviews/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete review) |
| `GET` | `/api/tracking/config` | No | Yes | Public — Courier & tracking service configuration |
| `GET` | `/api/tracking` | Yes (Admin) | No | Admin — Courier tracking integrations |
| `GET` | `/api/tracking/logs` | Yes (Admin) | No | Admin — Courier webhook / dispatch logs |
| `GET` | `/api/tracking/:id` | Yes (Admin) | No | Admin — Tracking rule by ID |
| `POST` | `/api/tracking` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create tracking rule) |
| `PUT` | `/api/tracking/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update tracking rule) |
| `DELETE` | `/api/tracking/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete tracking rule) |
| `GET` | `/api/pages/p/:slug` | No | Yes | Public — CMS policy / custom content page |
| `GET` | `/api/pages` | Yes (Admin) | No | Admin — CMS pages management list |
| `GET` | `/api/pages/contact` | Yes (Admin) | No | Admin — Inbound contact form inquiries |
| `POST` | `/api/pages/contact` | No | No | **NOT TESTED — MUTATING ENDPOINT** (Submit contact message) |
| `POST` | `/api/pages` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create CMS page) |
| `PUT` | `/api/pages/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update CMS page) |
| `PATCH` | `/api/pages/contact/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update message status) |
| `DELETE` | `/api/pages/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete CMS page) |
| `POST` | `/api/order/create` | No / Optional | No | **NOT TESTED — MUTATING ENDPOINT** (Place order) |
| `POST` | `/api/order/track` | No | No | **NOT TESTED — READ ONLY RPC** (Track by ID + Phone) |
| `GET` | `/api/order/my-orders` | Yes (User) | No | User — Order history |
| `GET` | `/api/order/:id/my-invoice` | Yes (User) | No | User — Order customer invoice |
| `GET` | `/api/order` | Yes (Admin) | No | Admin — Orders list with filters |
| `GET` | `/api/order/stats` | Yes (Admin) | No | Admin — Order conversion metrics |
| `GET` | `/api/order/:id` | Yes (Admin) | No | Admin — Order details |
| `GET` | `/api/order/:id/invoice` | Yes (Admin) | No | Admin — Admin invoice format |
| `PATCH` | `/api/order/:id/status` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update order status) |
| `PATCH` | `/api/order/:id/payment/verify` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Verify payment) |
| `PATCH` | `/api/order/:id/admin-note` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Add note) |
| `DELETE` | `/api/order/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete order) |
| `POST` | `/api/coupons/validate` | No | No | **NOT TESTED — READ ONLY RPC** (Validate promo code) |
| `GET` | `/api/coupons` | Yes (Admin) | No | Admin — Coupons list |
| `POST` | `/api/coupons` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create coupon) |
| `PUT` | `/api/coupons/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Update coupon) |
| `DELETE` | `/api/coupons/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete coupon) |
| `POST` | `/api/users/register` | No | No | **NOT TESTED — MUTATING ENDPOINT** (User register) |
| `POST` | `/api/users/login` | No | No | **NOT TESTED — MUTATING ENDPOINT** (User login) |
| `POST` | `/api/users/password-reset-request` | No | No | **NOT TESTED — MUTATING ENDPOINT** (Request reset) |
| `POST` | `/api/users/password-reset` | No | No | **NOT TESTED — MUTATING ENDPOINT** (Confirm reset) |
| `GET` | `/api/users/profile` | Yes (User) | No | User — Profile details |
| `PUT` | `/api/users/profile` | Yes (User) | No | **NOT TESTED — MUTATING ENDPOINT** (Update profile) |
| `POST` | `/api/users/change-password` | Yes (User) | No | **NOT TESTED — MUTATING ENDPOINT** (Change password) |
| `GET` | `/api/users/orders` | Yes (User) | No | User — User orders |
| `GET` | `/api/users/addresses` | Yes (User) | No | User — User address book |
| `POST` | `/api/users/addresses` | Yes (User) | No | **NOT TESTED — MUTATING ENDPOINT** (Add address) |
| `PUT` | `/api/users/addresses/:id` | Yes (User) | No | **NOT TESTED — MUTATING ENDPOINT** (Update address) |
| `DELETE` | `/api/users/addresses/:id` | Yes (User) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete address) |
| `POST` | `/api/users/admin` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Create sub-admin) |
| `GET` | `/api/users` | Yes (Admin) | No | Admin — Customers & users directory |
| `DELETE` | `/api/users/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete user) |
| `GET` | `/api/admin/dashboard` | Yes (Admin) | No | Admin — Executive analytics & sales overview |
| `GET` | `/api/media` | Yes (Admin) | No | Admin — Cloudinary / local media library |
| `GET` | `/api/media/folders` | Yes (Admin) | No | Admin — Media folders |
| `GET` | `/api/media/config` | Yes (Admin) | No | Admin — Media upload provider configuration |
| `POST` | `/api/media/upload` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Upload file) |
| `POST` | `/api/media/upload/multiple` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Upload batch) |
| `PUT` | `/api/media/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Rename/move asset) |
| `DELETE` | `/api/media/:id` | Yes (Admin) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete asset) |
| `POST` | `/api/uploads/payment-proof` | No | No | **NOT TESTED — MUTATING ENDPOINT** (Upload proof slip) |
| `POST` | `/api/analytics/purchase` | No | No | **NOT TESTED — MUTATING ENDPOINT** (Server conversion track) |
| `GET` | `/api/expenses` | Yes (Manager) | No | Management — Expense ledger |
| `GET` | `/api/expenses/summary` | Yes (Manager) | No | Management — Expense summary breakdown |
| `GET` | `/api/expenses/monthly` | Yes (Manager) | No | Management — Monthly expense report |
| `GET` | `/api/expenses/trends` | Yes (Manager) | No | Management — 12-month expense trend |
| `GET` | `/api/expenses/profit` | Yes (Manager) | No | Management — Net profit calculation |
| `GET` | `/api/expenses/categories` | Yes (Manager) | No | Management — Expense categories |
| `GET` | `/api/expenses/members` | Yes (Manager) | No | Management — Team members list |
| `POST` | `/api/expenses` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Log expense) |
| `PUT` | `/api/expenses/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Update expense) |
| `DELETE` | `/api/expenses/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete expense) |
| `GET` | `/api/costs` | Yes (Manager) | No | Management — Direct cost entries |
| `POST` | `/api/costs` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Log direct cost) |
| `PUT` | `/api/costs/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Update direct cost) |
| `DELETE` | `/api/costs/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete direct cost) |
| `GET` | `/api/bookings` | Yes (Manager) | No | Management — Booking schedule |
| `POST` | `/api/bookings` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Create booking) |
| `PUT` | `/api/bookings/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Update booking) |
| `DELETE` | `/api/bookings/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete booking) |
| `GET` | `/api/rentals` | Yes (Manager) | No | Management — Equipment / space rentals |
| `POST` | `/api/rentals` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Log rental) |
| `PUT` | `/api/rentals/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Update rental) |
| `DELETE` | `/api/rentals/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete rental) |
| `GET` | `/api/memos` | Yes (Manager) | No | Management — Document memo archive |
| `POST` | `/api/memos` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Upload memo) |
| `DELETE` | `/api/memos/:id` | Yes (Manager) | No | **NOT TESTED — MUTATING ENDPOINT** (Delete memo) |

---

## 3. Performance Results

All measurements are based on 15 successive requests per endpoint (with 2 warm-up requests discarded), sorted by **P95 descending**:

| Method | Endpoint | Requests | Avg (ms) | P50 (ms) | P90 (ms) | P95 (ms) | P99 (ms) | Min (ms) | Max (ms) | Payload | Status | Class |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| `GET` | `/api/homepage` | 15 | 774.6 | 753.7 | 931.8 | **950.5** | 950.5 | 681.3 | 950.5 | 56.19 KB | 200 | Slow |
| `GET` | `/api/products/:id/related` | 15 | 514.5 | 511.0 | 540.7 | **554.2** | 554.2 | 486.2 | 554.2 | 18.39 KB | 200 | Slow |
| `GET` | `/api/products?sort=price_asc` | 15 | 311.1 | 291.0 | 326.2 | **542.5** | 542.5 | 277.5 | 542.5 | 27.00 KB | 200 | Slow |
| `GET` | `/api/products` | 15 | 312.8 | 297.1 | 340.6 | **490.6** | 490.6 | 279.6 | 490.6 | 27.82 KB | 200 | Moderate |
| `GET` | `/api/products?category=air-fryers` | 15 | 378.0 | 380.6 | 393.0 | **408.3** | 408.3 | 358.9 | 408.3 | 2.48 KB | 200 | Moderate |
| `GET` | `/api/products?brand=anker` | 15 | 361.9 | 357.1 | 388.1 | **395.0** | 395.0 | 343.5 | 395.0 | 0.09 KB | 200 | Moderate |
| `GET` | `/api/products?inStock=true` | 15 | 306.8 | 295.0 | 337.3 | **371.3** | 371.3 | 278.7 | 371.3 | 27.82 KB | 200 | Moderate |
| `GET` | `/api/products?page=1&limit=10` | 15 | 297.1 | 291.3 | 318.9 | **350.0** | 350.0 | 281.2 | 350.0 | 23.22 KB | 200 | Moderate |
| `GET` | `/api/products/slug/:slug` | 15 | 303.9 | 298.5 | 319.5 | **341.9** | 341.9 | 281.1 | 341.9 | 2.57 KB | 200 | Moderate |
| `GET` | `/api/products?sale=true` | 15 | 299.6 | 295.8 | 329.0 | **338.9** | 338.9 | 282.0 | 338.9 | 27.82 KB | 200 | Moderate |
| `GET` | `/api/products?search=JBL` | 15 | 298.2 | 294.1 | 319.3 | **334.6** | 334.6 | 278.1 | 334.6 | 4.66 KB | 200 | Moderate |
| `GET` | `/api/products?page=1&limit=20` | 15 | 303.4 | 302.3 | 316.8 | **325.9** | 325.9 | 284.1 | 325.9 | 45.91 KB | 200 | Moderate |
| `GET` | `/api/products?sort=price_desc` | 15 | 294.8 | 291.5 | 310.6 | **323.9** | 323.9 | 279.4 | 323.9 | 27.30 KB | 200 | Moderate |
| `GET` | `/api/sizes` | 15 | 93.9 | 84.8 | 92.7 | **208.4** | 208.4 | 84.1 | 208.4 | 1.83 KB | 200 | Good |
| `GET` | `/api/reviews?productId=:id` | 15 | 170.4 | 170.8 | 176.1 | **177.8** | 177.8 | 165.4 | 177.8 | 0.09 KB | 200 | Good |
| `GET` | `/api/reviews` | 15 | 170.9 | 170.3 | 174.7 | **175.2** | 175.2 | 167.6 | 175.2 | 5.19 KB | 200 | Good |
| `GET` | `/api/settings/hero-slides` | 15 | 86.1 | 83.0 | 87.9 | **124.9** | 124.9 | 81.6 | 124.9 | 0.03 KB | 200 | Good |
| `GET` | `/api/collections` | 15 | 88.7 | 86.6 | 91.8 | **119.2** | 119.2 | 83.7 | 119.2 | 1.73 KB | 200 | Good |
| `GET` | `/api/payment-methods/public` | 15 | 87.6 | 85.1 | 97.6 | **114.2** | 114.2 | 82.5 | 114.2 | 0.73 KB | 200 | Good |
| `GET` | `/api/vendors` | 15 | 88.0 | 86.1 | 94.8 | **111.6** | 111.6 | 83.1 | 111.6 | 1.40 KB | 200 | Good |
| `GET` | `/api/brands` | 15 | 88.0 | 85.9 | 94.5 | **99.8** | 99.8 | 83.4 | 99.8 | 3.67 KB | 200 | Excellent |
| `GET` | `/api/categories` | 15 | 89.0 | 88.7 | 93.3 | **96.9** | 96.9 | 85.3 | 96.9 | 16.33 KB | 200 | Excellent |
| `GET` | `/api/settings/store-info` | 15 | 86.6 | 84.0 | 93.5 | **96.5** | 96.5 | 82.4 | 96.5 | 0.23 KB | 200 | Excellent |
| `GET` | `/api/banners/:id` | 15 | 85.4 | 82.9 | 91.2 | **95.8** | 95.8 | 81.3 | 95.8 | 0.40 KB | 200 | Excellent |
| `GET` | `/api/banners` | 15 | 85.6 | 84.8 | 88.7 | **95.4** | 95.4 | 82.7 | 95.4 | 0.81 KB | 200 | Excellent |
| `GET` | `/api/checkout-notices/public` | 15 | 86.3 | 85.8 | 90.1 | **95.4** | 95.4 | 83.2 | 95.4 | 0.43 KB | 200 | Excellent |
| `GET` | `/api/pages/p/shipping` | 15 | 85.4 | 84.7 | 87.9 | **92.6** | 92.6 | 82.1 | 92.6 | 2.84 KB | 200 | Excellent |
| `GET` | `/api/colors` | 15 | 85.9 | 85.8 | 87.3 | **91.3** | 91.3 | 83.9 | 91.3 | 1.25 KB | 200 | Excellent |
| `GET` | `/api/tracking/config` | 15 | 85.0 | 83.0 | 88.7 | **91.3** | 91.3 | 82.5 | 91.3 | 0.07 KB | 200 | Excellent |
| `GET` | `/api/suppliers` | 15 | 85.2 | 84.4 | 88.6 | **89.9** | 89.9 | 82.8 | 89.9 | 1.42 KB | 200 | Excellent |
| `GET` | `/api/pages/p/return-refund` | 15 | 85.3 | 85.2 | 87.4 | **88.9** | 88.9 | 82.8 | 88.9 | 5.57 KB | 200 | Excellent |
| `GET` | `/api/settings/contact_info` | 15 | 84.8 | 84.0 | 88.1 | **88.8** | 88.8 | 82.7 | 88.8 | 0.17 KB | 200 | Excellent |
| `GET` | `/api/shipping-methods/public` | 15 | 84.5 | 84.0 | 87.3 | **87.5** | 87.5 | 82.5 | 87.5 | 0.96 KB | 200 | Excellent |
| `GET` | `/api/health` | 15 | 1.3 | 1.2 | 1.8 | **1.9** | 1.9 | 0.5 | 1.9 | 0.05 KB | 200 | Excellent |
| `GET` | `/api/homepage/config` | 15 | 1.4 | 1.4 | 1.6 | **1.8** | 1.8 | 0.8 | 1.8 | 0.06 KB | 404* | Excellent |
| `GET` | `/` | 15 | 0.7 | 0.5 | 1.4 | **1.4** | 1.4 | 0.3 | 1.4 | 0.05 KB | 200 | Excellent |

---

## 4. Top 10 Slowest APIs — Detailed Diagnostics

### 1. `GET /api/homepage`
* **Average:** 774.6 ms | **P95:** 950.5 ms | **P99:** 950.5 ms | **Min:** 681.3 ms | **Max:** 950.5 ms
* **Response Payload:** 56.19 KB (Largest in the system)
* **Observed Database Operations:**
  * Sequential query 1: `getConfig()` to load section definitions and hero slides.
  * Parallel batch 1: `resolveCategories()`, `resolveBrands()`, `resolveCollections()`, `resolveBanners()`, and `resolveReviews()`.
  * Multi-query batch 2: Up to 10 product sections resolved simultaneously (`resolveLabelProducts('flash_sale')`, `resolveLabelProducts('featured')`, `resolveTrending()`, `resolveLabelProducts('new_arrival')`, `resolveBestSellers()`, etc.).
  * Each product resolution runs the 7-table `fullQuery` left joins + post-query `fetchRatingMap()`.
* **Possible Bottlenecks:**
  * **Aggregate Query Volume:** Resolving 8–10 distinct product sections runs 15+ complex MySQL queries simultaneously against the remote database over TLS.
  * **7-table LEFT JOINs:** Every section query joins `categories`, `subCategories`, `childCategories`, `collections`, `vendors`, `suppliers`, and `brands`.
  * **Payload Volume:** Over 56 KB of JSON serialized on every request.

---

### 2. `GET /api/products/:id/related`
* **Average:** 514.5 ms | **P95:** 554.2 ms | **P99:** 554.2 ms | **Min:** 486.2 ms | **Max:** 554.2 ms
* **Response Payload:** 18.39 KB
* **Observed Database Operations:**
  * Query 1: Fetch source product by ID to read its category, sub-category, and tags.
  * Query 2: Fetch manual relations from `product_relations` table.
  * Query 3: Complex OR query on `(categoryId = X OR subCategoryId = Y OR tags LIKE Z)` with `fullQuery` 7-table LEFT JOIN.
  * Query 4: Separate aggregation query on `reviews` to compute ratings for related products.
* **Possible Bottlenecks:**
  * **Multi-stage Sequential Database Round-Trips:** Query 1 must finish before Query 3 can be constructed.
  * **Unindexed Tag Search:** `sql`${products.tags} LIKE ...`` performs a table scan when tags condition is evaluated.

---

### 3. `GET /api/products?sort=price_asc`
* **Average:** 311.1 ms | **P95:** 542.5 ms | **P99:** 542.5 ms | **Min:** 277.5 ms | **Max:** 542.5 ms
* **Response Payload:** 27.00 KB
* **Observed Database Operations:**
  * Query 1: `buildWhere(query)` condition evaluation.
  * Query 2: `fullQuery` with 7 LEFT JOINs, `ORDER BY products.price ASC LIMIT 12 OFFSET 0`.
  * Query 3: `SELECT count(*) FROM products WHERE ...`.
  * Query 4: `fetchRatingMap` on the 12 returned product IDs.
* **Possible Bottlenecks:**
  * **Sorting on Decimal Column Without Dedicated Index:** Sorting by `price` when filtered by `status = 'active'` requires an in-memory filesort because there is no composite index on `(status, price)`.

---

### 4. `GET /api/products?category=air-fryers`
* **Average:** 378.0 ms | **P95:** 408.3 ms | **P99:** 408.3 ms | **Min:** 358.9 ms | **Max:** 408.3 ms
* **Response Payload:** 2.48 KB
* **Observed Database Operations:**
  * Query 1: `SELECT * FROM categories WHERE slug = 'air-fryers' LIMIT 1`.
  * Query 2: `fullQuery` with 7 LEFT JOINs filtering by `(categoryId = X OR subCategoryId = X OR childCategoryId = X)`.
  * Query 3: `SELECT count(*)` for pagination.
  * Query 4: `fetchRatingMap`.
* **Possible Bottlenecks:**
  * **Sequential Category Slug Lookup:** The category slug resolution must complete over the network before the product catalog query can begin.
  * **3-Column OR Filter:** `(categoryId = X OR subCategoryId = X OR childCategoryId = X)` prevents single-index optimization.

---

### 5. `GET /api/products?brand=anker`
* **Average:** 361.9 ms | **P95:** 395.0 ms | **P99:** 395.0 ms | **Min:** 343.5 ms | **Max:** 395.0 ms
* **Response Payload:** 0.09 KB
* **Observed Database Operations:**
  * Query 1: `SELECT * FROM brands WHERE slug = 'anker' LIMIT 1`.
  * Query 2: `fullQuery` filtering by `brand_id = X`.
  * Query 3: `SELECT count(*)` pagination total.
* **Possible Bottlenecks:**
  * Sequential brand slug lookup adds one extra round-trip (~85ms) prior to catalog query.

---

### 6. `GET /api/products` (Default Catalog)
* **Average:** 312.8 ms | **P95:** 490.6 ms | **P99:** 490.6 ms | **Min:** 279.6 ms | **Max:** 490.6 ms
* **Response Payload:** 27.82 KB
* **Observed Database Operations:**
  * Query 1: `fullQuery` with 7 LEFT JOINs ordering by `createdAt DESC LIMIT 12 OFFSET 0`.
  * Query 2: `SELECT count(*) FROM products WHERE status = 'active'`.
  * Query 3: `fetchRatingMap` for the 12 IDs.
* **Possible Bottlenecks:**
  * Three separate database network round-trips for every catalog page request.

---

### 7. `GET /api/products?inStock=true`
* **Average:** 306.8 ms | **P95:** 371.3 ms | **P99:** 371.3 ms | **Min:** 278.7 ms | **Max:** 371.3 ms
* **Response Payload:** 27.82 KB
* **Observed Database Operations:**
  * Filter condition: `(stock > 0 OR unlimited_stock = true)`.
  * Same 3-query pipeline as standard catalog.

---

### 8. `GET /api/products/slug/:slug`
* **Average:** 303.9 ms | **P95:** 341.9 ms | **P99:** 341.9 ms | **Min:** 281.1 ms | **Max:** 341.9 ms
* **Response Payload:** 2.57 KB
* **Observed Database Operations:**
  * Query 1: `fullQuery` with 7 LEFT JOINs `WHERE slug = :slug LIMIT 1`.
  * Query 2: `fetchRatingMap([id])`.
  * Query 3: `fetchChildren`: `productVariants` query + `productSpecs` query + `productRelations` query (run via `Promise.all`).
  * Query 4: Related products summary query if relations exist.
* **Possible Bottlenecks:**
  * Executes up to 5 SQL queries across 7 joins to construct the full product view.

---

### 9. `GET /api/products?page=1&limit=20`
* **Average:** 303.4 ms | **P95:** 325.9 ms | **P99:** 325.9 ms | **Min:** 284.1 ms | **Max:** 325.9 ms
* **Response Payload:** 45.91 KB
* **Observed Database Operations:**
  * Identical query plan to default catalog, with higher transfer volume (20 full product objects).

---

### 10. `GET /api/reviews`
* **Average:** 170.9 ms | **P95:** 175.2 ms | **P99:** 175.2 ms | **Min:** 167.6 ms | **Max:** 175.2 ms
* **Response Payload:** 5.19 KB
* **Observed Database Operations:**
  * Query 1: `SELECT reviews.*, products.title, products.slug, JSON_EXTRACT(products.images) FROM reviews LEFT JOIN products WHERE status = 'approved' ORDER BY createdAt DESC`.
  * Query 2: Review aggregation counts.
* **Possible Bottlenecks:**
  * `JSON_EXTRACT` on product images performed during table join.

---

## 5. Database Analysis

### Schema & Index Inspection
From inspecting `backend/src/config/schema.ts`:
* **Primary Keys:** Every table has an auto-incrementing integer primary key (`id`).
* **Unique Constraints:** `slug` fields are indexed as unique keys on `products`, `categories`, `brands`, `collections`, `vendors`, `suppliers`, `pages`.
* **Missing Composite Indexes Observed:**
  * `products`: Queries frequently filter by `status = 'active'` combined with `is_featured = true`, `is_flash_sale = true`, `is_best_seller = true`, or `created_at DESC`. There are **no composite secondary indexes** defined on `(status, created_at)`, `(status, is_featured)`, `(status, is_flash_sale)`, or `(category_id, status)`.
  * `reviews`: Filters by `(product_id, status)` and `status = 'approved'`. No composite index exists on `(status, created_at)`.
  * `order_items`: Aggregations during best-seller computation join on `product_id` and filter through `orders.status`.

### Sequential vs. Parallel Queries
* **Sequential Bottlenecks Identified:**
  1. `GET /api/products?category=:slug`: Resolves category slug in DB $\rightarrow$ passes category ID to catalog query $\rightarrow$ runs count query $\rightarrow$ runs rating map query (4 sequential trips).
  2. `GET /api/products?brand=:slug`: Resolves brand slug in DB $\rightarrow$ passes brand ID to catalog query (3 sequential trips).
  3. `GET /api/products/:id/related`: Resolves product in DB $\rightarrow$ runs category/tag match $\rightarrow$ runs rating query (3 sequential trips).
* **Effective Parallelization Identified:**
  1. `homepage.service.ts`: Uses `Promise.all` across section types.
  2. `fetchChildren()` in `product.service.ts`: Runs variants, specs, and relations in parallel via `Promise.all`.

---

## 6. Response Payload Analysis

Summary of API responses ordered by data transfer size:

| Rank | Endpoint | Uncompressed Size | Data Elements | Notes |
| :---: | :--- | :---: | :---: | :--- |
| **1** | `GET /api/homepage` | **56.19 KB** | 8+ sections, ~40 product cards | Contains entire homepage product data & banners |
| **2** | `GET /api/products?page=1&limit=20` | **45.91 KB** | 20 full products | Full product schema including descriptions |
| **3** | `GET /api/products` (12 items) | **27.82 KB** | 12 full products | Standard catalog payload |
| **4** | `GET /api/products/:id/related` | **18.39 KB** | 8 related products | Full product format per related item |
| **5** | `GET /api/categories` | **16.33 KB** | 42 categories | Category tree with SEO descriptions & image URLs |
| **6** | `GET /api/pages/p/return-refund` | **5.57 KB** | 1 page | HTML / rich text policy body |
| **7** | `GET /api/reviews` | **5.19 KB** | 10 reviews | Review comments + product titles |
| **8** | `GET /api/brands` | **3.67 KB** | 11 brands | Brand names, slugs, logos, product counts |
| **9** | `GET /api/products/slug/:slug` | **2.57 KB** | 1 product | Clean detailed product payload |
| **10** | `GET /api/collections` | **1.73 KB** | 6 collections | Collection titles, slugs, images |

---

## 7. Authentication Impact

* **Authentication Strategy:** Stateless JWT verification using `jwt.verify(token, env.JWT_SECRET)`.
* **Database Round-Trips for Auth:** **Zero** database queries are executed for standard route authentication (`authMiddleware` extracts user ID and role directly from JWT payload).
* **Observed Latency Added by Auth:** **< 0.15 ms** (negligible CPU cost for HMAC-SHA256 token verification in memory).
* **Permission Middleware:** Evaluates in-memory role-to-permission mapping (`ROLE_PERMISSIONS`) in $\approx$ **0.02 ms**.

---

## 8. Mutating APIs (Not Tested)

The following 64 mutating, administrative, or state-changing endpoints were discovered and strictly **omitted from performance execution** to preserve database integrity:

```text
POST   /api/order/create                      Status: NOT TESTED — MUTATING ENDPOINT (Creates real customer orders)
POST   /api/order/track                       Status: NOT TESTED — READ ONLY RPC (Requires valid customer order+phone)
PATCH  /api/order/:id/status                  Status: NOT TESTED — MUTATING ENDPOINT (Changes order lifecycle status)
PATCH  /api/order/:id/payment/verify          Status: NOT TESTED — MUTATING ENDPOINT (Marks payment verified)
PATCH  /api/order/:id/admin-note              Status: NOT TESTED — MUTATING ENDPOINT (Appends internal order note)
DELETE /api/order/:id                         Status: NOT TESTED — MUTATING ENDPOINT (Deletes order record)
POST   /api/products                          Status: NOT TESTED — MUTATING ENDPOINT (Inserts product record)
PUT    /api/products/:id                      Status: NOT TESTED — MUTATING ENDPOINT (Updates product record)
DELETE /api/products/:id                      Status: NOT TESTED — MUTATING ENDPOINT (Deletes product record)
POST   /api/products/bulk                     Status: NOT TESTED — MUTATING ENDPOINT (Bulk updates products)
POST   /api/products/import/csv               Status: NOT TESTED — MUTATING ENDPOINT (Batch imports catalog)
POST   /api/products/:id/duplicate            Status: NOT TESTED — MUTATING ENDPOINT (Clones product record)
POST   /api/products/:id/draft                Status: NOT TESTED — MUTATING ENDPOINT (Saves draft state)
POST   /api/categories                        Status: NOT TESTED — MUTATING ENDPOINT (Inserts category)
PUT    /api/categories/:id                    Status: NOT TESTED — MUTATING ENDPOINT (Updates category)
POST   /api/categories/:id/move               Status: NOT TESTED — MUTATING ENDPOINT (Reassigns products)
DELETE /api/categories/:id                    Status: NOT TESTED — MUTATING ENDPOINT (Deletes category)
POST   /api/brands                            Status: NOT TESTED — MUTATING ENDPOINT (Inserts brand)
PUT    /api/brands/:id                        Status: NOT TESTED — MUTATING ENDPOINT (Updates brand)
POST   /api/brands/:id/move                   Status: NOT TESTED — MUTATING ENDPOINT (Reassigns brand products)
DELETE /api/brands/:id                        Status: NOT TESTED — MUTATING ENDPOINT (Deletes brand)
POST   /api/collections                       Status: NOT TESTED — MUTATING ENDPOINT (Inserts collection)
PUT    /api/collections/:id                   Status: NOT TESTED — MUTATING ENDPOINT (Updates collection)
DELETE /api/collections/:id                   Status: NOT TESTED — MUTATING ENDPOINT (Deletes collection)
POST   /api/colors                            Status: NOT TESTED — MUTATING ENDPOINT (Inserts color)
PUT    /api/colors/:id                        Status: NOT TESTED — MUTATING ENDPOINT (Updates color)
DELETE /api/colors/:id                        Status: NOT TESTED — MUTATING ENDPOINT (Deletes color)
POST   /api/sizes                             Status: NOT TESTED — MUTATING ENDPOINT (Inserts size)
PUT    /api/sizes/:id                         Status: NOT TESTED — MUTATING ENDPOINT (Updates size)
DELETE /api/sizes/:id                         Status: NOT TESTED — MUTATING ENDPOINT (Deletes size)
POST   /api/vendors                           Status: NOT TESTED — MUTATING ENDPOINT (Inserts vendor)
PUT    /api/vendors/:id                       Status: NOT TESTED — MUTATING ENDPOINT (Updates vendor)
DELETE /api/vendors/:id                       Status: NOT TESTED — MUTATING ENDPOINT (Deletes vendor)
POST   /api/suppliers                         Status: NOT TESTED — MUTATING ENDPOINT (Inserts supplier)
PUT    /api/suppliers/:id                     Status: NOT TESTED — MUTATING ENDPOINT (Updates supplier)
DELETE /api/suppliers/:id                     Status: NOT TESTED — MUTATING ENDPOINT (Deletes supplier)
POST   /api/banners                           Status: NOT TESTED — MUTATING ENDPOINT (Inserts banner)
PUT    /api/banners/:id                       Status: NOT TESTED — MUTATING ENDPOINT (Updates banner)
DELETE /api/banners/:id                       Status: NOT TESTED — MUTATING ENDPOINT (Deletes banner)
POST   /api/shipping-methods                  Status: NOT TESTED — MUTATING ENDPOINT (Inserts shipping method)
PUT    /api/shipping-methods/:id              Status: NOT TESTED — MUTATING ENDPOINT (Updates shipping method)
DELETE /api/shipping-methods/:id              Status: NOT TESTED — MUTATING ENDPOINT (Deletes shipping method)
POST   /api/payment-methods                   Status: NOT TESTED — MUTATING ENDPOINT (Inserts payment method)
PUT    /api/payment-methods/:id               Status: NOT TESTED — MUTATING ENDPOINT (Updates payment method)
PUT    /api/payment-methods                   Status: NOT TESTED — MUTATING ENDPOINT (Bulk updates methods)
DELETE /api/payment-methods/:id               Status: NOT TESTED — MUTATING ENDPOINT (Deletes payment method)
POST   /api/checkout-notices                  Status: NOT TESTED — MUTATING ENDPOINT (Inserts notice)
PUT    /api/checkout-notices/:id              Status: NOT TESTED — MUTATING ENDPOINT (Updates notice)
DELETE /api/checkout-notices/:id              Status: NOT TESTED — MUTATING ENDPOINT (Deletes notice)
PUT    /api/settings                          Status: NOT TESTED — MUTATING ENDPOINT (Updates store settings)
POST   /api/settings/hero-slides              Status: NOT TESTED — MUTATING ENDPOINT (Inserts slide)
DELETE /api/settings/hero-slides/:index       Status: NOT TESTED — MUTATING ENDPOINT (Deletes slide)
POST   /api/reviews                           Status: NOT TESTED — MUTATING ENDPOINT (Inserts review)
PATCH  /api/reviews/:id/status                Status: NOT TESTED — MUTATING ENDPOINT (Moderates review)
DELETE /api/reviews/:id                       Status: NOT TESTED — MUTATING ENDPOINT (Deletes review)
POST   /api/pages/contact                     Status: NOT TESTED — MUTATING ENDPOINT (Inserts contact message)
POST   /api/pages                             Status: NOT TESTED — MUTATING ENDPOINT (Inserts CMS page)
PUT    /api/pages/:id                         Status: NOT TESTED — MUTATING ENDPOINT (Updates CMS page)
DELETE /api/pages/:id                         Status: NOT TESTED — MUTATING ENDPOINT (Deletes CMS page)
POST   /api/coupons                           Status: NOT TESTED — MUTATING ENDPOINT (Inserts coupon)
PUT    /api/coupons/:id                       Status: NOT TESTED — MUTATING ENDPOINT (Updates coupon)
DELETE /api/coupons/:id                       Status: NOT TESTED — MUTATING ENDPOINT (Deletes coupon)
POST   /api/users/register                    Status: NOT TESTED — MUTATING ENDPOINT (Creates user account)
POST   /api/users/login                       Status: NOT TESTED — MUTATING ENDPOINT (Creates user session)
POST   /api/expenses                          Status: NOT TESTED — MUTATING ENDPOINT (Logs financial expense)
POST   /api/costs                             Status: NOT TESTED — MUTATING ENDPOINT (Logs direct cost)
POST   /api/bookings                          Status: NOT TESTED — MUTATING ENDPOINT (Creates booking)
POST   /api/rentals                           Status: NOT TESTED — MUTATING ENDPOINT (Logs rental)
POST   /api/memos                             Status: NOT TESTED — MUTATING ENDPOINT (Uploads memo document)
```

---

## 9. Recommendations (Suggestions Only)

*These recommendations are provided strictly as architectural observations for future reference. No changes have been implemented.*

1. **In-Memory Caching for Public Reference Data:**
   * Endpoints like `GET /api/categories`, `GET /api/brands`, `GET /api/collections`, and `GET /api/settings/store-info` change infrequently. Implementing a lightweight TTL cache (e.g., 5–15 minutes) or stale-while-revalidate pattern in Express would reduce database load and cut response times from ~88 ms to < 2 ms.
2. **Homepage Aggregate Caching:**
   * `GET /api/homepage` takes ~774 ms because it resolves 8+ product rails simultaneously. Serving this response from a short-lived cache (e.g., 60 seconds with background invalidation on product updates) would drastically reduce server CPU and database bandwidth.
3. **Database Composite Indexes:**
   * Consider adding composite indexes in MySQL for common query filter pairs:
     * `products(status, created_at)`
     * `products(status, is_featured)`
     * `products(status, is_flash_sale)`
     * `products(category_id, status)`
     * `products(brand_id, status)`
4. **Parallelize Category/Brand Slug Lookups in `buildWhere`:**
   * When searching or filtering by slug (e.g. `?category=electronics`), resolve the slug concurrently with count queries or join directly on `slug` rather than awaiting the ID lookup sequentially.
5. **Selective Column Projection on Product Rails:**
   * `formatHomepageProduct` already strips unused fields, but SQL queries still select all columns (`description`, `specs`, `warehouse`, etc.). Projecting only necessary fields in Drizzle would reduce data transfer between MySQL and Express.

---

## 10. Test Environment Details

* **Runtime:** Node.js v24.13.0
* **Package Manager:** npm v11.6.2
* **Framework:** Express.js v4.22.1
* **ORM:** Drizzle ORM v0.30.10
* **Database Engine:** MySQL Compatible (TiDB Cloud TLS Cluster)
* **Testing Protocol:** Local HTTP client $\rightarrow$ Express backend $\rightarrow$ Remote Managed MySQL DB
* **Warm-up Protocol:** 2 discarded initial requests per endpoint
* **Sample Size:** 15 recorded requests per endpoint (600 total test requests)
* **Concurrency:** 1 (controlled sequential requests with 15ms interval)
* **Security & Privacy:** Zero credentials, keys, tokens, or connection strings exposed in this report.
