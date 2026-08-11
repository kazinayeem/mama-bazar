import { useMemo, useState } from 'react'
import LoadingBlock from '../../components/common/LoadingBlock'
import PaginationControls from '../../components/common/PaginationControls'
import { currency } from '../../lib/format'
import { useAppDispatch, useAppSelector } from '../../store/hooks'
import { fetchMyOrders } from '../../store/slices/authSlice'
import type { UserOrderWithItems } from '../../types'
import { formatOrderStatus, getOrderStatusBadge } from './dashboardUtils'
import { SEO } from '../../components/common/SEO'

const PER_PAGE = 5

const getOrderSummary = (userOrders: UserOrderWithItems[]) => {
  const totalSpend = userOrders.reduce((total, order) => total + Number(order.totalPrice || 0), 0)
  const activeOrders = userOrders.filter((order) => !['delivered', 'cancelled'].includes(order.status)).length
  const deliveredOrders = userOrders.filter((order) => order.status === 'delivered').length
  const pendingOrders = userOrders.filter((order) => order.status === 'pending').length

  return {
    totalSpend,
    activeOrders,
    deliveredOrders,
    pendingOrders,
  }
}

const DashboardOrdersPage = () => {
  const dispatch = useAppDispatch()
  const { userOrders, ordersLoading } = useAppSelector((state) => state.auth)
  const [page, setPage] = useState(1)

  const totalPages = Math.max(1, Math.ceil(userOrders.length / PER_PAGE))
  const summary = getOrderSummary(userOrders)

  const paginatedOrders = useMemo(() => {
    const startIndex = (page - 1) * PER_PAGE
    return userOrders.slice(startIndex, startIndex + PER_PAGE)
  }, [page, userOrders])

  const refreshOrders = () => {
    dispatch(fetchMyOrders({ force: true }))
  }

  return (
    <div className="relative overflow-hidden rounded-3xl border border-amber-100 bg-gradient-to-b from-[#fcfaf5] via-white to-[#f8f3ea] p-4 sm:p-6 lg:p-8">
      <SEO title="My Orders" description="View and track your order history and status." url="/dashboard/orders" />
      <div className="pointer-events-none absolute inset-x-0 top-0 h-32 bg-[radial-gradient(circle_at_top_right,_rgba(180,138,75,0.12),_transparent_45%),radial-gradient(circle_at_top_left,_rgba(17,24,39,0.05),_transparent_35%)]" />

      <header className="relative mb-6 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-end">
        <div>
          <p className="text-[10px] font-bold uppercase tracking-[0.32em] text-amber-700">Orders</p>
          <h2 className="mt-2 font-headline text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Order Ledger</h2>
          <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
            A refined view of every purchase, with clear status progression, delivery notes, and a classic paper-like presentation.
          </p>

          <div className="mt-4 flex flex-wrap gap-2">
            <span className="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900">
              {userOrders.length} total orders
            </span>
            <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
              {summary.activeOrders} active
            </span>
            <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
              {summary.deliveredOrders} delivered
            </span>
            <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
              {summary.pendingOrders} pending
            </span>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3 rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur">
          <div className="rounded-xl border border-slate-100 bg-slate-50/80 p-3">
            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Spend</p>
            <p className="mt-2 text-xl font-black text-slate-950">{currency(summary.totalSpend.toFixed(2))}</p>
          </div>
          <div className="rounded-xl border border-slate-100 bg-slate-50/80 p-3">
            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Pagination</p>
            <p className="mt-2 text-xl font-black text-slate-950">{page}/{totalPages}</p>
          </div>
          <div className="col-span-2 flex items-center justify-between gap-3 rounded-xl border border-amber-100 bg-amber-50/80 p-3">
            <div>
              <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-700">Actions</p>
              <p className="mt-1 text-sm font-medium text-slate-700">Refresh the ledger without leaving the page.</p>
            </div>
            <button
              type="button"
              onClick={refreshOrders}
              className="rounded-full border border-slate-900 bg-slate-900 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-white transition hover:-translate-y-0.5 hover:bg-slate-800"
            >
              Refresh
            </button>
          </div>
        </div>
      </header>

      {ordersLoading ? (
        <LoadingBlock label="Loading orders" />
      ) : userOrders.length === 0 ? (
        <div className="rounded-3xl border border-dashed border-amber-200 bg-white/70 p-10 text-center shadow-sm">
          <p className="text-lg font-semibold text-slate-800">No orders yet</p>
          <p className="mt-2 text-sm leading-6 text-slate-500">Your purchases will appear here once you place an order from the shop.</p>
        </div>
      ) : (
        <>
          <div className="space-y-4">
            {paginatedOrders.map((order) => (
              <article className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_8px_30px_rgba(15,23,42,0.04)]" key={order.id}>
                <div className="h-1 bg-gradient-to-r from-amber-200 via-slate-900 to-amber-300" />

                <div className="p-4 sm:p-5 lg:p-6">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <p className="text-[10px] font-bold uppercase tracking-[0.28em] text-slate-500">Order</p>
                      <p className="mt-1 text-lg font-extrabold tracking-tight text-slate-950">#{order.orderId}</p>
                      <p className="mt-1 text-sm text-slate-500">Placed on {new Date(order.createdAt).toLocaleDateString()}</p>
                    </div>

                    <span className={`inline-flex rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] ${getOrderStatusBadge(order.status)}`}>
                      {formatOrderStatus(order.status)}
                    </span>
                  </div>

                  <div className="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                      <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Total</p>
                      <p className="mt-2 text-xl font-black text-slate-950">{currency(order.totalPrice)}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                      <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Payment</p>
                      <p className="mt-2 text-xl font-black text-slate-950">{order.paymentMethod.toUpperCase()}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                      <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Area</p>
                      <p className="mt-2 text-xl font-black text-slate-950">
                        {[order.division, order.district, order.upazila, order.area].filter(Boolean).join(', ') || '—'}
                      </p>
                    </div>
                    <div className="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                      <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">Tracking</p>
                      <p className="mt-2 text-xl font-black text-slate-950">{order.courierTrackingNumber || 'Pending'}</p>
                    </div>
                  </div>

                  <div className="mt-5 rounded-2xl border border-slate-200 bg-[#fcfbf7] p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                      <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">
                        Order Items ({order.items?.length || 0})
                      </p>
                    </div>

                    <div className="mt-3 space-y-2">
                      {order.items?.map((item) => (
                        <div key={item.id} className="flex items-center justify-between rounded-xl border border-slate-100 bg-white px-3 py-2.5">
                          <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium text-slate-900">{item.product?.title || `Product #${item.productId}`}</p>
                            <p className="mt-0.5 text-xs text-slate-500">
                              {item.quantity} × {currency(item.price)}
                              {item.color ? ` · Color: ${item.color}` : ''}
                              {item.size ? ` · Size: ${item.size}` : ''}
                            </p>
                          </div>
                          <span className="ml-3 text-sm font-bold text-slate-950">{currency(Number(item.price) * item.quantity)}</span>
                        </div>
                      ))}
                      {(!order.items || order.items.length === 0) && (
                        <p className="text-sm text-slate-500">Item details not available.</p>
                      )}
                    </div>
                  </div>

                  <div className="mt-5 rounded-2xl border border-slate-200 bg-[#fcfbf7] p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                      <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">
                        Delivery Timeline ({order.historyCount || order.statusHistory?.length || 0})
                      </p>
                      <p className="text-sm font-medium text-slate-600">{formatOrderStatus(order.status)}</p>
                    </div>

                    <div className="mt-4 space-y-3">
                      {(order.statusHistory || []).map((entry, index) => (
                        <div className="flex gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3" key={entry.id}>
                          <div className="flex flex-col items-center pt-1">
                            <span className={`h-3 w-3 rounded-full ${index === (order.statusHistory?.length || 1) - 1 ? 'bg-slate-900' : 'bg-slate-300'}`} />
                            {index < (order.statusHistory?.length || 0) - 1 ? <span className="mt-2 h-full w-px bg-slate-200" /> : null}
                          </div>

                          <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                              <span className="text-sm font-semibold tracking-wide text-slate-900">{formatOrderStatus(entry.status)}</span>
                              <span className="text-xs text-slate-500">{new Date(entry.createdAt).toLocaleString()}</span>
                            </div>
                            {entry.note ? <p className="mt-1 text-sm leading-6 text-slate-600">{entry.note}</p> : null}
                          </div>
                        </div>
                      ))}

                      {(!order.statusHistory || order.statusHistory.length === 0) && (
                        <p className="text-sm text-slate-500">No timeline available yet.</p>
                      )}
                    </div>
                  </div>
                </div>
              </article>
            ))}
          </div>

          <div className="mt-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm font-medium text-slate-600">
              Showing {(page - 1) * PER_PAGE + 1}-{Math.min(page * PER_PAGE, userOrders.length)} of {userOrders.length} orders
            </p>
            <PaginationControls currentPage={page} totalPages={totalPages} onPageChange={setPage} />
          </div>
        </>
      )}
    </div>
  )
}

export default DashboardOrdersPage
