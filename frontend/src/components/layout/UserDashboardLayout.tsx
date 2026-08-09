import { useEffect } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { fetchMyOrders, fetchMyProfile } from '../../store/slices/authSlice'

const navItems = [
  { label: 'Overview', href: '/dashboard/overview' },
  { label: 'Orders', href: '/dashboard/orders' },
  { label: 'Profile', href: '/dashboard/profile' },
  { label: 'Addresses', href: '/dashboard/addresses' },
  { label: 'Security', href: '/dashboard/security' },
]

const UserDashboardLayout = () => {
  const dispatch = useAppDispatch()
  const { user, userOrders } = useAppSelector((state) => state.auth)

  useEffect(() => {
    dispatch(fetchMyProfile())
    dispatch(fetchMyOrders())
  }, [dispatch])

  return (
    <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <header className="mb-6 rounded-2xl border border-outline-variant/20 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 p-5 text-white sm:p-8">
        <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-200">Customer Portal</p>
        <h1 className="mt-2 text-2xl font-extrabold tracking-tight sm:text-4xl">Hi, {user?.name || 'User'}</h1>
        <p className="mt-2 max-w-2xl text-sm text-slate-200">
          Clean account management, quick order tracking, and a faster checkout flow from saved settings.
        </p>

        <div className="mt-5 grid grid-cols-2 gap-3 sm:max-w-xl sm:grid-cols-4">
          <div className="rounded-xl border border-white/20 bg-white/10 p-3">
            <p className="text-[10px] uppercase tracking-[0.15em] text-slate-300">Orders</p>
            <p className="mt-1 text-xl font-bold">{userOrders.length}</p>
          </div>
          <div className="rounded-xl border border-white/20 bg-white/10 p-3">
            <p className="text-[10px] uppercase tracking-[0.15em] text-slate-300">Phone</p>
            <p className="mt-1 truncate text-sm font-semibold">{user?.phone || '-'}</p>
          </div>
          <div className="rounded-xl border border-white/20 bg-white/10 p-3">
            <p className="text-[10px] uppercase tracking-[0.15em] text-slate-300">Area</p>
            <p className="mt-1 truncate text-sm font-semibold">{user?.shippingArea || 'Not set'}</p>
          </div>
          <div className="rounded-xl border border-white/20 bg-white/10 p-3">
            <p className="text-[10px] uppercase tracking-[0.15em] text-slate-300">Role</p>
            <p className="mt-1 text-sm font-semibold uppercase">{user?.role || 'user'}</p>
          </div>
        </div>
      </header>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside className="rounded-2xl border border-outline-variant/20 bg-white p-3 lg:sticky lg:top-24 lg:h-fit">
          <nav className="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible" aria-label="Dashboard sections">
            {navItems.map((item) => (
              <NavLink
                key={item.href}
                to={item.href}
                className={({ isActive }) =>
                  [
                    'rounded-xl border px-4 py-2 text-sm font-semibold transition whitespace-nowrap',
                    isActive
                      ? 'border-slate-900 bg-slate-900 text-white'
                      : 'border-outline-variant/30 text-slate-700 hover:border-slate-400 hover:bg-slate-50',
                  ].join(' ')
                }
              >
                {item.label}
              </NavLink>
            ))}
          </nav>
        </aside>

        <section className="min-w-0 rounded-2xl border border-outline-variant/20 bg-white p-4 sm:p-6">
          <Outlet />
        </section>
      </div>
    </main>
  )
}

export default UserDashboardLayout
