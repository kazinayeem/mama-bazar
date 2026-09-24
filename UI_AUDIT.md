# Mama Bazar — UI Audit (React = Visual Source of Truth)

> **Status:** AUDIT COMPLETE — no UI implementation in this step.  
> **Date:** 2026-09-24  
> **Objective:** Reimplement the existing React application in Laravel Blade so User UI and Admin UI are as close to 100% identical as technically possible.  
> **Rule:** Do not redesign. Do not approximate. Clone.

| Layer | Path | Role |
|---|---|---|
| React SPA (UI source of truth) | `/frontend` | Exact visual + interaction reference |
| Node API (contract source of truth) | `/backend` | REST routes, auth, business rules |
| Laravel target | `/backend-laravel` | Blade + Tailwind + Alpine + SQLite + local storage + API mirror |

Related docs (do not treat as replacements for this file):
- `AUDIT_REPORT.md` — earlier migration overview
- `REACT_TO_LARAVEL_MAP.md` — route/component mapping notes
- Previous backend-heavy content from this file is folded into **FUNCTIONALITY** below

---

## 0. Verdict (read first)

### What React actually is
- Vite + React 19 + TypeScript SPA
- Tailwind CSS 3.4 + `tailwindcss-animate` + shadcn/Radix UI primitives
- Lucide icons, Framer Motion, Swiper, Recharts, TipTap, Sonner toasts
- Redux Toolkit + RTK Query
- Self-hosted Inter + Noto Sans Bengali (`.woff2` in `frontend/public/fonts/`)
- Brand green `#176B3A` + brand orange `#F47B20` on canvas `#F8FAF8`
- Light mode only (`dark` class forcibly removed at boot)

### What Laravel currently is (honest gap)
Laravel already has a **partial** Blade storefront + admin and a near-complete API mirror. It is **not** a 100% UI clone yet.

| Area | React | Laravel Blade today | Parity |
|---|---|---|---|
| Brand color tokens | Exact palette in `tailwind.config.js` | Matching tokens in `resources/css/app.css` | Tokens OK |
| Fonts | Self-hosted `/fonts/*.woff2` | Google Fonts CDN in admin layout | **Mismatch** |
| Admin sidebar menus | Full 10 sections / ~30 items (`adminNav.ts`) | Truncated (~12 items) | **Major gap** |
| Admin pages | 30+ pages | ~12 modules | **Major gap** |
| User dashboard | 5 routes under `/dashboard/*` | Missing | **Gap** |
| Auth forgot-password | `/auth/forgot-password` | Missing | **Gap** |
| Homepage sections | Config-driven 15 section types | Partial hardcoded-ish Blade home | **Incomplete** |
| Product card hover | `::after` opacity shadow (GPU) | Direct `box-shadow` transition | **Mismatch** |
| Navbar glass | Exact CSS (blur 16px, saturate 1.6) | Approximate / different values | **Mismatch** |
| Icons | Lucide React | Inline Heroicon-like SVG paths | **Shape mismatch risk** |
| API | Node Express | Laravel API largely ported | Strong |
| Cloudinary | Used in React/Node | Removed → local storage (required) | Intentional change |

**Implementation must treat React as the blueprint and close every gap above — not declare “looks similar” done.**

---

# USER UI

## 1. Entry, stack, global shell

### Entry
| File | Role |
|---|---|
| `frontend/index.html` | `#root`, preloads Inter 400, Cloudinary preconnect, GA |
| `frontend/src/main.tsx` | Redux, Helmet, BrowserRouter, Sonner, PWA SW (prod), remove `dark` |
| `frontend/src/App.tsx` | All routes (auth / MainLayout / admin) |
| `frontend/src/index.css` | `@font-face`, design tokens, navbar glass, product-card hover, TipTap, skeletons |
| `frontend/tailwind.config.js` | Brand palette, fonts, shadows, radii, animations |

