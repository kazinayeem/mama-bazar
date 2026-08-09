import { Link } from 'react-router-dom'
import { currency } from '../../lib/format'
import { useAppSelector } from '../../store/hooks'
import { formatOrderStatus, getOrderStatusBadge } from './dashboardUtils'
import { SEO } from '../../components/common/SEO'

const DashboardOverviewPage = () => {
  const { userOrders, ordersLoading } = useAppSelector((state) => state.auth)

  const totalSpend = userOrders.reduce((acc, order) => acc + Number(order.totalPrice || 0), 0)
  const activeOrders = userOrders.filter((order) => !['delivered', 'cancelled'].includes(order.status)).length
  const deliveredOrders = userOrders.filter((order) => order.status === 'delivered').length
  const latestOrders = [...userOrders].slice(0, 3)

  return (
    <div>
      <SEO title="My Dashboard" description="View your account overview, recent orders, and account information." url="/dashboard/overview" />
      <header className="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Overview</p>
          <h2 className="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Dashboard Summary</h2>
        </div>
        <Link
          className="rounded-lg border border-slate-300 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700 hover:bg-slate-50"
          to="/shop"
        >
          Continue Shopping
        </Link>
      </header>

      <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article className="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p className="text-xs font-semibold text-slate-500">Total Orders</p>
          <p className="mt-2 text-2xl font-black text-slate-900">{userOrders.length}</p>
        </article>
        <article className="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p className="text-xs font-semibold text-slate-500">Active Orders</p>
          <p className="mt-2 text-2xl font-black text-slate-900">{activeOrders}</p>
        </article>
        <article className="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p className="text-xs font-semibold text-slate-500">Delivered</p>
          <p className="mt-2 text-2xl font-black text-slate-900">{deliveredOrders}</p>
        </article>
        <article className="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p className="text-xs font-semibold text-slate-500">Total Spend</p>
          <p className="mt-2 text-2xl font-black text-slate-900">{currency(totalSpend.toFixed(2))}</p>
        </article>
      </section>

      <section className="mt-8">
        <div className="mb-3 flex items-center justify-between">
          <h3 className="text-lg font-bold text-slate-900">Recent Orders</h3>
          <Link className="text-sm font-semibold text-indigo-700 hover:underline" to="/dashboard/orders">
            View all
          </Link>
        </div>

        {ordersLoading ? (
          <p className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Loading recent orders...</p>
        ) : latestOrders.length === 0 ? (
          <p className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">No orders yet. Place your first order from Shop.</p>
        ) : (
          <div className="space-y-3">
            {latestOrders.map((order) => (
              <article className="rounded-xl border border-slate-200 p-4" key={order.id}>
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className="font-bold text-slate-900">Order #{order.orderId}</p>
                  <span className={`inline-flex rounded-full border px-3 py-1 text-xs font-bold ${getOrderStatusBadge(order.status)}`}>
                    {formatOrderStatus(order.status)}
                  </span>
                </div>
                <div className="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-600 sm:grid-cols-3">
                  <p>Total: {currency(order.totalPrice)}</p>
                  <p>Payment: {order.paymentMethod.toUpperCase()}</p>
                  <p>Date: {new Date(order.createdAt).toLocaleDateString()}</p>
                </div>
              </article>
            ))}
          </div>
        )}
      </section>
    </div>
  )
}

export default DashboardOverviewPage
