# Mama Bazar — Complete Full-Stack Laravel Application

A standalone, production-ready, full-stack Laravel application replacing the Node.js backend while providing:
1. **REST API**: 100% contract parity with the existing React frontend (`/frontend`).
2. **User-Facing Laravel Blade UI**: Visually and functionally matching the React storefront design (using Blade, Tailwind CSS, Alpine.js).
3. **Dedicated Admin Panel**: Complete management dashboard with full CRUD at `/admin`.
4. **Primary Database**: Pure SQLite (`database/database.sqlite`).
5. **Local File & Image Storage**: High-performance local disk storage under `storage/app/public` exposed via `public/storage`. **Zero Cloudinary or external CDN dependencies**.
6. **Authentication & RBAC**: Customer session & JWT token authentication, dedicated `/admin/login`, role/permission authorization.
7. **Seeded Admin Account**: Seeded through `AdminUserSeeder` reading credentials directly from `.env`.

---

## 🏛️ Application Architecture

```
                       Laravel Application
                                |
        +-----------------------+-----------------------+
        |                       |                       |
    REST API             User Blade UI             Admin Panel
(routes/api.php)        (routes/web.php)        (routes/web.php)
  Api Controllers        Web Controllers       Admin Controllers
        \                       |                      /
         \                      |                     /
          +---------------------+--------------------+
                                |
                       Shared Service Layer
     (ProductService, OrderService, MediaStorageService, etc.)
                                |
                         Eloquent Models
                                |
                 SQLite Database & Local Public Disk
```

Both the **React Frontend** (via `/api/*`) and the **Laravel Blade UI** (via Web Controllers) execute against the **exact same Laravel service layer** and SQLite database.

---

## 🚀 Quick Setup & Local Development

Run the entire application locally with these commands:

```bash
cd backend-laravel

# 1. Install PHP dependencies
composer install

# 2. Install Node dependencies
npm install

# 3. Create SQLite database file
touch database/database.sqlite

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# 5. Run database migrations & seeders (includes admin account & policy pages)
php artisan migrate --seed

# 6. Create storage symlink for public images
php artisan storage:link

# 7. Build frontend assets (Tailwind CSS & Alpine.js)
npm run build

# 8. Start local web server
php artisan serve
```

The application is now running at `http://127.0.0.1:8000`:
- **User Storefront**: `http://127.0.0.1:8000/`
- **Shop Catalog**: `http://127.0.0.1:8000/shop`
- **Cart & Checkout**: `http://127.0.0.1:8000/cart` & `/checkout`
- **Track Order**: `http://127.0.0.1:8000/track`
- **Admin Panel**: `http://127.0.0.1:8000/admin`
- **REST API**: `http://127.0.0.1:8000/api`

---

## 🔐 Seeded Admin Account

The seeded administrator account is automatically created from your `.env` configuration:

```env
ADMIN_NAME="Administrator"
ADMIN_EMAIL="admin@example.com"
ADMIN_PHONE="01700000000"
ADMIN_PASSWORD="ChangeMe123!"
```

To log in:
1. Navigate to `http://127.0.0.1:8000/admin/login`
2. Enter **Email** (`admin@example.com`) or **Phone** (`01700000000`)
3. Enter **Password** (`ChangeMe123!`)
4. Click **Sign In** to access `/admin/dashboard`

---

## 🖼️ Pure Local Image Storage (No Cloudinary)

Cloudinary has been **completely removed**:
- Storage Disk: `Storage::disk('public')`
- Physical location: `storage/app/public/{folder}/`
  - `storage/app/public/products/`
  - `storage/app/public/categories/`
  - `storage/app/public/banners/`
  - `storage/app/public/media/`
  - `storage/app/public/payments/`
- Public URL format: `/storage/{folder}/{filename}`
- Symlink: `public/storage` linked to `storage/app/public` via `php artisan storage:link`
- Replacement & Deletion: Handled automatically by `App\Services\MediaStorageService`.

---

## 🧪 Running Automated Tests

Run the test suite covering both API parity and full-stack Blade/Admin flows:

```bash
php artisan test
```

All 26 feature tests and 141 assertions validate:
- Seeded admin login & authentication middleware
- Blade user pages rendering (`/`, `/shop`, `/cart`, `/checkout`, `/track`, etc.)
- Product detail pages and catalog search
- Checkout and atomic order placement into SQLite
- Local image storage upload and deletion
- React REST API contract parity

---

## 📱 Connecting the React Frontend

The existing React frontend (`/frontend`) can immediately communicate with this Laravel backend:
1. Update `frontend/.env`:
   ```env
   VITE_API_URL=http://localhost:8000
   ```
2. Run `npm run dev` in `frontend/`.
3. The React app will fetch categories, products, and process orders against the Laravel SQLite database seamlessly.
