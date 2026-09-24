# React to Laravel Architecture & Mapping Guide

This document maps every React frontend component, page, layout, store hook, and Node.js backend route directly to its corresponding Laravel Blade, Controller, Service, and Eloquent representation.

---

## 1. Storefront & Public User Pages

| React Route | React Component | Laravel Blade View | Laravel Controller | Shared Service |
| :--- | :--- | :--- | :--- | :--- |
| `/` | `HomePage.tsx` + `HomepageSections.tsx` | `resources/views/shop/home.blade.php` | `HomeController@index` | `HomepageService`, `ProductService`, `CategoryService` |
| `/shop` | `ShopPage.tsx` | `resources/views/shop/index.blade.php` | `ShopController@index` | `ProductService`, `CategoryService`, `BrandService` |
| `/products/:slug` | `ProductDetailsPage.tsx` | `resources/views/shop/products/show.blade.php` | `ProductWebController@show` | `ProductService`, `ReviewService` |
| `/cart` | `CartPage.tsx` | `resources/views/shop/cart.blade.php` | `CartController@index` | Session / Cart helper |
| `/checkout` | `CheckoutPage.tsx` | `resources/views/shop/checkout.blade.php` | `CheckoutController@index`, `process` | `OrderService`, `ShippingService`, `PaymentMethodService`, `CouponService` |
| `/track` | `OrderTrackingPage.tsx` | `resources/views/shop/track.blade.php` | `OrderTrackingController@index` | `OrderService` |
| `/order/success` | `OrderSuccessPage.tsx` | `resources/views/shop/order-success.blade.php` | `CheckoutController@success` | `OrderService` |
| `/about` | `AboutPage.tsx` | `resources/views/pages/about.blade.php` | `PageWebController@about` | `StoreInfoService` |
| `/faq` | `FaqPage.tsx` | `resources/views/pages/faq.blade.php` | `PageWebController@faq` | Static / `SettingsService` |
| `/contact` | `ContactPage.tsx` | `resources/views/pages/contact.blade.php` | `PageWebController@contact`, `submitContact` | `ContactMessageService` |
| `/refund-policy` | `PolicyPage.tsx` (`return-refund`) | `resources/views/pages/policy.blade.php` | `PageWebController@show` | `PolicyPageService` |
| `/shipping-policy`| `PolicyPage.tsx` (`shipping`) | `resources/views/pages/policy.blade.php` | `PageWebController@show` | `PolicyPageService` |
| `/privacy-policy` | `PrivacyPolicyPage.tsx` | `resources/views/pages/policy.blade.php` | `PageWebController@show` | `PolicyPageService` |
| `/terms-and-conditions` | `TermsAndConditionsPage.tsx`| `resources/views/pages/policy.blade.php` | `PageWebController@show` | `PolicyPageService` |
| `/cookie-policy` | `CookiePolicyPage.tsx` | `resources/views/pages/policy.blade.php` | `PageWebController@show` | `PolicyPageService` |

---

## 2. Customer Authentication & User Dashboard

| React Route | React Component | Laravel Blade View | Laravel Controller | Service / Auth |
| :--- | :--- | :--- | :--- | :--- |
| `/auth/login` | `LoginPage.tsx` | `resources/views/auth/login.blade.php` | `AuthWebController@showLogin`, `login` | Laravel `web` guard / `AuthService` |
| `/auth/register`| `RegisterPage.tsx` | `resources/views/auth/register.blade.php` | `AuthWebController@showRegister`, `register` | `AuthService` |
| `/auth/forgot-password`| `ForgotPasswordPage.tsx`| `resources/views/auth/forgot-password.blade.php`| `AuthWebController@forgotPassword` | `AuthService` |
| `/dashboard/overview` | `DashboardOverviewPage.tsx` | `resources/views/dashboard/overview.blade.php` | `UserDashboardController@overview` | `OrderService`, `UserService` |
| `/dashboard/orders` | `DashboardOrdersPage.tsx` | `resources/views/dashboard/orders.blade.php` | `UserDashboardController@orders` | `OrderService` |
| `/dashboard/profile` | `DashboardProfilePage.tsx` | `resources/views/dashboard/profile.blade.php` | `UserDashboardController@profile` | `UserService` |
| `/dashboard/addresses` | `DashboardAddressesPage.tsx` | `resources/views/dashboard/addresses.blade.php` | `UserDashboardController@addresses` | `UserAddressService` |
| `/dashboard/security` | `DashboardSecurityPage.tsx` | `resources/views/dashboard/security.blade.php` | `UserDashboardController@security` | `UserService` |