### MainLayout (`components/layout/MainLayout.tsx`)
```
flex min-h-screen flex-col bg-background font-body text-foreground
├── skip-link → #main-content
├── SiteNavbar
├── <main id="main-content"> + PageTransition (Framer) + <Outlet />
├── SiteFooter
├── CartDrawer
├── MobileBottomNav
├── WhatsAppButton
└── PixelTracker
```

### AuthLayout (`components/layout/AuthLayout.tsx`)
Dedicated auth shell for `/auth/login`, `/auth/register`, `/auth/forgot-password` (not MainLayout).

### UserDashboardLayout (`components/layout/UserDashboardLayout.tsx`)
Nested under MainLayout for `/dashboard/*`. Profile hero + 240px sticky side nav + mobile bottom tab strip.

---

## 2. Every public / user route

| Route | Page component | Layout | Notes |
|---|---|---|---|
| `/` | `HomePage.tsx` | MainLayout | Homepage config + QuickViewModal |
| `/shop` | `ShopPage.tsx` | MainLayout | Filters, sort, pagination PAGE_SIZE=12 |
| `/products/:slug` | `ProductDetailsPage.tsx` | MainLayout | Gallery, variants, ATC, related |
| `/cart` | `CartPage.tsx` | MainLayout | Full-page cart (also CartDrawer global) |
| `/checkout` | `CheckoutPage.tsx` | MainLayout | BD geo cascade, shipping, payment, coupon |
| `/track` | `OrderTrackingPage.tsx` | MainLayout | Order ID or phone |
| `/order/success` | `OrderSuccessPage.tsx` | MainLayout | Confirmation |
| `/about` | `AboutPage.tsx` | MainLayout | Includes team |
| `/faq` | `FaqPage.tsx` | MainLayout | Accordion |
| `/contact` | `ContactPage.tsx` | MainLayout | Form + info |
| `/refund-policy` | `PolicyPage` slug=`return-refund` | MainLayout | |
| `/return-refund-policy` | same | MainLayout | Alias |
| `/shipping-policy` | `PolicyPage` slug=`shipping` | MainLayout | |
| `/privacy-policy` | `PrivacyPolicyPage.tsx` | MainLayout | Dedicated |
| `/terms-and-conditions` | `TermsAndConditionsPage.tsx` | MainLayout | Dedicated |
| `/cookie-policy` | `CookiePolicyPage.tsx` | MainLayout | Dedicated |
| `/payment-policy` | `PolicyPage` slug=`payment` | MainLayout | |
| `/cancellation-policy` | `PolicyPage` slug=`cancellation` | MainLayout | |
| `/warranty-policy` | `PolicyPage` slug=`warranty` | MainLayout | |
| `/auth/login` | `LoginPage.tsx` | AuthLayout | Phone-centric login |
| `/auth/register` | `RegisterPage.tsx` | AuthLayout | |
| `/auth/forgot-password` | `ForgotPasswordPage.tsx` | AuthLayout | |
| `/dashboard` | redirect → `overview` | UserDashboardLayout | role=`user` |
| `/dashboard/overview` | `DashboardOverviewPage.tsx` | UserDashboardLayout | |
| `/dashboard/orders` | `DashboardOrdersPage.tsx` | UserDashboardLayout | |
| `/dashboard/profile` | `DashboardProfilePage.tsx` | UserDashboardLayout | |
| `/dashboard/addresses` | `DashboardAddressesPage.tsx` | UserDashboardLayout | |
| `/dashboard/security` | `DashboardSecurityPage.tsx` | UserDashboardLayout | |
| `/dashboard/legacy` | `UserDashboardPage.tsx` | UserDashboardLayout | Legacy |
| `*` | `NotFoundPage.tsx` | MainLayout | |

---

## 3. Navbar (`SiteNavbar.tsx`) — exact structure to clone

### Layers
1. **Announcement bar** (optional) — from homepage config  
   - Background default `#0F4D2C`, text white, `Zap` icon, truncate text  
2. **Main row** — `h-16`, `max-w-7xl`, logo (`/brandlogo.png`), SearchBar, wishlist, cart (orange pill), user menu  
3. **Category bar** — desktop only `hidden lg:block`, `h-11 bg-brand-green-600`, white/10 nav links + category mega dropdowns  

