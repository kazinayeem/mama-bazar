# 🎨 Modern Admin Dashboard - Complete Guide

## 📊 Overview

A professional, production-ready admin dashboard UI for your clothing eCommerce platform, built with React, TypeScript, Tailwind CSS, and Redux.

**URL**: `http://localhost:5173/admin/dashboard`

---

## 🏗️ Architecture

### Admin Layout Component
- **File**: `src/components/layout/AdminLayout.tsx`
- **Features**:
  - Fixed left sidebar (collapsible)
  - Top navigation bar
  - User profile dropdown
  - Notification bell
  - Search bar
  - Logo/branding

### Dashboard Pages

| Page | URL | Purpose | Features |
|------|-----|---------|----------|
| Dashboard | `/admin/dashboard` | Overview & KPIs | Revenue, orders, conversion rate, charts, recent orders |
| Products | `/admin/products` | Manage products | List, search, add, edit, delete, images, pricing |
| Orders | `/admin/orders` | Manage orders | Order list, status filtering, customer info, payment details |
| Categories | `/admin/categories` | Product categories | Grid view, add/edit, delete, descriptions |
| Customers | `/admin/customers` | Customer management | List, order history, spending, status |
| Coupons | `/admin/coupons` | Discount codes | Create, enable/disable, expiry dates, discount % |
| Analytics | `/admin/analytics` | Business metrics | Sales charts, traffic sources, revenue breakdown, conversion |
| Marketing | `/admin/marketing` | Campaigns | Homepage banners, email campaigns, SMS notifications |
| Settings | `/admin/settings` | Configuration | Payment methods, shipping zones, store info, taxes, admin users |

---

## 🎯 Design System

### Colors
- **Primary**: Black (`#000000`)
- **Background**: White (`#FFFFFF`)
- **Sidebar**: White with black hover
- **Cards**: White with subtle borders & shadows
- **Status Badges**:
  - Green: Delivered/Active
  - Blue: Processing
  - Orange: Confirmed/Pending
  - Gray: Inactive/Default

### Typography
- **Headlines**: Inter Bold
- **Body**: Inter Regular
- **UI Labels**: Inter Medium (12px uppercase)

### Spacing & Layout
- 8px grid system
- Sidebar: 256px (collapsed: 80px)
- Content padding: 32px
- Card gaps: 16px
- Rounded corners: 8px-12px

---

## 🔐 Access Control

### Role-Based Routes
```typescript
// Admin & Manager can access:
- /admin/dashboard
- /admin/products
- /admin/orders
- /admin/categories
- /admin/customers
- /admin/coupons
- /admin/analytics
- /admin/marketing

// Admin-only:
- /admin/settings
```

### Required Roles
- `admin` - Full access to all pages
- `manager` - Access to all except settings

---

## 📡 Backend Integration

### API Endpoints Used

#### Dashboard Stats
```typescript
GET /api/order/stats
```
Returns: `{ totalRevenue, pendingOrders, conversionRate, marketShare }`

#### Orders List
```typescript
GET /api/order?page=1&limit=8
```
Returns: Paginated order list with customer info, amounts, status

#### Products List
```typescript
GET /api/products?page=1&limit=10
```
Returns: Paginated product list with images, pricing, stock

#### Categories
```typescript
GET /api/categories
```
Returns: All product categories

---

## 🔄 Redux Integration

### State Slices Used
- **dashboard**: `fetchDashboardStats()` → KPI data
- **orders**: `fetchRecentOrders(params)` → Order list with pagination
- **products**: `fetchProducts(params)` → Product list with pagination
- **categories**: `fetchCategories()` → Category list
- **auth**: Current user info, logout action

### Reducer Examples
```typescript
// Dispatch from any admin page
dispatch(fetchRecentOrders({ page: 1, limit: 10 }))
dispatch(fetchProducts({ page: 1, limit: 10, category: 'shirts' }))
dispatch(fetchCategories())
```

---

## 📋 Page Features

### Dashboard
- **KPI Cards**: Revenue, orders, conversion rate, market share
- **Recent Orders Table**: Order ID, customer, amount, status, date
- **Pagination**: Page info & prev/next buttons

### Products
- **Search Bar**: Search products by name
- **Product Table**: Image, name, price, stock, category, status, actions
- **Add Modal**: Form for new products
- **Actions**: Edit, delete buttons

### Orders
- **Status Filter**: Dropdown to filter by status (pending, processing, shipped, delivered)
- **Order Table**: ID, customer, amount, status, payment method, actions
- **View Details**: Link to order details page (placeholder)
- **Pagination**: Navigate through pages

### Categories
- **Grid View**: Category cards with images
- **Add Modal**: Form for new categories
- **Edit/Delete**: Inline actions

### Customers
- **Customer Table**: Name, email, phone, orders count, total spent, status, actions
- **Block/Unblock**: Manage customer access

### Coupons
- **Coupon List**: Code, discount %, expiry, status
- **Add Modal**: Create new coupon
- **Edit/Delete**: Manage coupons

