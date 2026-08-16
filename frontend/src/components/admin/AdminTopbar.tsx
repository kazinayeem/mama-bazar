import { useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import {
  Bell,
  PanelLeftClose,
  PanelLeftOpen,
  Search,
  ChevronRight,
  ExternalLink,
  LogOut,
  Settings,
  UserRound,
} from 'lucide-react'
import { useAppDispatch, useAppSelector } from '@/store/hooks'
import { logout } from '@/store/slices/authSlice'

import { useGetAdminDashboardQuery } from '@/store/services/adminProductsApi'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Badge } from '@/components/ui/badge'
import { adminNavSections } from './adminNav'

interface AdminTopbarProps {
  collapsed: boolean
  onToggleCollapse: () => void
  onOpenCommandPalette: () => void
  onOpenMobileSidebar: () => void
}

type TopbarNotification = {
  id: string
  title: string
  desc: string
  href: string
  unread: boolean
}

const AdminTopbar = ({ collapsed, onToggleCollapse, onOpenCommandPalette, onOpenMobileSidebar }: AdminTopbarProps) => {
  const location = useLocation()
  const navigate = useNavigate()
  const dispatch = useAppDispatch()
  const { user } = useAppSelector((state) => state.auth)
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const { data: dashboardData } = useGetAdminDashboardQuery()
  const notifications: TopbarNotification[] = (() => {
    if (!dashboardData) return []
    const list: TopbarNotification[] = []
    const recentPending = dashboardData.recentOrders
      .filter((order) => order.status === 'pending' || order.status === 'payment_pending')
      .slice(0, 5)
    recentPending.forEach((order) =>
      list.push({
        id: `order-${order.id}`,
        title: 'New order received',
        desc: `Order ${order.orderId} by ${order.customerName}`,
        href: `/admin/orders/${order.id}`,
        unread: true,
      }),
    )
    dashboardData.lowStockProducts.slice(0, 3).forEach((product) =>
      list.push({
        id: `stock-${product.id}`,
        title: 'Low stock alert',
        desc: `${product.title.slice(0, 40)} — only ${product.stock} left`,
        href: `/admin/products/${product.id}/edit`,
        unread: true,
      }),
    )
    return list
  })()

  const crumbs = adminNavSections
    .flatMap((s) => s.items)
    .filter((item) => location.pathname.startsWith(item.href))
    .slice(-1)

  const section = adminNavSections.find((s) =>
    s.items.some((item) => location.pathname.startsWith(item.href)),
  )

  return (
    <header className="sticky top-0 z-40 flex h-16 items-center gap-3 border-b bg-background/95 px-4 backdrop-blur sm:px-6">
      <button
        type="button"
        onClick={onOpenMobileSidebar}
        className="rounded-md p-2 text-muted-foreground hover:bg-muted lg:hidden"
        aria-label="Open sidebar"
      >
        <PanelLeftOpen className="h-5 w-5" />
      </button>
      <button
        type="button"
        onClick={onToggleCollapse}
        className="hidden rounded-md p-2 text-muted-foreground hover:bg-muted lg:inline-flex"
        aria-label="Toggle sidebar"
      >
        {collapsed ? <PanelLeftOpen className="h-5 w-5" /> : <PanelLeftClose className="h-5 w-5" />}
      </button>

      <div className="hidden items-center gap-1.5 text-sm md:flex">
        <span className="text-muted-foreground">{section?.label || 'Admin'}</span>
        <ChevronRight className="h-3.5 w-3.5 text-muted-foreground" />
        <span className="font-semibold">{crumbs[0]?.label || 'Dashboard'}</span>
      </div>

      <div className="flex-1" />

      <button
        type="button"
        onClick={onOpenCommandPalette}
        className="hidden items-center gap-2 rounded-lg border bg-muted/40 px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted sm:flex"
      >
        <Search className="h-4 w-4" />
        <span className="hidden lg:inline">Search anything...</span>
        <kbd className="ml-2 rounded border bg-background px-1.5 py-0.5 text-[10px]">⌘K</kbd>
      </button>

    

      <DropdownMenu open={notificationsOpen} onOpenChange={setNotificationsOpen}>
        <DropdownMenuTrigger asChild>
          <button type="button" className="relative rounded-md p-2 text-muted-foreground hover:bg-muted" aria-label="Notifications">
            <Bell className="h-5 w-5" />
            {notifications.length > 0 && <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-destructive" />}
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-80">
          <DropdownMenuLabel className="flex items-center justify-between">
            Notifications
            {notifications.length > 0 && <Badge variant="secondary">{notifications.length} new</Badge>}
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          {notifications.length === 0 ? (
            <div className="px-4 py-6 text-center text-sm text-muted-foreground">No notifications</div>
          ) : (
            notifications.map((n) => (
              <DropdownMenuItem
                key={n.id}
                className="cursor-pointer items-start gap-2 py-2.5"
                onClick={() => {
                  setNotificationsOpen(false)
                  navigate(n.href)
                }}
              >
                <span className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${n.unread ? 'bg-primary' : 'bg-muted-foreground/40'}`} />
                <span className="flex-1">
                  <span className="block text-sm font-medium">{n.title}</span>
                  <span className="block text-xs text-muted-foreground">{n.desc}</span>
                </span>
              </DropdownMenuItem>
            ))
          )}
        </DropdownMenuContent>
      </DropdownMenu>

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <button type="button" className="flex items-center gap-2 rounded-full focus:outline-none">
            <Avatar className="h-8 w-8">
              <AvatarFallback className="bg-primary text-xs font-bold text-primary-foreground">
                {user?.name?.charAt(0)?.toUpperCase() || 'A'}
              </AvatarFallback>
            </Avatar>
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuLabel>
            <div className="flex flex-col">
              <span className="text-sm font-semibold">{user?.name || 'Admin'}</span>
              <span className="text-xs font-normal text-muted-foreground">{user?.phone}</span>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem onClick={() => navigate('/')} className="cursor-pointer">
            <ExternalLink className="mr-2 h-4 w-4" /> View Storefront
          </DropdownMenuItem>
          <DropdownMenuItem className="cursor-pointer">
            <UserRound className="mr-2 h-4 w-4" /> Profile
          </DropdownMenuItem>
          <DropdownMenuItem onClick={() => navigate('/admin/settings')} className="cursor-pointer">
            <Settings className="mr-2 h-4 w-4" /> Settings
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            className="cursor-pointer text-destructive focus:text-destructive"
            onClick={() => dispatch(logout())}
          >
            <LogOut className="mr-2 h-4 w-4" /> Logout
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </header>
  )
}

export default AdminTopbar