### Key classes / behaviors
- Header root: `navbar-root` sticky; scrollY > 30 → add `navbar--scrolled`
- Icon buttons: `h-11 w-11 rounded-full border border-brand-green-100 bg-brand-green-50`
- Cart button: orange variants (`border-brand-orange-200 bg-brand-orange-50`)
- Mobile: full-screen drawer with brand-green header; body scroll lock
- Icons: Lucide `Menu`, `X`, `ShoppingBag`, `Heart`, `User`, `LogIn`, `LogOut`, `Package`, `LayoutDashboard`, `ChevronDown`, `ChevronRight`, `Zap`, `MapPin`, `Settings`

### Scroll glass (must match `index.css`)
```css
.navbar-root { background #fff; border transparent; shadow none; }
.navbar-root.navbar--scrolled {
  background: rgba(255,255,255,0.90);
  border-color: rgba(34,197,94,0.15);
  box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.07);
  backdrop-filter: blur(16px) saturate(1.6);
}
```

---

## 4. Footer (`SiteFooter.tsx`)

- `border-t border-brand-green-100 bg-white pb-20 pt-14 md:pb-14` (extra bottom padding for MobileBottomNav)
- Grid: brand column (logo, blurb, social SVG paths) + Shop categories + Info links + Support links
- Contact block from store info API (phone / email / address) with Lucide `Phone`, `Mail`, `MapPin`
- Payment method chips with fixed style map (cod/bkash/nagad/rocket/bank/…)
- Copyright year row

Footer link groups (exact destinations):
- Info: About, Our Team (`/about#our-team`), Contact, Privacy, Terms, Cookie
- Support: My Orders, Shipping, Returns, Warranty, FAQ

---

## 5. Homepage — sections that actually exist

Driven by: `HomePage` → `HomepageSections` → `SectionRenderer`  
API: homepage config query (`useGetHomepageConfigQuery` / `/api/homepage…`)  
Fallback order: `DEFAULT_HOMEPAGE_SECTIONS` in `HomepageSections.tsx`

| # | Section type | Component(s) | Default title / notes |
|---|---|---|---|
| 1 | `hero` | `HeroCarousel` + popular searches pills | Slides from config; searches → `/shop?search=` |
| 2 | `trust_strip` | `TrustStrip` | Why shop with us |
| 3 | `categories` | `ProgressiveCategories` / `CategoryGrid` | Explore Categories, limit 12, lazy |
| 4 | `new_arrivals` | `ProgressiveProductRail` | New arrivals, limit 12 |
| 5 | `promo_banner` | `ProgressivePromoBanner` | Promo 1 |
| 6 | `featured` | Product rail | Featured products |
| 7 | `brands` | `ProgressiveBrands` / `BrandRow` | Trusted brands, limit 10 |
| 8 | `promo_banner` (id `promo_banner_2`) | PromoBanner | Promo 2 |
| 9 | `collections` | `ProgressiveCollections` / `CollectionTiles` | Featured collections, limit 6 |
| 10 | `flash_deals` | `ProgressiveFlashDeals` / `FlashDeals` | Countdown + flame pulse CSS |
| 11 | `best_sellers` | Product rail | Best sellers |
| 12 | `trending` | Product rail | Trending, muted bg |
| 13 | `reviews` | `ProgressiveReviews` / `ReviewsSection` | Customer Reviews |
| 14 | `why_choose_us` | `ProgressiveWhyChooseUs` | |
| 15 | `newsletter` | `ProgressiveNewsletter` / `NewsletterBlock` | Subscribe |

Also exists in feature folder (admin / about use): `TeamSection`, homepage admin builders under `features/homepage/admin/`.

**Do not invent sections.** Only render enabled sections from config (or the default list above when config empty).

Error state: centered card `rounded-3xl border … shadow-soft` with Try Again pill button.

---

## 6. Product listing / card / details

