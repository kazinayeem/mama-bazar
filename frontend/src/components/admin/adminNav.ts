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
} from 'lucide-react'

export interface AdminNavItem {
  label: string
  href: string
  icon: LucideIcon
  badge?: number
}

export interface AdminNavSection {
  label: string
  items: AdminNavItem[]
}

export const adminNavSections: AdminNavSection[] = [
  {
    label: 'Overview',
    items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard }],
  },
  {
    label: 'Catalog',
    items: [
      { label: 'Products', href: '/admin/products', icon: Package },
      { label: 'Categories', href: '/admin/categories', icon: Tags },
      { label: 'Brands', href: '/admin/brands', icon: Stamp },
      { label: 'Collections', href: '/admin/collections', icon: FolderOpen },
      { label: 'Colors', href: '/admin/colors', icon: Palette },
      { label: 'Sizes', href: '/admin/sizes', icon: Ruler },
      { label: 'Vendors', href: '/admin/vendors', icon: Store },
      { label: 'Suppliers', href: '/admin/suppliers', icon: Truck },
    ],
  },
  {
    label: 'Sales',
    items: [
      { label: 'Orders', href: '/admin/orders', icon: ShoppingCart },
      { label: 'Coupons', href: '/admin/coupons', icon: TicketPercent },
      { label: 'Marketing', href: '/admin/marketing', icon: Megaphone },
    ],
  },
  {
    label: 'Checkout',
    items: [
      { label: 'Shipping Methods', href: '/admin/shipping', icon: MapPin },
      { label: 'Payment Methods', href: '/admin/payment-methods', icon: CreditCard },
      { label: 'Checkout Notices', href: '/admin/checkout-notices', icon: BellRing },
    ],
  },
  {
    label: 'Customers',
    items: [{ label: 'Customers', href: '/admin/customers', icon: Users }],
  },
  {
    label: 'Content',
    items: [
      { label: 'Homepage Builder', href: '/admin/homepage', icon: PanelsTopLeft },
      { label: 'Policies & Messages', href: '/admin/policies', icon: FileText },
      { label: 'Media Library', href: '/admin/media', icon: Image },
      { label: 'Banners', href: '/admin/banners', icon: Megaphone },
    ],
  },
  {
    label: 'Insights',
    items: [{ label: 'Analytics', href: '/admin/analytics', icon: BarChart3 }],
  },
  {
    label: 'System',
    items: [
      { label: 'Inventory', href: '/admin/inventory', icon: Boxes },
      { label: 'Settings', href: '/admin/settings', icon: Settings },
    ],
  },
]

export const allAdminNavItems = adminNavSections.flatMap((section) =>
  section.items.map((item) => ({ ...item, section: section.label })),
)
