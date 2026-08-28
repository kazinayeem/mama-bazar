import { createContext, useContext, useEffect, useState } from 'react'
import { Outlet } from 'react-router-dom'
import Sidebar from '../admin/Sidebar'
import AdminTopbar from '../admin/AdminTopbar'
import CommandPalette from '../admin/CommandPalette'
import { useThemeSync } from '../../lib/useThemeSync'
import { cn } from '@/lib/utils'
import {
  useGetCategoriesQuery,
  useGetBrandsQuery,
  useGetCollectionsQuery,
} from '@/store/services/commerceApi'
import {
  useGetAdminColorsQuery,
  useGetAdminSizesQuery,
  useGetAdminSuppliersQuery,
  useGetAdminVendorsQuery,
} from '@/store/services/adminProductsApi'

const AdminLayoutContext = createContext<boolean>(false)

const AdminLayout = ({ children }: { children?: React.ReactNode }) => {
  useThemeSync()

  // Retain active cache subscriptions for shared master reference data throughout the admin session.
  // This guarantees Categories, Brands, Collections, Colors, Sizes, Vendors, and Suppliers
  // are loaded ONCE and reused everywhere without duplicate requests or waterfall latency.
  useGetCategoriesQuery(undefined, { refetchOnMountOrArgChange: false, refetchOnFocus: false, refetchOnReconnect: false })
  useGetBrandsQuery(undefined, { refetchOnMountOrArgChange: false, refetchOnFocus: false, refetchOnReconnect: false })
  useGetCollectionsQuery(undefined, { refetchOnMountOrArgChange: false, refetchOnFocus: false, refetchOnReconnect: false })
  useGetAdminVendorsQuery(undefined, { refetchOnMountOrArgChange: false, refetchOnFocus: false, refetchOnReconnect: false })
  useGetAdminSuppliersQuery(undefined, { refetchOnMountOrArgChange: false, refetchOnFocus: false, refetchOnReconnect: false })
  useGetAdminColorsQuery(undefined, { refetchOnMountOrArgChange: false, refetchOnFocus: false, refetchOnReconnect: false })
  useGetAdminSizesQuery(undefined, { refetchOnMountOrArgChange: false, refetchOnFocus: false, refetchOnReconnect: false })

  const isInsideAdminLayout = useContext(AdminLayoutContext)

  // Persistent sidebar collapsed preference
  const [collapsed, setCollapsed] = useState(() => {
    try {
      return localStorage.getItem('mamabazar:admin_sidebar_collapsed') === 'true'
    } catch {
      return false
    }
  })

  const [mobileOpen, setMobileOpen] = useState(false)
  const [commandOpen, setCommandOpen] = useState(false)

  const toggleCollapsed = () => {
    setCollapsed((prev) => {
      const next = !prev
      try {
        localStorage.setItem('mamabazar:admin_sidebar_collapsed', String(next))
      } catch {
        // ignore
      }
      return next
    })
  }

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault()
        setCommandOpen((v) => !v)
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  // If this component is already rendered inside an active AdminLayout shell,
  // pass through children/Outlet directly to avoid nested duplicate sidebars.
  if (isInsideAdminLayout) {
    return <>{children ?? <Outlet />}</>
  }

  return (
    <AdminLayoutContext.Provider value={true}>
      <div className="flex h-screen overflow-hidden bg-background">
        {/* Desktop sidebar — stays mounted across all admin route changes */}
        <aside
          className={cn(
            'hidden shrink-0 border-r bg-card transition-all duration-200 lg:block',
            collapsed ? 'w-16' : 'w-60',
          )}
        >
          <Sidebar collapsed={collapsed} />
        </aside>

        {/* Mobile sidebar drawer */}
        {mobileOpen && (
          <div className="fixed inset-0 z-[200] lg:hidden">
            <div className="absolute inset-0 bg-black/60" onClick={() => setMobileOpen(false)} />
            <aside className="absolute left-0 top-0 h-full w-64 border-r bg-card shadow-xl">
              <Sidebar collapsed={false} onNavigate={() => setMobileOpen(false)} />
            </aside>
          </div>
        )}

        <div className="flex min-w-0 flex-1 flex-col">
          <AdminTopbar
            collapsed={collapsed}
            onToggleCollapse={toggleCollapsed}
            onOpenCommandPalette={() => setCommandOpen(true)}
            onOpenMobileSidebar={() => setMobileOpen(true)}
          />
          <main className="flex-1 overflow-y-auto p-4 sm:p-6">
            {children ?? <Outlet />}
          </main>
        </div>

        <CommandPalette open={commandOpen} onOpenChange={setCommandOpen} />
      </div>
    </AdminLayoutContext.Provider>
  )
}

export default AdminLayout
