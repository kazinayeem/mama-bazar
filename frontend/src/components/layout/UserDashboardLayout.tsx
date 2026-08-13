import { useCallback, useEffect } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { LayoutDashboard, Package, User, MapPin, Shield } from 'lucide-react'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { fetchMyOrders, fetchMyProfile } from '../../store/slices/authSlice'

const navItems = [
  { label: 'Overview', href: '/dashboard/overview', icon: LayoutDashboard },
  { label: 'Orders', href: '/dashboard/orders', icon: Package },
  { label: 'Profile', href: '/dashboard/profile', icon: User },
  { label: 'Addresses', href: '/dashboard/addresses', icon: MapPin },
  { label: 'Security', href: '/dashboard/security', icon: Shield },
]

const UserDashboardLayout = () => {
  const dispatch = useAppDispatch()
  const { user, userOrders } = useAppSelector((state) => state.auth)

  useEffect(() => {
    dispatch(fetchMyProfile())
    dispatch(fetchMyOrders())
  }, [dispatch])

  const getUserInitials = useCallback((name: string) => {
    if (!name) return 'U'
    return name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)
  }, [])

  return (
    <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      {/* Profile Hero */}
      <header className="mb-6 overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-white">
        <div className="flex flex-col gap-6 p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex items-center gap-5">
            <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-2xl font-bold text-emerald-800 ring-4 ring-emerald-100/60 sm:h-20 sm:w-20 sm:text-3xl">
              {getUserInitials(user?.name || '')}
            </div>
            <div>
              <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-emerald-600">Customer Portal</p>
              <h1 className="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                Hi, {user?.name || 'User'}
              </h1>
              <p className="mt-1 max-w-lg text-sm text-slate-500">
                Manage your profile, orders and delivery preferences from one place.
              </p>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
              <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">Orders</p>
              <p className="mt-1 text-xl font-bold text-slate-900">{userOrders.length}</p>
            </div>
            <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
              <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">Phone</p>
              <p className="mt-1 truncate text-sm font-semibold text-slate-900">{user?.phone || '-'}</p>
            </div>
            <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
              <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">Area</p>
              <p className="mt-1 truncate text-sm font-semibold text-slate-900">{user?.shippingArea || 'Not set'}</p>
            </div>
            <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
              <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500">Account</p>
              <p className="mt-1 text-sm font-semibold uppercase text-emerald-600">Active</p>
            </div>
          </div>
        </div>
      </header>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
        {/* Desktop Sidebar */}
        <aside className="hidden rounded-2xl border border-slate-200 bg-white p-3 lg:sticky lg:top-24 lg:block lg:h-fit">
          <nav className="flex flex-col gap-1" aria-label="Dashboard sections">
            {navItems.map((item) => {
              const Icon = item.icon
              return (
                <NavLink
                  key={item.href}
                  to={item.href}
                  className={({ isActive }) =>
                    [
                      'flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                      isActive
                        ? 'bg-slate-900 text-white'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                    ].join(' ')
                  }
                >
                  <Icon size={18} />
                  {item.label}
                </NavLink>
              )
            })}
          </nav>
        </aside>

        {/* Mobile Navigation */}
        <div className="mb-2 overflow-x-auto lg:hidden">
          <nav className="flex gap-2 pb-2" aria-label="Dashboard sections">
            {navItems.map((item) => {
              const Icon = item.icon
              return (
                <NavLink
                  key={item.href}
                  to={item.href}
                  className={({ isActive }) =>
                    [
                      'flex shrink-0 items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition whitespace-nowrap',
                      isActive
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 text-slate-600 hover:border-slate-400 hover:bg-slate-50',
                    ].join(' ')
                  }
                >
                  <Icon size={16} />
                  {item.label}
                </NavLink>
              )
            })}
          </nav>
        </div>

        <section className="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
          <Outlet />
        </section>
      </div>
    </main>
  )
}

export default UserDashboardLayout
