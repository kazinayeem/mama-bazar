import type { LucideIcon } from 'lucide-react'
import {
  LayoutDashboard,
  Package,
  Tags,
  Stamp,
  ShoppingCart,
  TicketPercent,
  Users,
  Image,
  BarChart3,
  Megaphone,
  Settings,
  Boxes,
  FolderOpen,
  Palette,
  Ruler,
  Store,
  Truck,
  CreditCard,
  BellRing,
  MapPin,
  PanelsTopLeft,
  FileText,
  Wallet,
  ReceiptText,
  ListOrdered,
  ChartPie,
  UserCheck,
  DatabaseBackup,
} from 'lucide-react'

export interface AdminNavItem {
  label: string
  href: string
  icon: LucideIcon
  permission?: string
  badge?: number
}

export interface AdminNavSection {
  label: string
  permission?: string
  items: AdminNavItem[]
}

export const adminNavSections: AdminNavSection[] = [
  {
    label: 'Overview',
    items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard, permission: 'dashboard.view' }],
  },
  {
    label: 'Catalog',
    items: [
      { label: 'Products', href: '/admin/products', icon: Package, permission: 'products.view' },
      { label: 'Categories', href: '/admin/categories', icon: Tags, permission: 'categories.view' },
      { label: 'Brands', href: '/admin/brands', icon: Stamp, permission: 'brands.view' },
      { label: 'Collections', href: '/admin/collections', icon: FolderOpen, permission: 'collections.view' },
      { label: 'Colors', href: '/admin/colors', icon: Palette, permission: 'colors.view' },
      { label: 'Sizes', href: '/admin/sizes', icon: Ruler, permission: 'sizes.view' },
      { label: 'Vendors', href: '/admin/vendors', icon: Store, permission: 'vendors.view' },
      { label: 'Suppliers', href: '/admin/suppliers', icon: Truck, permission: 'suppliers.view' },
    ],
  },
  {
    label: 'Sales',
    items: [
      { label: 'Orders', href: '/admin/orders', icon: ShoppingCart, permission: 'orders.view' },
      { label: 'Coupons', href: '/admin/coupons', icon: TicketPercent, permission: 'coupons.view' },
      { label: 'Marketing', href: '/admin/marketing', icon: Megaphone, permission: 'marketing.view' },
    ],
  },
  {
    label: 'Finance',
    items: [
      { label: 'Expenses', href: '/admin/expenses', icon: ReceiptText, permission: 'expenses.view' },
      { label: 'Expense Categories', href: '/admin/expenses/categories', icon: ListOrdered, permission: 'expenses.view' },
      { label: 'Expense Reports', href: '/admin/expenses/reports', icon: ChartPie, permission: 'reports.view' },
      { label: 'Profit Overview', href: '/admin/expenses/reports?tab=profit', icon: Wallet, permission: 'reports.view' },
    ],
  },
  {
    label: 'Checkout',
    items: [
      { label: 'Shipping Methods', href: '/admin/shipping', icon: MapPin, permission: 'shipping.view' },
      { label: 'Payment Methods', href: '/admin/payment-methods', icon: CreditCard, permission: 'payment_methods.view' },
      { label: 'Checkout Notices', href: '/admin/checkout-notices', icon: BellRing, permission: 'checkout_notices.view' },
    ],
  },
  {
    label: 'Customers',
    items: [{ label: 'Customers', href: '/admin/customers', icon: Users, permission: 'customers.view' }],
  },
  {
    label: 'Content',
    items: [
      { label: 'Homepage Builder', href: '/admin/homepage', icon: PanelsTopLeft, permission: 'homepage.view' },
      { label: 'Policies & Messages', href: '/admin/policies', icon: FileText, permission: 'policies.view' },
      { label: 'Media Library', href: '/admin/media', icon: Image, permission: 'media.view' },
      { label: 'Banners', href: '/admin/banners', icon: Megaphone, permission: 'banners.view' },
    ],
  },
  {
    label: 'Insights',
    items: [{ label: 'Analytics', href: '/admin/analytics', icon: BarChart3, permission: 'analytics.view' }],
  },
  {
    label: 'Security & Access',
    items: [
      { label: 'Team Members', href: '/admin/members', icon: UserCheck, permission: 'members.view' },
      { label: 'Backup & Restore', href: '/admin/backup', icon: DatabaseBackup, permission: 'backup.view' },
    ],
  },
  {
    label: 'System',
    items: [
      { label: 'Inventory', href: '/admin/inventory', icon: Boxes, permission: 'inventory.view' },
      { label: 'Settings', href: '/admin/settings', icon: Settings, permission: 'settings.manage' },
    ],
  },
]

export const allAdminNavItems = adminNavSections.flatMap((section) =>
  section.items.map((item) => ({ ...item, section: section.label })),
)