### Shop (`ShopPage.tsx`)
- Query params: `category`, `search`, `sort`, `brand`, `minPrice`, `maxPrice`, `rating`, `stock=1`, `sale=1`, `page`
- Sort options: newest, price_asc, price_desc, rating_desc, title_asc, title_desc, oldest
- Desktop sidebar filters + mobile filter sheet (`SlidersHorizontal`)
- Grid of `ProductCard` + `QuickViewModal`
- Pagination PAGE_SIZE = 12

### ProductCard (`ProductCard.tsx`) — must clone
- Wrapper: `product-card … rounded-2xl border border-brand-green-100 bg-white product-card-fadein`
- Image area: `h-[190px] sm:h-[210px]`, `object-contain p-3`, hover scale `1.06` / 500ms
- Badge priority: variant/base discount (`bg-brand-orange-500`) → Flash Sale → New → Best Seller
- Hover actions: Eye (quick view), Heart (wishlist)
- Brand label, `StarRating`, 2-line title, price / strikethrough, low-stock hint
- Color swatches + size pills when options exist
- ATC button → toast + 900ms check state
- Stock: out-of-stock overlay when `stock <= 0`; low when `<= 10`

### Product details
- Gallery (`ProductGallery`), variant selectors, qty, ATC, wishlist, specs, description HTML, related products, reviews where present
- Price hierarchy: salePrice / discount% / variant effective price (same as card)

### Shared commerce UI components
`QuickViewModal`, `QuickAddModal`, `CartDrawer`, `SearchBar`, `StarRating`, `Skeletons` / `ProductCardSkeleton`, `CountdownTimer`, `ConfirmModal`, `LocationSelect`, `MobileBottomNav`, `WhatsAppButton`

---

## 7. Cart / checkout / tracking

### Cart
- Full page + global drawer; qty ±, remove, subtotal; continue shopping / checkout CTAs

### Checkout
- Bilingual Bangla/English labels in places
- Cascading Bangladesh locations (`data/locations.ts` + `LocationSelect`)
- Shipping methods, payment methods (COD / bKash / Nagad / Rocket / …), coupon validate, checkout notices
- Guest checkout supported (API)

### Track / Success
- Track by order ID or phone; status timeline
- Success page with reference + next steps

---

## 8. User dashboard

| Route | Purpose |
|---|---|
| `/dashboard/overview` | Stats / recent activity |
| `/dashboard/orders` | Order history |
| `/dashboard/profile` | Name, phone, shipping area |
| `/dashboard/addresses` | Address CRUD + default |
| `/dashboard/security` | Change password |

Layout: emerald gradient hero, initials avatar, 4 mini stats, `lg:grid-cols-[240px_1fr]` nav with Lucide icons.

**Laravel: not implemented yet — required for parity.**

---

# ADMIN UI

## 9. Admin shell

### Layout (`AdminLayout.tsx`)
```
flex h-screen overflow-hidden bg-background
├── aside desktop: hidden lg:block | w-60 expanded | w-16 collapsed | border-r bg-card
├── mobile drawer: z-[200], backdrop bg-black/60, aside w-64
├── column: AdminTopbar + main p-4 sm:p-6 overflow-y-auto
└── CommandPalette (⌘/Ctrl+K)
```
Collapse persisted: `localStorage['mamabazar:admin_sidebar_collapsed']`

### Sidebar (`Sidebar.tsx` + `adminNav.ts`) — SOURCE OF TRUTH

**Brand header:** `h-16`, Store icon in `h-9 w-9 rounded-lg bg-primary text-white`, title “MamaBazar”, role dot (amber super-admin / emerald otherwise), `text-[10px] uppercase tracking-wider`

**Nav item classes:**
- Base: `flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium`
- Active: `bg-primary/10 text-primary`
- Hover: `hover:bg-muted hover:text-foreground`
- Icon size: `h-[18px] w-[18px]`
- Section headers: `text-[11px] font-semibold uppercase tracking-wider text-muted-foreground`

**Exact menu (clone all of these):**