---

## 3. Storefront UI Layout & Common Components

| React Component | File Path | Laravel Blade Equivalent | Tech & Notes |
| :--- | :--- | :--- | :--- |
| `MainLayout` | `components/layout/MainLayout.tsx` | `resources/views/layouts/app.blade.php` | Base HTML5 shell with header, footer, toast, pixel, cart drawer |
| `SiteNavbar` | `components/layout/SiteNavbar.tsx` | `resources/views/layouts/partials/navbar.blade.php` | Sticky glass navbar, search bar, cart pill, user dropdown, category mega-menu |
| `SiteFooter` | `components/layout/SiteFooter.tsx` | `resources/views/layouts/partials/footer.blade.php` | 5-column footer with contact details, shop links, social links, payment icons |
| `ProductCard` | `components/common/ProductCard.tsx` | `resources/views/components/product-card.blade.php` | GPU-accelerated hover shadow, badge priorities, color swatches, quick view, add to cart |
| `CartDrawer` | `components/common/CartDrawer.tsx` | `resources/views/components/cart-drawer.blade.php` | Alpine.js side-slide drawer with reactive subtotal & quantities |
| `QuickViewModal` | `components/common/QuickViewModal.tsx` | `resources/views/components/quick-view-modal.blade.php` | Alpine.js modal with image gallery, variant selectors, add to cart |
| `SearchBar` | `components/common/SearchBar.tsx` | `resources/views/components/search-bar.blade.php` | Real-time search with debounced dropdown preview |
| `MobileBottomNav` | `components/common/MobileBottomNav.tsx` | `resources/views/layouts/partials/mobile-nav.blade.php` | Fixed bottom mobile tab bar (Home, Shop, Cart, Account) |
| `ToastProvider` | `components/common/ToastProvider.tsx` | `resources/views/components/toast.blade.php` | Alpine.js toast notifications |

---

## 4. Homepage Sections

| React Section Component | React Source File | Laravel Blade Partial | Backend Service Call |
| :--- | :--- | :--- | :--- |
| `HeroCarousel` | `features/homepage/HeroCarousel.tsx` | `resources/views/shop/partials/hero-carousel.blade.php` | `HomepageService::getHeroSlides()` |
| `TrustStrip` | `features/homepage/TrustStrip.tsx` | `resources/views/shop/partials/trust-strip.blade.php` | `HomepageService::getTrustStrip()` |
| `CategoryGrid` | `features/homepage/CategoryGrid.tsx` | `resources/views/shop/partials/category-grid.blade.php` | `CategoryService::getFeatured()` |
| `ProductCarousel` | `features/homepage/ProductCarousel.tsx` | `resources/views/shop/partials/product-rail.blade.php` | `ProductService::getByLabel($label)` |
| `FlashDeals` | `features/homepage/FlashDeals.tsx` | `resources/views/shop/partials/flash-deals.blade.php` | `ProductService::getFlashDeals()` |
| `PromoBanner` | `features/homepage/PromoBanner.tsx` | `resources/views/shop/partials/promo-banner.blade.php` | `BannerService::getByPlacement()` |
| `BrandRow` | `features/homepage/BrandRow.tsx` | `resources/views/shop/partials/brand-row.blade.php` | `BrandService::getAll()` |
| `CollectionTiles` | `features/homepage/CollectionTiles.tsx` | `resources/views/shop/partials/collection-tiles.blade.php` | `CollectionService::getAll()` |
| `ReviewsSection` | `features/homepage/ReviewsSection.tsx` | `resources/views/shop/partials/reviews-section.blade.php` | `ReviewService::getFeatured()` |
| `WhyChooseUs` | `features/homepage/WhyChooseUs.tsx` | `resources/views/shop/partials/why-choose-us.blade.php` | `HomepageService::getWhyChooseUs()` |
| `NewsletterBlock` | `features/homepage/NewsletterBlock.tsx` | `resources/views/shop/partials/newsletter.blade.php` | `HomepageService::subscribeNewsletter()` |

