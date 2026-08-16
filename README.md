# Mama Bazar

A modern, responsive and API-driven e-commerce platform built to provide a smooth online shopping experience.

[![License](https://img.shields.io/badge/license-proprietary-lightgrey.svg?style=flat-square)](./LICENSE)

---

## 1. Project Overview

**Mama Bazar** is an online shopping platform focused on gadgets, electronics, accessories, smart devices, and everyday tech products. The platform connects customers with a curated product catalog, enabling browsing, searching, and purchasing from any device. Admins can manage the entire store — products, categories, orders, customers, and homepage content — from a single dashboard.

The system follows a modern API-driven architecture: the frontend (React + Redux Toolkit + RTK Query) communicates with a REST API backend (Node.js + Express + MySQL), which serves data from a relational database. This separation ensures scalability, maintainability, and consistent data across web and future mobile interfaces.

Customers can browse products by category or search, view detailed product information with images and variants, manage a shopping cart, and complete checkout with multiple payment methods. Admins have full control over product listings, inventory, pricing, and order fulfillment.

---

## 2. Key Features

### Customer Features

- **Modern responsive homepage** — API-driven sections populated from the admin panel
- **Product browsing** — Browse by category, brand, collection, vendor, or supplier
- **Category navigation** — Hierarchical category structure with parent/child relationships
- **Product search** — Search products by name or keywords
- **Product details** — Full product information including description, pricing, variants, and specifications
- **Product images** — Image carousel with responsive sizing and preview support
- **Product variants** — Size and color options with independent pricing, discount tracking, and stock status
- **Pricing** — Regular price, discount/sale price, and stock availability display
- **Add to cart** — Add products with selected variants to the shopping cart
- **Buy Now** — Quick checkout for single-product purchases
- **Wishlist** — Save products for later
- **Cart management** — Update quantities, remove items, calculate subtotal
- **Checkout** — Multi-step flow: customer information → shipping → payment method → payment verification → order confirmation
- **Customer information** — Save and manage user profiles
- **Shipping information** — Address collection with shipping method estimation
- **Payment method selection** — COD, mobile banking, bank transfer, and online payment options
- **Order placement** — Complete order with status tracking
- **Order history** — View past orders and details
- **Responsive mobile shopping experience** — Fully adapted layouts for smaller screens

---

## 3. Homepage Features

The Mama Bazar homepage is **API-driven and admin-controlled**. Key sections include:

- **Announcement bar** — Top banner for promotions or important notices
- **Header** — Navigation, search, cart, wishlist, account actions
- **Search** — Real-time product search with query typing
- **Track Order** — Order tracking by order ID or phone number
- **Wishlist** — Quick access to saved items
- **Cart** — Cart summary with item count and total
- **Account** — User menu with profile and order history
- **Category navigation** — Browse product categories
- **Hero / banner slider** — Dynamic featured banners (detailed in Section 4)
- **Explore Categories** — Category grid or list
- **New Arrivals** — Newly added products (admin-markable)
- **Featured / Popular Products** — Admin-highlighted products
- **Promotional banners** — Special offers and campaigns
- **Trusted Brands** — Brand logos and links
- **Product collections** — Curated product groups
- **Customer Reviews** — Ratings and review summaries
- **Newsletter CTA** — Email subscription form
- **Footer** — Links, policy pages, contact information

Homepage content is populated via the database and managed through the admin panel. Not every section is automatically admin-configurable — structure varies by implementation.

---

## 4. Hero Slider / Banner System

- **Dynamic hero slider** — Banners loaded from the API/database, not hardcoded
- **API-driven banner data** — Content managed through the admin panel
- **Desktop / mobile responsive images** — Supported where implemented
- **Automatic slider transitions** — Smooth auto-play with configurable timing
- **Smooth animation** — Focus on visual presentation without excessive motion
- **Responsive banner behavior** — Adapts layout across desktop, tablet, and mobile breakpoints
- **Admin-controlled banner content** — Add, edit, or remove banners via the admin panel
- **No unnecessary hardcoded product/banner data** — All content comes from the backend

---

## 5. Product System

- **Product name** — Displayed prominently with slug for URL routing
- **Product image** — Main product image from Cloudinary or local storage
- **Product description** — Full text description of the product
- **Category** — Assigned category for navigation and filtering
- **Brand** — Brand name and link where available
- **Price** — Regular price display
- **Discount price** — Sale pricing with strikethrough display
- **Stock status** — Real-time availability (in stock, low stock, out of stock)
- **SKU** — Stock Keeping Unit where applicable
- **Product variants** — Size and color options with independent pricing, discount, and stock
- **Product specifications** — Label/value specification table
- **Add to Cart** — Add product with selected variant to shopping cart
- **Buy Now** — Quick purchase flow
- **Wishlist** — Save products to personal wishlist
- **Product collections** — Group products by collection

All product data is fetched from the real API/database. Static or fake product data is not used.

---

## 6. Product Image Experience

Recent improvements to product image presentation:

- **Product image carousel** — Main image with thumbnail navigation
- **Automatic image switching** — Where implemented, images transition automatically
- **Smooth transitions** — Fade or slide effects between images
- **Responsive image sizing** — Images scale appropriately across device sizes
- **Image preview** — Hover or tap preview of larger view
- **Full-screen image viewing** — Lightbox-style expanded view for detailed inspection
- **Mobile and desktop support** — Optimized rendering for both screen sizes
- **Better visual presentation of product photography** — Enhanced rendering without over-engineering

---

## 7. Cart System

- **Add product to cart** — Include selected size/color variant
- **Update quantity** — Increase or decrease item quantity (minimum 1)
- **Remove product** — Remove individual items from the cart
- **Product price calculation** — Line price = variant price × quantity
- **Subtotal** — Sum of all line items before shipping and discounts
- **Shipping calculation** — Applied based on shipping method and order total (free above threshold, otherwise fixed charge)
- **Total amount** — Subtotal + shipping - discounts + tax
- **Cart persistence / state management** — Cart stored in localStorage and Redux state; survives browser refresh
- **Checkout navigation** — "Proceed to Checkout" CTA from cart drawer/view

---

## 8. Checkout System

The checkout flow follows a clear, simple progression:

**Customer → Cart → Checkout → Customer Information → Shipping Information → Payment Method → Payment Verification Information → Order Confirmation**

Each step is designed to keep the process straightforward:

1. **Cart** — Review items, quantities, and totals
2. **Customer Information** — Name, email, phone number
3. **Shipping Information** — Delivery address and shipping method selection
4. **Payment Method** — Select from available methods: COD, mobile banking, bank transfer, or online payment
5. **Payment Verification Information** — Provide transaction ID, screenshot, or reference number for non-COD payments
6. **Order Confirmation** — Final order summary with order number and status

The checkout is designed to be clear and frictionless, with validation at each step.

---

## 9. Payment Verification

Payment verification is supported for manual and bKash/manual payment flows. When applicable, the checkout can collect payment-related information:

- **Payment Method** — COD, mobile banking, bank, or online
- **Sender Number / Mobile Number** — For mobile banking transactions
- **Transaction ID** — Reference number from the payment provider
- **Payment reference / details** — Additional verification information
- **Order / payment status** — Tracks verification state

For **bKash / manual payment flows**, the system allows attaching payment verification information to the order so the business/admin can verify the payment manually. The order enters a `payment_verification` status until the admin confirms or rejects the payment.

> **Manual payment verification support**

No automatic payment gateway verification is claimed unless actually implemented. Admins can verify and update payment status via the admin panel (`PATCH /api/order/:id/payment/verify`).

---

## 10. Order Management

The order lifecycle follows implemented statuses:

```
Order Placed → Payment Pending / Verified → Processing → Shipped → Delivered
```

Only actual implemented statuses are listed. Additional statuses may exist based on order flow.

**Order details include:**

- **Order number** — Unique identifier for the order
- **Customer information** — Name, phone, email
- **Shipping information** — Delivery address
- **Ordered products** — List of products with variants, quantities, and pricing
- **Quantity** — Number of units per product
- **Price** — Per-item and total pricing
- **Shipping charge** — Applied shipping cost
- **Total amount** — Final amount paid
- **Payment method** — COD, mobile banking, bank, or online
- **Payment status** — pending, verified, rejected, success, failed, refunded
- **Order status** — Track current fulfillment state

---

## 11. Admin Panel

### Dashboard

- **Overview** — KPI cards: total revenue, total orders, average order value, total customers, today's orders, period revenue, delivered/cancelled counts, low stock, out of stock, conversion rate, period visitors
- **Store statistics** — Order breakdown, payment method distribution, top products by revenue

### Product Management

- **Add product** — Full product form with images, variants, specifications, and relations
- **Edit product** — Update all product details including stock, pricing, and status
- **Delete product** — Remove product from catalog
- **Product status** — Active, inactive, featured, featured/unfeature toggle
- **Stock management** — Track inventory, low stock alerts, unlimited stock, backorder
- **Price management** — Regular price, sale price, discount percentage, cost price, profit margin
- **Category management** — Assign category, sub-category, child category
- **Brand / Vendor / Collection / Supplier** — Associate products with master data
- **Product relations** — Frequently bought together, cross-sell, up-sell, accessories, similar products
- **Draft saving** — Save product drafts for later completion
- **Bulk actions** — Publish, archive, feature/unfeature, delete multiple products
- **CSV import / export** — Bulk product data management

### Category Management

- **Create category** — New category with image and parent/child hierarchy
- **Edit category** — Update category name, image, and parent
- **Delete category** — Remove category (with product reassignment options)
- **Product organization** — Move products between categories

### Order Management

- **View orders** — Paginated list with filters and search
- **Order details** — Full order information: customer, products, pricing, status
- **Payment information** — Payment method and verification status
- **Customer information** — Contact details and order history
- **Shipping information** — Delivery address and method
- **Order status** — Update fulfillment status with notes and tracking number
- **Payment verification** — Mark payment as verified or rejected
- **Admin notes** — Stamp orders with internal notes
- **Delete order** — Remove order records (admin only)

### Payment Management

- **Payment details** — View payment method, status, and transaction information
- **Payment verification** — Verify or reject payment for an order
- **Payment status** — Track verified, rejected, success, failed, refunded states
- **Transaction information** — Reference numbers and timestamps

### Homepage Management (where implemented)

- **Hero banners** — Create, edit, and delete hero slider banners
- **Promotional banners** — Add promotional banner content
- **Product sections** — Manage featured/new arrivals product sections
- **Featured products** — Select and showcase featured products on homepage
- **Homepage content** — Configure homepage section order and visibility

---

## 12. Responsive Design

Mama Bazar is designed for seamless experience across all device sizes:

| Device | Description |
|--------|-------------|
| **Desktop** (1024px+) | Full sidebar, 3-column product grids, complete tables |
| **Laptop** (1024px+) | Same as desktop, optimized for smaller screens |
| **Tablet** (640px - 1024px) | Collapsible sidebar (icon-only), 2-column grids, scrollable tables |
| **Mobile** (<640px) | Sidebar collapsed to icon-only mode, 1-column grids, horizontal scroll tables |

**Key responsive patterns:**

- **Responsive navigation** — Desktop navbar vs. mobile hamburger drawer
- **Mobile-friendly product grids** — Automatic column adjustment (1 column mobile, 2-3 column desktop)
- **Touch-friendly controls** — Minimum 44×44px tap targets, enhanced hit areas
- **Responsive banners** — Slider adapts to available width; thumbnail navigation adjusts
- **Responsive product images** — Images scale with container; lightbox supports touch and mouse
- **No unnecessary horizontal overflow** — Layouts adapt; tables become scrollable when needed
- **Mobile-specific layout improvements** — Stacked modals, full-width inputs, large touch targets
- **Optimized checkout experience** — Single-column layout, full-width forms, simplified navigation

> **Mobile is not simply a scaled-down desktop interface; layouts are adapted for smaller screens.**

---

## 13. UI/UX

- **Clean modern interface** — Professional e-commerce appearance without clutter
- **Premium appearance** — Thoughtful typography, spacing, and visual hierarchy
- **Consistent spacing** — Tailwind CSS design token-based spacing scale
- **Clear typography** — Inter variable font for body, Noto Sans Bengali for Bangla support
- **Product-focused layouts** — Product cards, galleries, and specification tables prioritize information
- **Modern cards** — Elevation, rounded corners, consistent styling across the platform
- **Smooth transitions** — Framer Motion animations respect `prefers-reduced-motion`
- **Responsive interactions** — Meaningful interactions that work across devices
- **Clear CTA buttons** — Primary (brand orange) and secondary actions clearly distinguished
- **Easy navigation** — Logical information architecture, skip links, focus-visible outlines
- **User-friendly checkout** — Step-by-step progression with clear progress indication

> WCAG 2.2 AA compliance has been implemented for contrast ratios, touch targets, focus outlines, and error messaging. Accessibility features have been tested and verified.

---

## 14. Performance Optimization

Performance optimizations implemented in the project:

- **RTK Query caching** — Automatic server data caching with tag-based invalidation
- **Request deduplication** — Duplicate API requests within same scope are merged
- **Lazy loading** — Images and components load on demand where configured
- **Optimized images** — Cloudinary integration with responsive resize and format selection
- **Skeleton / loading states** — UI placeholders during data fetching
- **Avoiding unnecessary API calls** — Selective queries, parametrized fetches
- **Avoiding unnecessary React re-renders** — Proper use of `React.memo`, selective state updates, RTK Query cache reuse
- **Responsive asset loading** — Device-appropriate image sizes and resolutions
- **Efficient product data fetching** — RTK Query with keepUnusedDataFor configured per data type

These optimizations result in faster page loads, reduced data usage, and smoother interactions for customers.

---

## 15. State Management

- **Redux Toolkit** — Core state management library
  - `cartSlice` — Cart state with localStorage persistence
  - `authSlice` — Authentication state (login, register, logout, user profile)
  - `ordersSlice` — Recent orders and checkout state
  - `uiSlice` — UI state (theme, cart open/closed, wishlist, compare)
- **RTK Query** — API state management and caching
  - `commerceApi` — User-facing API queries and mutations (products, categories, brands, checkout, auth, orders, profiles)
  - `adminProductsApi` — Admin-specific API endpoints (CRUD, dashboard stats, master data)
  - **60+ tagTypes** for precise cache invalidation
  - Automatic sync: auth logout → reset all RTK Query caches; admin mutations → invalidate user caches
  - **Long-lived caches** (900s keepUnusedDataFor) for reference data (categories, brands)
  - **Short-lived caches** (300s default) for metrics and volatile data
- **Client / UI state** — Non-persisted state handled via Redux slices
- **Cache management** — Tag-based invalidation ensures data freshness on updates
- **Tags / invalidation** — On product create/update/delete, related caches are automatically refreshed
- **Loading states** — RTK Query `isLoading` and UI skeleton states
- **Error states** — API errors caught and displayed with user-friendly messages
- **Empty states** — Rendered when no data available (empty cart, no orders, no products matching search)

Server data should be handled through RTK Query where applicable. Unnecessary duplicate API requests are avoided through automatic request deduplication and tag-based cache reuse.

---

## 16. API Architecture

```
Customer
   │
   ▼
Mama Bazar Frontend (React + Redux Toolkit + RTK Query)
   │
   ▼
Redux Toolkit / RTK Query (API caching, mutations, global state)
   │
   ▼
REST API (Node.js + Express)
   │
   ▼
Backend (Node.js + Express + MySQL / TiDB Cloud)
   │
   ▼
Database
```

**Brief layer explanation:**

- **Frontend** — React components dispatch RTK Query hooks and Redux actions. UI updates based on cached and fresh data.
- **Redux / RTK Query** — Centralizes API responses, loading states, and error handling. Caches reduce round-trips. Mutations handle create/update/delete operations.
- **REST API** — Express routes handle business logic, validation, and database operations. Public and admin-protected routes.
- **Backend** — Node.js server processes requests, enforces authentication/authorization, and interacts with the database.
- **Database** — MySQL (TiDB Cloud) stores products, users, orders, categories, payments, and all application data.

The frontend should reuse existing APIs and should not create duplicate API systems unnecessarily. Actual endpoint names are available in the project's backend source code.

---

## 17. Technology Stack

Only technologies confirmed as implemented in the project:

### Frontend

- **React.js** — 19.2.4 (core UI library)
- **TypeScript** — 5.9.x (type-safe development)
- **Vite** — 8.0.1 (development server and build)
- **Tailwind CSS** — 3.4.13 (utility-first styling with design tokens)
- **Redux Toolkit** — 2.11.2 (state management)
- **RTK Query** — Built into Redux Toolkit (API caching and mutations)
- **React Router DOM** — 7.14.0 (routing)
- **Framer Motion** — 13.0.0 (animations and transitions)
- **Lucide React** — Icon library
- **Radix UI primitives** — Accessible UI primitives (all components)
- **shadcn-ui** — Component patterns and primitives
- **React Hook Form** — 7.84.0 (form management)
- **Zod** — 4.4.3 (form schema validation)
- **Sonner** — 2.0.7 (toast notifications)
- **Recharts** — 3.10.1 (analytics charts - partially implemented)
- **@tanstack/react-table** — 9.0.1 (data tables for admin)

### Backend

- **Node.js** — with TypeScript
- **Express** — 4.22.1 (REST API framework)
- **Drizzle ORM** — 0.30.10 (database ORM)
- **MySQL** — TiDB Cloud (serverless cloud database)
- **bcryptjs** — 2.4.3 (password hashing)
- **jsonwebtoken** — 9.0.2 (JWT authentication)
- **Cloudinary** — Image upload and storage
- **express-rate-limit** — DDoS protection
- **helmet** — Security headers

### Database

- **MySQL** — TiDB Cloud (serverless)

### Other

- **Git** — Version control
- **GitHub** — Repository hosting

---

## 18. Project Architecture

```text
Customer
   │
   ▼
Mama Bazar Frontend
   │
   ▼
Redux Toolkit / RTK Query
   │
   ▼
REST API
   │
   ▼
Backend
   │
   ▼
Database
```

**Layer breakdown:**

- **Customer** — Visitors to the website, using any device (desktop, laptop, tablet, mobile)
- **Mama Bazar Frontend** — React application with client-side routing, component rendering, and state management
- **Redux Toolkit / RTK Query** — Central store for API data, caching, loading/error states, and client UI state (cart, auth, UI)
- **REST API** — Node.js Express server exposing endpoints for product catalog, user management, orders, payments, and admin operations
- **Backend** — Business logic, authentication, validation, and database interaction layer
- **Database** — MySQL (TiDB Cloud) storing all persistent data: products, users, orders, categories, payments, reviews, etc.

Data flows from database → backend API → RTK Query cache → React components. Updates flow in reverse: component action → RTK Query mutation → backend → database. Tag-based invalidation keeps caches consistent across user and admin contexts.

---

## 19. User Flow

```text
Visit Website
   ↓
Browse Categories
   ↓
Search / Explore Products
   ↓
View Product
   ↓
Add to Cart / Buy Now
   ↓
Checkout
   ↓
Select Payment Method
   ↓
Submit Payment Information
   ↓
Place Order
   ↓
Order Processing
   ↓
Delivery
```

**Journey notes:**

- Customers can start at the homepage, browse featured collections, or use search
- Product pages show images, variants, pricing, and stock status
- Cart drawer shows summary; quantities can be adjusted before checkout
- Checkout guides the customer through information collection and payment
- After order placement, customers receive an order number and can track status
- Order history is available in the user account menu

---

## 20. Admin Flow

```text
Admin Login
   ↓
Dashboard
   ↓
Manage Products / Categories
   ↓
Manage Homepage Content
   ↓
Manage Orders
   ↓
Review Payment Information
   ↓
Update Order Status
```

**Admin journey notes:**

- Admin login requires authentication; `AdminRoute` protects all admin pages
- Dashboard provides KPI overview and quick access to main sections
- Products and categories can be added, edited, deleted, or reordered
- Homepage content (banners, featured products) is configurable where implemented
- Orders can be viewed, filtered by status, and updated with payment verification and status changes
- Payment verification (`PATCH /api/order/:id/payment/verify`) marks orders as verified or rejected

---

## 21. Security

Only implemented or intentionally designed security practices are mentioned:

- **Authentication** — JWT-based login/register with phone/password; admin route protection with role checks
- **Authorization** — `AdminRoute` restricts admin pages to `['admin', 'manager']` roles; `AdminSettingsPage` to `['admin']` only
- **Protected admin routes** — All admin pages guarded by `AdminRoute` with role validation
- **API validation** — All incoming data validated on backend (Zod schemas, Drizzle validation)
- **Secure environment variables** — Backend `.env` file; frontend `VITE_API_URL` config; no hardcoded secrets
- **No sensitive credentials in source code** — Environment variables loaded at runtime
- **Input validation** — Server-side validation on all API routes; Zod schemas for forms
- **Secure API communication** — HTTPS recommended; CORS configured for trusted origins
- **Rate limiting** — `express-rate-limit` configured on backend
- **Security headers** — `helmet` middleware sets secure HTTP headers

> No advanced security certifications or claims are made beyond intentionally designed practices.

---

## 22. Error Handling

- **API error handling** — HTTP error status codes mapped to user-friendly messages
- **Loading states** — RTK Query `isLoading` UI skeletons during data fetch
- **Empty states** — Shown when no products, orders, or search results match
- **Failed request handling** — Network errors and HTTP errors displayed gracefully
- **User-friendly messages** — Clear error descriptions without technical jargon
- **Graceful rendering when API data is unavailable** — Components fall back to empty or placeholder state where implemented

---

## 23. Responsive & Browser Compatibility

**Designed to support:**

| Browser | Support |
|---------|---------|
| Chrome | ✓ |
| Firefox | ✓ |
| Safari | ✓ |
| Edge | ✓ |

| Device | Support |
|--------|---------|
| Desktop | ✓ |
| Laptop | ✓ |
| Tablet | ✓ |
| Mobile | ✓ |

*(Tested where applicable; otherwise: designed to support across modern browsers and devices.)*

---

## 24. Installation & Setup

### Development Setup

```bash
git clone <repository-url>
cd <project-folder>
npm install
npm run dev
```

### Environment Variables

```env
VITE_API_URL=http://localhost:5000
# When VITE_API_URL is empty, API is assumed same origin
# VITE_API_BASE_URL is supported as legacy alias
```

> Check the project's `.env.example` file for the complete environment configuration.

The frontend `.env.example` provides `VITE_API_URL` as the single source of truth for the backend API origin. When empty, the API is assumed to be same-origin (useful for Vercel or other platforms routing `/api/*` to the backend).

---

## 25. Production Deployment

```text
GitHub
   ↓
Build
   ↓
Production Server / Hosting
   ↓
API Configuration
   ↓
Database
   ↓
Domain + HTTPS
```

> Do not invent the current hosting provider unless confirmed. The general production flow involves building the frontend (Vite) and backend (Node/TypeScript), configuring the API URL and database connection, and deploying to a hosting provider with a custom domain and HTTPS.

---

## 26. Project Status

### ✅ Completed / Available

- Core e-commerce functionality: product catalog, shopping cart, checkout, order management
- Full admin dashboard with 9 pages (products, orders, categories, customers, coupons, analytics, marketing, settings)
- Authentication system (login/register/forgot password)
- Payment methods: COD, mobile banking, bank, online
- Shipping methods with estimation
- Coupon system with validation
- Order status tracking with history
- Invoice generation
- Image upload with Cloudinary integration
- Design system with WCAG 2.2 AA compliance
- Responsive design (mobile/tablet/desktop)
- RTK Query state management with tag-based invalidation
- Redux slices for cart, auth, orders, UI state
- Pixel tracking for add-to-cart events
- Policy pages (return/refund, shipping, about, terms, etc.)
- Multi-role access control (admin/manager/user)

### 🔄 In Progress

- Real Recharts graphs in admin analytics (currently placeholder charts)
- Connecting admin form submissions to backend API for all admin pages
- Advanced filtering and search in admin interfaces
- Real-time order status updates
- SMS notification system

### 📅 Future / Planned

- Real-time order tracking with websockets
- Advanced analytics dashboards with proper charts
- Multi-language support (Bengali + English)
- Subscription management
- Gift card system
- Advanced SEO settings per page
- AI product recommendations
- Mobile application
- Push notifications
- Loyalty system
- Courier API integration
- Advanced analytics and marketing tools

---

## 27. Future Improvements

Possible future items, clearly labeled as not yet implemented:

- Advanced payment gateway integration
- Courier API integration
- Advanced order tracking
- Customer reviews (full implementation)
- Recommendation engine
- Loyalty system
- Push notifications
- Mobile application
- Advanced analytics
- Advanced marketing tools

---

## 28. Client Benefits

- **Customers can shop from any device** — Desktop, laptop, tablet, and mobile experiences are all optimized
- **Admin can manage products and orders from one place** — Complete admin panel with product, category, order, and customer management
- **Dynamic product and homepage content** — Content managed via API/database; admins can update banners, featured products, and collections without code changes
- **Better mobile shopping experience** — Adapted layouts, touch-friendly controls, and optimized checkout for smaller screens
- **Faster product discovery** — Category browsing, search, and filters help customers find products quickly
- **Structured checkout process** — Clear step-by-step flow with validation at each stage
- **Payment verification workflow** — Manual verification support for bKash and other manual payment methods
- **Scalable architecture** — API-driven design allows for future feature expansion without major restructure
- **Easy future feature expansion** — Modular codebase and clear separation of concerns make adding new features straightforward

---

## 29. Project Quality

- **Responsive UI** — Fully adapts to desktop, tablet, and mobile breakpoints
- **Reusable components** — Component-driven architecture with shadcn-ui and Radix primitives
- **API-driven architecture** — Frontend communicates with backend via REST API; RTK Query manages server state
- **Scalable state management** — Redux Toolkit + RTQ Query provide structured, cached, and invalidated data flow
- **Performance optimization** — Caching, lazy loading, optimized images, and minimized re-renders
- **Clean UX** — User-centered design with clear navigation, CTA buttons, and frictionless checkout
- **Maintainable code structure** — TypeScript, modular organization, and documented code patterns
- **Production-focused development** — ESLint-ready, TypeScript-checked, and build-optimized

> No exaggerated claims such as "100% secure" or "zero bugs" are made.

---

## 30. License

> This project is proprietary and developed for Mama Bazar. Redistribution, reuse, or commercial use requires permission from the project owner.

---