| Section | Items (label → path → Lucide icon → permission) |
|---|---|
| Overview | Dashboard → `/admin/dashboard` → LayoutDashboard → `dashboard.view` |
| Catalog | Products Package `products.view`; Categories Tags `categories.view`; Brands Stamp `brands.view`; Collections FolderOpen `collections.view`; Colors Palette `colors.view`; Sizes Ruler `sizes.view`; Vendors Store `vendors.view`; Suppliers Truck `suppliers.view` |
| Sales | Orders ShoppingCart `orders.view`; Coupons TicketPercent `coupons.view`; Marketing Megaphone `marketing.view` |
| Finance | Expenses ReceiptText; Expense Categories ListOrdered; Expense Reports ChartPie; Profit Overview Wallet (`/admin/expenses/reports?tab=profit`) |
| Checkout | Shipping MapPin; Payment Methods CreditCard; Checkout Notices BellRing |
| Customers | Customers Users |
| Content | Homepage Builder PanelsTopLeft; Policies FileText; Media Image; Banners Megaphone |
| Insights | Analytics BarChart3 |
| Security & Access | Team Members UserCheck; Backup DatabaseBackup |
| System | Inventory Boxes; Settings Settings |

Footer strip: “MamaBazar Admin v1.0”

### AdminTopbar (`AdminTopbar.tsx`)
- `sticky top-0 z-40 h-16 border-b bg-background/95 backdrop-blur px-4 sm:px-6`
- Mobile hamburger / desktop collapse (`PanelLeftOpen` / `PanelLeftClose`)
- Breadcrumb: section + `ChevronRight` + page (`hidden md:flex`)
- Search trigger → Command Palette (“Search anything…” + ⌘K) `hidden sm:flex`
- Notifications `Bell` + destructive red dot; dropdown pending orders + low stock
- Avatar `h-8 w-8` dropdown: View Storefront, Profile, Settings, Logout

---

## 10. Every admin route / page

| Path | Component | Permission | UI summary |
|---|---|---|---|
| `/admin/dashboard` | `AdminDashboardPage` | `dashboard.view` | 6 KPIs, revenue AreaChart, status Pie, expense cards/trend, recent orders, top products, low stock; range select 7/30/365 |
| `/admin/products` | `AdminProductListPage` | `products.view` | Heavy filters, bulk actions, CSV import/export, table, pagination |
| `/admin/products/create` | `AdminProductCreatePage` | `products.create` | 8-section form + sticky action bar |
| `/admin/products/:id/edit` | `AdminProductEditPage` | `products.update` | Same form prefilled |
| `/admin/products/:id` | `AdminProductViewPage` | `products.view` | Read-only cards |
| `/admin/orders` | `AdminOrdersPage` | `orders.view` | Table + right Sheet detail, payment verify, status FSM, notes, timeline |
| `/admin/orders/:id/invoice` | `AdminOrderInvoicePage` | `orders.view` | Printable invoice |
| `/admin/categories` | `AdminCategoriesPage` | `categories.view` | Tree table, CropImageField, SEO, UsageAlertDialog |
| `/admin/brands` | `AdminBrandsPage` | `brands.view` | Card grid CRUD |
| `/admin/collections` | CatalogCrudPage | `collections.view` | Generic CRUD |
| `/admin/colors` | CatalogCrudPage + hex | `colors.view` | |
| `/admin/sizes` | CatalogCrudPage | `sizes.view` | |
| `/admin/vendors` | CatalogCrudPage | `vendors.view` | |
| `/admin/suppliers` | CatalogCrudPage | `suppliers.view` | |
| `/admin/shipping` | CatalogCrudPage | `shipping.view` | |
| `/admin/payment-methods` | CatalogCrudPage | `payment_methods.view` | |
| `/admin/checkout-notices` | CatalogCrudPage | `checkout_notices.view` | |
| `/admin/customers` | `AdminCustomersPage` | `customers.view` | Stats + table |
| `/admin/coupons` | `AdminCouponsPage` | `coupons.view` | Mono code badges |
| `/admin/analytics` | `AdminAnalyticsPage` | `analytics.view` | Charts |
| `/admin/marketing` | `AdminMarketingPage` | `marketing.view` | Pixels / campaigns |
| `/admin/banners` | `AdminBannersPage` | `banners.view` | Banner CRUD |
| `/admin/homepage` | `AdminHomepagePage` | `homepage.view` | Tabs: Layout / Hero / Content / Subscribers |
| `/admin/policies` | `AdminPoliciesPage` | `policies.view` | RichTextEditor |
| `/admin/media` | `AdminMediaPage` | `media.view` | Grid library |
| `/admin/expenses` | `AdminExpensesPage` | `expenses.view` | Advanced filters + dialogs |
| `/admin/expenses/categories` | CatalogCrudPage | `expenses.view` | |
| `/admin/expenses/reports` | `AdminExpenseReportsPage` | `reports.view` | Multi-tab reports + profit |
| `/admin/inventory` | `AdminInventoryPage` | `inventory.view` | Stock bars ±1 |
| `/admin/members` | `AdminMembersPage` | `members.view` | Team + audit log + permission matrix |
| `/admin/backup` | `AdminBackupPage` | `backup.view` | PIN modal, create/restore |
| `/admin/settings` | `AdminSettingsPage` | `settings.manage` | Store / tax / pixel / admin users |

