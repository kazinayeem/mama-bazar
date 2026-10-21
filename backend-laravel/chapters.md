# Mama Bazar — Complete Product Walkthrough Video Chapters

## Total Duration: ~45–60 Minutes (Full Demonstration)
**Walkthrough Video Format:** Animated WebP Video + Interactive HTML5 Player (`http://127.0.0.1:8000/walkthrough.html`)

---

### Part 1: Customer-Facing Storefront (Frontend)

- **00:00 – 03:30 | Chapter 1: Introduction & Product Overview**
  - Brand identity, multi-category e-commerce platform overview, responsive architecture.
  - Verification of fixed product grid CSS layout across desktop and mobile.

- **03:30 – 08:15 | Chapter 2: Homepage Experience & Navigation**
  - Top promotion announcement bar with dynamic notice.
  - Multi-slide hero carousel with SSR support.
  - Popular search tags pill bar.
  - Trust strip: Fast nationwide delivery, official warranty, easy returns, 24/7 support.
  - Dynamic homepage sections: Flash Deals, New Arrivals, Best Sellers, Category Showcases.
  - Customer review wall with verified purchase ratings.
  - Comprehensive footer navigation, helpline, social channels, and payment badges.

- **08:15 – 15:00 | Chapter 3: Product Discovery & Catalog Filtering**
  - Full catalog navigation at `/shop`.
  - Multi-category accordion filtering (Lighting, Combo Packages, Gadgets & Electronics).
  - Price range slider and real-time sorting (Newest, Price Low-to-High, Price High-to-Low).
  - Search auto-complete and keyword query routing.
  - Product card components: Discount badge, flash indicators, out-of-stock badges, and quick add buttons.

- **15:00 – 22:30 | Chapter 4: Product Detail Page & Option Selection**
  - High-resolution multi-angle image gallery.
  - Localized bilingual (Bengali / English) titles and descriptions.
  - Live inventory indicators ("Only 2 left" low-stock urgency alerts).
  - Dynamic pricing (regular vs. sale price calculation).
  - Quantity selection increments/decrements.
  - One-click "Add to Cart" and "Buy Now" workflows.

- **22:30 – 28:00 | Chapter 5: Shopping Cart & Checkout Process**
  - Sliding cart drawer and dedicated cart page at `/cart`.
  - Order subtotal calculation and line item removal.
  - Streamlined one-page checkout at `/checkout`.
  - Customer delivery details form (Name, Phone, Delivery Address, District/City).
  - Cash on Delivery (COD) and SSLCommerz payment options.
  - Order review, shipping fee calculation, and order submission.

- **28:00 – 32:00 | Chapter 6: Post-Purchase Experience & Order Tracking**
  - Order confirmation screen with unique tracking ID.
  - Customer order tracking tool at `/track` (lookup by phone or order number).
  - Informational pages: About Us (`/about`), Contact Us (`/contact`), FAQ (`/faq`).

---

### Part 2: Administrator Control Panel (Admin)

- **32:00 – 37:30 | Chapter 7: Admin Dashboard & Real-Time Analytics**
  - Secure role-based staff authentication at `/admin/login`.
  - Executive overview: gross sales, total orders, active customer accounts.
  - Revenue trend graphs and order status breakdown charts.

- **37:30 – 44:00 | Chapter 8: Product Catalog Management**
  - Product inventory table at `/admin/products`.
  - Status management (Published, Draft, Out of Stock, Featured toggle).
  - Complete product creation form at `/admin/products/create`:
    - Title, auto-slug generator, short highlights.
    - Full rich text Quill editor with sanitization.
    - Category, Sub-category, and Brand assignment.
    - Pricing, promotional discount price, SKU, and stock thresholds.

- **44:00 – 48:30 | Chapter 9: Categories, Brands & Promotions**
  - Category tree hierarchy and image thumbnails (`/admin/categories`).
  - Brand association management (`/admin/brands`).
  - Coupon management at `/admin/coupons`: discount percentage, fixed discounts, min spend, expiry date.

- **48:30 – 53:00 | Chapter 10: Order Processing & Customer Management**
  - Orders queue at `/admin/orders`: filtering by Pending, Processing, Shipped, Delivered, Cancelled.
  - Customer directory at `/admin/customers`: contact info, order histories, lifetime value.
  - Shipping rate configuration at `/admin/shipping` (inside vs. outside Dhaka).

- **53:00 – 58:00 | Chapter 11: System Administration & Governance**
  - Staff role assignment and granular permissions (`/admin/members`).
  - Audit logging and login security tracking (`/admin/activity-monitor`).
  - Database backup and recovery snapshot tools (`/admin/backup`).
  - Advanced analytical reports with CSV & PDF export (`/admin/advanced-analytics`).

- **58:00 – 60:00 | Chapter 12: Mobile Experience & Wrap-Up**
  - Mobile responsive drawer navigation, touch-friendly product tiles, and thumb-friendly checkout.