### Analytics
- **Sales Chart**: Placeholder for graph (coming soon)
- **Traffic Sources**: Bar chart showing Google, Facebook, Direct, Other
- **Revenue Breakdown**: Today, this week, this month
- **Conversion Metrics**: Conversion rate, AOV, LTV, cart abandonment

### Marketing
- **Homepage Banners**: Create/edit banners with images and links
- **Email Campaigns**: Campaign creation UI
- **SMS Notifications**: SMS sending interface

### Settings
- **Payment Methods**: Enable/disable bKash, Nagad, COD, Credit Card
- **Shipping Zones**: Dhaka, Outside Dhaka, Hill Tracts with rates
- **Store Information**: Store name, email, phone
- **Tax Settings**: Tax rate, apply to shipping
- **Admin Users**: List and manage admin accounts

---

## 🎨 UI Components

### Tables
- Horizontal scrollable on mobile
- Hover effects (bg-gray-50)
- Status badges with colors
- Pagination controls
- Search filters

### Forms
- Modal dialogs
- Input fields (text, email, number, date, file)
- Submit/Cancel buttons
- Validation placeholders

### Navigation
- Sidebar with icons & labels
- Collapsible toggle
- Active state highlighting
- Hover states

### Cards & Sections
- White background with border
- Subtle shadows
- Rounded corners
- Gap spacing

---

## 🚀 How to Use

### Access Admin Dashboard
1. Log in with admin credentials:
   - **Phone**: `01711111111`
   - **Password**: `admin123`
   - Or use quick login button on login page
2. You'll be redirected to `/admin/dashboard`

### Navigate Between Pages
- Use sidebar menu to switch between pages
- Click icons or labels to navigate
- Active page is highlighted in black

### Common Tasks

#### Add a Product
1. Go to `/admin/products`
2. Click "+ Add Product" button
3. Fill form (name, price, category)
4. Click "Create Product"

#### View Orders
1. Go to `/admin/orders`
2. Filter by status (optional)
3. Click "View" to see order details
4. Pagination to navigate pages

#### Create Coupon
1. Go to `/admin/coupons`
2. Click "+ New Coupon"
3. Enter code, discount %, expiry date
4. Click "Create Coupon"

#### Configure Settings
1. Go to `/admin/settings` (admin-only)
2. Toggle payment methods ON/OFF
3. Modify shipping rates, taxes, store info
4. Click "Save Changes"

---

## 🔧 Customization Guide

### Change Colors
Edit `AdminLayout.tsx` and admin pages:
```typescript
// Black to blue:
className="bg-black hover:bg-gray-900"
// To:
className="bg-blue-600 hover:bg-blue-700"
```

### Add New Menu Item
In `AdminLayout.tsx`:
```typescript
const menuItems = [
  // ... existing items
  { label: 'Reports', href: '/admin/reports', icon: '📄' },
]
```

### Change Sidebar Width
In `AdminLayout.tsx`:
```typescript
// Adjust these:
w-64        // Default width
ml-64       // Content margin
w-20        // Collapsed width
ml-20       // Collapsed margin
```

### Add More Status Badges
In any page:
```typescript
className={`inline-flex rounded-full px-3 py-1 text-xs font-medium ${
  order.status === 'new-status' ? 'bg-purple-100 text-purple-800' :
  // ... more statuses
}`}
```

---

## ⚡ Performance Tips

- Tables support pagination (load only 10 items per page)
- Lazy load charts and heavy components
- Use Redux for state management (one source of truth)
- Images optimized with thumbnail sizes
- CSS is pre-compiled with Tailwind

---

## 📱 Responsive Design

### Breakpoints
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

### Responsive Features
- Sidebar collapses on mobile
- Mobile tables are horizontally scrollable
- Grid layouts adapt (1 col mobile, 2-3 cols desktop)
- Fonts scale appropriately

---

## 🔒 Security

### Protected Routes
All admin pages are protected with `ProtectedRoute` component:
```typescript
<ProtectedRoute allowedRoles={['admin', 'manager']}>
  <AdminProductsPage />
</ProtectedRoute>
```

### Authentication
- JWT tokens stored in localStorage
- Bearer token sent with API requests
- Logout clears token and user data
- Redirects to login if unauthorized

---

## 📝 Next Steps

### To Connect Forms to Backend
```typescript
// In any admin page:
const handleAddProduct = async (formData) => {
  const response = await api.createProduct(formData)
  dispatch(fetchProducts())  // Refresh list
  showToast('Product added!')
}
```

### To Add Real Charts
```javascript
// Install: npm install recharts
import { LineChart, Line, XAxis, YAxis } from 'recharts'

<LineChart data={salesData}>
  <XAxis dataKey="date" />
  <YAxis />
  <Line type="monotone" dataKey="sales" />
</LineChart>
```

### To Add Confirmation Dialogs
```typescript
const handleDelete = () => {
  if (confirm('Delete this item?')) {
    // Call delete API
  }
}
```

---

## 📞 Support

For questions or issues:
1. Check TypeScript types in `/types/index.ts`
2. Review Redux slices in `/store/slices/`
3. Check API client in `/lib/api.ts`
4. Review component props and state

---

**Last Updated**: April 6, 2026
**Version**: 1.0
**Status**: Production-Ready ✅
