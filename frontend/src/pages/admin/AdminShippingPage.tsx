import { Truck } from 'lucide-react'
import CatalogCrudPage from '@/components/admin/CatalogCrudPage'
import type { ShippingMethod } from '@/types'
import { Badge } from '@/components/ui/badge'
import { currency } from '@/lib/format'
import { SEO } from '../../components/common/SEO'
import { store } from '@/store'
import { adminProductsApi } from '@/store/services/adminProductsApi'

const columns = [
  { key: 'name', label: 'Name', render: (m: ShippingMethod) => <span className="font-medium">{m.name}</span> },
  {
    key: 'charge',
    label: 'Charge',
    render: (m: ShippingMethod) => (
      <span>
        {currency(Number(m.charge))}
        {m.freeShippingMinAmount !== null && m.freeShippingMinAmount !== undefined && (
          <span className="block text-xs text-muted-foreground">FREE over {currency(Number(m.freeShippingMinAmount))}</span>
        )}
      </span>
    ),
  },
  {
    key: 'estimatedDelivery',
    label: 'Delivery',
    render: (m: ShippingMethod) => <span className="text-muted-foreground">{m.estimatedDelivery || '—'}</span>,
  },
  {
    key: 'codAvailable',
    label: 'COD',
    render: (m: ShippingMethod) =>
      m.codAvailable ? <Badge variant="success">Available</Badge> : <Badge variant="outline">Not available</Badge>,
  },
  {
    key: 'status',
    label: 'Status',
    render: (m: ShippingMethod) =>
      m.status === 'active' ? <Badge variant="success">Active</Badge> : <Badge variant="secondary">Inactive</Badge>,
  },
]

const AdminShippingPage = () => {
  return (
    <>
      <SEO title="Shipping Methods" description="Configure shipping methods and rates." url="/admin/shipping" />
      <CatalogCrudPage
      title="Shipping Methods"
      description="Manage delivery options shown at checkout. Free shipping applies when an order reaches the minimum amount."
      emptyMessage="No shipping methods yet. Add your first delivery option."
      icon={<Truck className="h-8 w-8" />}
      fields={[
        { key: 'name', label: 'Name', type: 'text', placeholder: 'e.g. Inside Dhaka (Standard)', fullWidth: true },
        { key: 'charge', label: 'Charge (Tk)', type: 'number', placeholder: '60' },
        { key: 'estimatedDelivery', label: 'Estimated Delivery', type: 'text', placeholder: '1-3 days' },
        { key: 'priority', label: 'Priority', type: 'number', placeholder: '1' },
        { key: 'freeShippingMinAmount', label: 'Free Shipping Min (Tk)', type: 'number', placeholder: '5000 (0 = never free)' },
        { key: 'codAvailable', label: 'Cash on Delivery Available', type: 'switch', placeholder: 'Allow COD for this method' },
        {
          key: 'status',
          label: 'Status',
          type: 'select',
          options: [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
          ],
        },
        { key: 'description', label: 'Description', type: 'textarea', fullWidth: true, placeholder: 'Shown under the method name' },
      ]}
      columns={columns}
      api={{
        list: async () => {
          const rows = await store
            .dispatch(adminProductsApi.endpoints.getAdminShippingMethods.initiate(undefined))
            .unwrap()
          return {
            data: rows,
            pagination: { page: 1, limit: rows.length, total: rows.length, totalPages: 1 },
          }
        },
        create: (payload) =>
          store.dispatch(adminProductsApi.endpoints.createShippingMethod.initiate(payload as never)).unwrap(),
        update: (id, payload) =>
          store
            .dispatch(adminProductsApi.endpoints.updateShippingMethod.initiate({ id, payload: payload as never }))
            .unwrap(),
        remove: async (id) => {
          await store.dispatch(adminProductsApi.endpoints.deleteShippingMethod.initiate(id)).unwrap()
          return undefined
        },
      }}
    />
    </>
  )
}

export default AdminShippingPage