### Product form sections (must exist in Blade)
1. General — title, slug, 3-level category, brand, collection, vendor, supplier, descriptions (TipTap), tags, label switches, physical/warranty fields  
2. Pricing — price, sale, discount %, cost  
3. Inventory — SKU, barcode, stock, stock status  
4. Images — MediaPicker, reorder  
5. Variants — option groups → generated rows  
6. Specifications — dynamic rows  
7. SEO — title/desc/keywords/canonical/JSON-LD  
8. Related — related / cross-sell / upsell  

### Shared admin components to port behaviorally
`CatalogCrudPage`, `CommandPalette`, `MediaPicker`, `MediaUploader`, `CropImageField`, `ImageCropperDialog`, `UsageAlertDialog`, `SecurityPinModal`, `RichTextEditor`, `PermissionGate` / `PermissionRoute`

---

# DESIGN

## 11. Fonts (exact)

Self-hosted in `frontend/public/fonts/`:
- `inter-400-normal.woff2`, `inter-500-normal.woff2`, `inter-600-normal.woff2`, `inter-700-normal.woff2`
- `noto-sans-bengali-400.woff2`, `noto-sans-bengali-600.woff2`, `noto-sans-bengali-700.woff2`

Declared in `index.css` with `font-display: swap` and unicode-range subsets.

| Token | Stack |
|---|---|
| `font-body` / default | Inter, Noto Sans Bengali, system |
| `font-headline` / `font-display` | Neue Haas Grotesk Display → Helvetica Now Display → Helvetica Neue → Helvetica → Arial → Inter |
| OpenType | `font-feature-settings: 'ss03'` on `:root` |

**Laravel must copy these files into `public/fonts/` and use the same `@font-face` blocks — not Google Fonts CDN.**

## 12. Colors (exact)

| Token | Hex |
|---|---|
| brand-green-50…700 | `#EAF6EF` `#C6E9D3` `#8FD4AE` `#58BF89` `#2EA665` `#176B3A` `#0F4D2C` `#0A3820` |
| brand-orange-50…700 | `#FFF1E6` `#FFE0C2` `#FFC08A` `#FF9F52` `#F88A34` `#F47B20` `#D96510` `#B54E0A` |
| ink | `#17221B` |
| canvas-cream | `#F8FAF8` |
| canvas-light | `#FFFFFF` |
| hairline-light | `#E4E4E7` |
| shade-30…70 | `#D4D4D8` `#A1A1AA` `#71717A` `#52525B` `#3F3F46` |

HSL CSS variables (shadcn layer) in `index.css` — primary = green, accent = orange, radius `0.5rem`. Dark tokens intentionally identical (dark mode disabled).

## 13. Spacing / sizing / radius / shadows