---

## 5. Admin Panel Layout & Sidebar

| React Admin Component | React Source File | Laravel Admin Blade Component | Behavior / Notes |
| :--- | :--- | :--- | :--- |
| `AdminLayout` | `components/layout/AdminLayout.tsx` | `resources/views/layouts/admin.blade.php` | Full admin viewport container with responsive sidebar and topbar |
| `Sidebar` | `components/admin/Sidebar.tsx` + `adminNav.ts` | `resources/views/layouts/partials/admin-sidebar.blade.php` | Exact 10 sections, collapsible drawer, persistent state in `localStorage`, RBAC permission checks |
| `AdminTopbar` | `components/admin/AdminTopbar.tsx` | `resources/views/layouts/partials/admin-topbar.blade.php` | Sidebar toggle, breadcrumb, ⌘K search trigger, live notifications, admin profile menu |
| `CommandPalette` | `components/admin/CommandPalette.tsx` | `resources/views/layouts/partials/admin-command-palette.blade.php` | Alpine.js ⌘K search modal jumping to any admin section or action |

---

## 6. Admin Feature Modules

| React Admin Page | React Source File | Laravel Blade View | Laravel Admin Controller | Laravel Service |
| :--- | :--- | :--- | :--- | :--- |
| **Dashboard** | `AdminDashboardPage.tsx` | `resources/views/admin/dashboard/index.blade.php` | `AdminDashboardController@index` | `DashboardService` |
| **Products List** | `AdminProductListPage.tsx` | `resources/views/admin/products/index.blade.php` | `AdminProductWebController@index` | `ProductService` |
| **Product Create** | `AdminProductCreatePage.tsx` | `resources/views/admin/products/create.blade.php` | `AdminProductWebController@create`, `store` | `ProductService` |
| **Product Edit** | `AdminProductEditPage.tsx` | `resources/views/admin/products/edit.blade.php` | `AdminProductWebController@edit`, `update` | `ProductService` |
| **Product View** | `AdminProductViewPage.tsx` | `resources/views/admin/products/show.blade.php` | `AdminProductWebController@show` | `ProductService` |
| **Orders** | `AdminOrdersPage.tsx` | `resources/views/admin/orders/index.blade.php` | `AdminOrderWebController@index`, `show` | `OrderService` |
| **Order Invoice** | `AdminOrderInvoicePage.tsx` | `resources/views/admin/orders/invoice.blade.php` | `AdminOrderWebController@invoice` | `OrderService` |
| **Categories** | `AdminCategoriesPage.tsx` | `resources/views/admin/categories/index.blade.php` | `AdminCategoryWebController@index`, `store`, etc. | `CategoryService` |
| **Brands** | `AdminBrandsPage.tsx` | `resources/views/admin/brands/index.blade.php` | `AdminBrandWebController@index`, `store`, etc. | `BrandService` |
| **Collections** | `AdminCollectionsPage.tsx` | `resources/views/admin/collections/index.blade.php` | `AdminCollectionWebController@index`, `store`, etc. | `CollectionService` |
| **Colors** | `AdminColorsPage.tsx` | `resources/views/admin/colors/index.blade.php` | `AdminCatalogWebController@colors` | `CatalogService` |
| **Sizes** | `AdminSizesPage.tsx` | `resources/views/admin/sizes/index.blade.php` | `AdminCatalogWebController@sizes` | `CatalogService` |
| **Vendors** | `AdminVendorsPage.tsx` | `resources/views/admin/vendors/index.blade.php` | `AdminCatalogWebController@vendors` | `CatalogService` |
| **Suppliers** | `AdminSuppliersPage.tsx` | `resources/views/admin/suppliers/index.blade.php` | `AdminCatalogWebController@suppliers` | `CatalogService` |
| **Shipping** | `AdminShippingPage.tsx` | `resources/views/admin/shipping/index.blade.php` | `AdminSettingWebController@shipping` | `ShippingService` |
| **Payment Methods**| `AdminPaymentMethodsPage.tsx` | `resources/views/admin/payments/index.blade.php` | `AdminSettingWebController@paymentMethods` | `PaymentMethodService` |
| **Checkout Notices**| `AdminCheckoutNoticesPage.tsx`| `resources/views/admin/checkout-notices/index.blade.php` | `AdminSettingWebController@checkoutNotices`| `CheckoutNoticeService` |
| **Coupons** | `AdminCouponsPage.tsx` | `resources/views/admin/coupons/index.blade.php` | `AdminCouponWebController@index`, `store`, etc. | `CouponService` |
| **Marketing** | `AdminMarketingPage.tsx` | `resources/views/admin/marketing/index.blade.php` | `AdminMarketingWebController@index`, `store`, etc. | `MarketingService` |
| **Expenses** | `AdminExpensesPage.tsx` | `resources/views/admin/expenses/index.blade.php` | `AdminExpenseWebController@index`, `store`, etc. | `ExpenseService` |
| **Expense Categories**| `AdminExpenseCategoriesPage.tsx`| `resources/views/admin/expenses/categories.blade.php` | `AdminExpenseWebController@categories` | `ExpenseService` |
| **Expense Reports**| `AdminExpenseReportsPage.tsx`| `resources/views/admin/expenses/reports.blade.php` | `AdminExpenseWebController@reports` | `ExpenseService` |
| **Customers** | `AdminCustomersPage.tsx` | `resources/views/admin/customers/index.blade.php` | `AdminCustomerWebController@index`, `toggle` | `UserService` |
| **Homepage Builder**| `AdminHomepagePage.tsx` | `resources/views/admin/homepage/index.blade.php` | `AdminHomepageWebController@index`, `update` | `HomepageService` |
| **Policies** | `AdminPoliciesPage.tsx` | `resources/views/admin/policies/index.blade.php` | `AdminPolicyWebController@index`, `update` | `PolicyPageService` |
| **Media Library** | `AdminMediaPage.tsx` | `resources/views/admin/media/index.blade.php` | `AdminSettingWebController@media`, `upload` | `MediaService` |
| **Banners** | `AdminBannersPage.tsx` | `resources/views/admin/banners/index.blade.php` | `AdminSettingWebController@banners` | `BannerService` |
| **Analytics** | `AdminAnalyticsPage.tsx` | `resources/views/admin/analytics/index.blade.php` | `AdminAnalyticsWebController@index` | `AnalyticsService` |
| **Team Members** | `AdminMembersPage.tsx` | `resources/views/admin/members/index.blade.php` | `AdminMemberWebController@index`, `store`, etc. | `RbacService`, `MemberService` |
| **Backup & Restore**| `AdminBackupPage.tsx` | `resources/views/admin/backup/index.blade.php` | `AdminSettingWebController@backup`, `create` | `BackupService` |
| **Inventory** | `AdminInventoryPage.tsx` | `resources/views/admin/inventory/index.blade.php` | `AdminInventoryWebController@index`, `update` | `ProductService` |
| **Settings** | `AdminSettingsPage.tsx` | `resources/views/admin/settings/index.blade.php` | `AdminSettingWebController@settings`, `update` | `SettingsService` |

---

## 7. Shared Service Layer Architecture

```
                                    DATABASE (SQLite)
                                           ▲
                                           │
                                    ELOQUENT MODELS
                                           ▲
                                           │
                            SHARED APPLICATION SERVICES
         (ProductService, OrderService, CategoryService, HomepageService, RbacService...)
                                           ▲
                     ┌─────────────────────┼─────────────────────┐
                     │                     │                     │
              REST API LAYER       PUBLIC BLADE LAYER    ADMIN BLADE LAYER
           (routes/api.php)       (routes/web.php)      (routes/web.php: /admin)
                     │                     │                     │
                     ▼                     ▼                     ▼
             Existing React SPA      Storefront Blade UI   Admin Panel Blade UI
```

Every database mutation and query is executed through the service layer. For example:
- Creating a product: `ProductService::create($data)` is called by `ProductController::create` (REST API) and `AdminProductWebController::store` (Blade Admin).
- Placing an order: `OrderService::createOrder($data)` is called by `OrderController::createOrder` (REST API) and `CheckoutController::process` (Blade Storefront).
- Applying discounts: `CouponService::validate($code, $subtotal)` is used by both the React API consumer and the Blade Checkout page.
