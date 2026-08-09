import type { Order } from '../../types'

export const formatOrderStatus = (status: Order['status']) => {
  if (status === 'out_for_delivery') return 'Out for Delivery'
  return status
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

export const getOrderStatusBadge = (status: Order['status']) => {
  if (status === 'delivered') return 'bg-emerald-100 text-emerald-800 border-emerald-200'
  if (status === 'out_for_delivery') return 'bg-cyan-100 text-cyan-800 border-cyan-200'
  if (status === 'shipped') return 'bg-indigo-100 text-indigo-800 border-indigo-200'
  if (status === 'packed') return 'bg-violet-100 text-violet-800 border-violet-200'
  if (status === 'processing') return 'bg-blue-100 text-blue-800 border-blue-200'
  if (status === 'confirmed') return 'bg-amber-100 text-amber-800 border-amber-200'
  if (status === 'cancelled') return 'bg-red-100 text-red-700 border-red-200'
  return 'bg-gray-100 text-gray-700 border-gray-200'
}