| Token | Value |
|---|---|
| Content max width | `max-w-7xl` |
| Page padding | `px-4 sm:px-6 lg:px-8` |
| Admin sidebar | `w-60` / collapsed `w-16` / mobile `w-64` |
| Admin topbar / nav row heights | `h-16` / category bar `h-11` |
| Card radius (store) | `rounded-2xl` (16px) often; sections `rounded-3xl` |
| Buttons | **pill** `rounded-full` (`button.tsx` default) |
| Default shadcn radius | `--radius: 0.5rem` |
| shadow-soft | `0 1px 2px rgba(0,0,0,0.05)` |
| shadow-card | `0 4px 20px rgba(0,0,0,0.07)` |
| shadow-lift | `0 20px 40px rgba(0,0,0,0.14)` |
| shadow-panel | `0 25px 50px -12px rgba(0,0,0,0.25)` |
| Product card hover lift | `translateY(-4px)` + `::after` shadow-card opacity |

## 14. Icons

- Library: **lucide-react** exclusively for UI icons
- Typical sizes: `h-4 w-4`, `h-5 w-5`, admin nav `h-[18px] w-[18px]`
- Laravel should use Lucide via SVG sprite / blade icons matching the same names — do not swap to unrelated Heroicons shapes where Lucide was used

## 15. Motion / animation

| Source | Behavior |
|---|---|
| `lib/motion.ts` | Ease `[0.22, 1, 0.36, 1]`; page 0.28s; fadeUp y:24; stagger 0.06 |
| CSS `product-card-fadein` | 0.35s ease-out |
| CSS `animate-pulse-scale` | Flash deals flame 1.4s |
| CSS marquee | 30s linear |
| Framer | PageTransition, modals, cart drawer, navbar menus, hero |
| Swiper | Carousels where used |
| prefers-reduced-motion | animations killed in `index.css` |

Alpine.js / CSS should reproduce these interactions in Blade.

## 16. Breakpoints

Tailwind defaults: `sm` 640, `md` 768, `lg` 1024, `xl` 1280, `2xl` 1536.  
Critical behaviors:
- Category bar & desktop admin sidebar: `lg:`
- Admin search trigger: `sm:`
- Breadcrumbs: `md:`
- Mobile bottom nav / footer `pb-20`
- Shop filters: sheet on mobile

## 17. Assets to migrate

From `frontend/public/`:
- `brandlogo.png`, `favicon.svg`, PWA icons, `icons.svg`
- Team photos: `nayeem.jpeg`, `roni.jpeg`, `safi.jpeg`, `shipon.jpeg`, `dolon.jpeg`
- Entire `fonts/` directory
- `screens/` (reference screenshots if present)

Product/category/banner images: currently Cloudinary in React/Node → Laravel must serve from `storage/app/public` via `php artisan storage:link` and rewrite URLs.

---

# FUNCTIONALITY

## 18. Auth & authorization

| Concern | React / Node behavior |
|---|---|
| Customer login | Phone (+ password); JWT Bearer 7d |
| Admin access | Roles `admin` / `manager` (+ super admin bypass) |
| Permissions | Per-route `PermissionRoute` + sidebar filter via `usePermissions` |
| Blade admin login | Required at `/admin/login`; seed from `ADMIN_EMAIL` / `ADMIN_PASSWORD` (.env) |
| API | Keep JWT for React SPA → Laravel API |

## 19. Node API surface (contract to preserve)

- Entry: `backend/src/server.ts` / Express app with modules under `/api/*`
- ~228 routes across ~28 modules (users, categories, products, order, coupons, banners, homepage, media, shipping, payments, checkout-notices, expenses, members, backup, analytics, settings, …)
- Guest checkout, order status FSM, server-side tax, RBAC
- Images: Cloudinary with local fallback in Node; **Laravel must be local-only**

Full route tables also appear in earlier sections of this project’s migration notes; Laravel `routes/api.php` should remain contract-compatible so React can point `VITE_API_URL` at Laravel.

## 20. Database

- Node: MySQL via Drizzle (~41 tables)
- Laravel requirement: **SQLite** at `database/database.sqlite`
- Seed targets (user requirement): 50 categories, 150+ subcategories, 50+ brands, 500 products + variants/attributes/inventory/pricing/images — must drive API + Blade user + Blade admin

## 21. Shared Laravel architecture (required)

```
API Controller  ─┐
Blade Web Ctrl  ─┼─→ Service ─→ Eloquent ─→ SQLite
Admin Blade Ctrl─┘
```

No duplicated business rules. Local media via `Storage::disk('public')`.

---

# LARAVEL PARITY CHECKLIST (current gaps)

## User UI gaps
- [ ] Pixel-match `layouts/app` navbar glass + announcement + `h-11` category bar to React
- [ ] Self-host fonts (remove Google Fonts from layouts)
- [ ] Homepage: config-driven SectionRenderer parity (all 15 types, lazy, skeletons, error)
- [ ] ProductCard GPU hover + badge priority + variant swatches exact
- [ ] Shop filters/sort/pagination parity
- [ ] Product details gallery/variants/related parity
- [ ] Cart drawer + page + Checkout geo cascade bilingual UI
- [ ] AuthLayout + forgot-password
- [ ] Full `/dashboard/*` UserDashboardLayout
- [ ] MobileBottomNav, WhatsAppButton, CartDrawer interactions via Alpine
- [ ] Policy pages: payment / cancellation / warranty routes

## Admin UI gaps
- [ ] Sidebar: **full** `adminNav.ts` menu (not truncated)
- [ ] Lucide-equivalent icons at 18px; active `bg-primary/10`
- [ ] Topbar: breadcrumbs, ⌘K CommandPalette, live notifications
- [ ] Dashboard charts (Recharts → Chart.js/Alpine/SVG equivalent) matching layout
- [ ] Missing pages: brands, collections, colors, sizes, vendors, suppliers, checkout-notices, homepage builder, policies, marketing, analytics, expenses*, inventory, members, orders Sheet UX, product 8-section form parity
- [ ] CatalogCrudPage pattern for master data
- [ ] MediaPicker / crop / UsageAlertDialog / SecurityPinModal equivalents

## Design token gaps
- [ ] Copy product-card `::after` hover CSS exactly
- [ ] Copy navbar scrolled CSS exactly
- [ ] Match button CVA variants (pill primary/outline/ghost/aloe)
- [ ] Bangla utility classes (`.bangla`, `.bilingual-label`, …)

## Visual verification (mandatory after implement)
For each important page: React screenshot vs Laravel screenshot — layout, header, sidebar, type, color, spacing, cards, buttons, forms, tables, images, icons, modals, responsive, empty/loading/error, animations. Fix every major mismatch.

---

# IMPLEMENTATION ORDER (do not skip)

1. ~~Audit React completely~~ ✅ (this document)  
2. ~~Audit Node backend~~ ✅ (FUNCTIONALITY + existing API inventory)  
3. ~~Audit database~~ ✅  
4. ~~Create `UI_AUDIT.md`~~ ✅  
5. Align Laravel architecture / shared services  
6. Finish / verify Laravel API contract + SQLite + local storage  
7. Clone Blade User layout (fonts, navbar, footer, drawers)  
8. Clone Homepage section-by-section  
9. Clone every User page  
10. Clone Admin shell (sidebar + header + command palette)  
11. Clone Admin dashboard  
12. Clone every Admin page/feature  
13. Seed data at required scale  
14. Test API + User UI + Admin UI  
15. Visual compare React vs Laravel; fix mismatches  

---

# ABSOLUTE RULE (restated)

```
React User UI  =  Laravel Blade User UI
React Admin UI =  Laravel Blade Admin UI
```

Same design, layout, components, navigation, sidebar, header, pages, forms, tables, interactions, responsive behavior, and features.  
Backend technology changes. Visual UX must not.

**NEXT STEP:** Begin implementation at Step 5–7 using this audit as the checklist — starting with design-token/font parity and Admin sidebar completeness, then Homepage clone.

---

*Audit authored from live source inspection of `/frontend`, `/backend`, and `/backend-laravel` on 2026-09-24. Research-only; no UI redesign performed in this step.*
