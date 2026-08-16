import { BellRing } from 'lucide-react'
import CatalogCrudPage from '@/components/admin/CatalogCrudPage'
import type { AdminCheckoutNotice } from '@/types/admin'
import { Badge } from '@/components/ui/badge'
import { SEO } from '../../components/common/SEO'
import { store } from '@/store'
import { adminProductsApi } from '@/store/services/adminProductsApi'

const ICON_OPTIONS = [
  { value: 'alert', label: '⚠️ Alert' },
  { value: 'truck', label: '🚚 Truck' },
  { value: 'info', label: 'ℹ️ Info' },
  { value: 'discount', label: '🏷️ Discount' },
]

const columns = [
  {
    key: 'text',
    label: 'Message',
    render: (n: AdminCheckoutNotice) => (
      <span
        className="line-clamp-2 max-w-md text-sm"
        style={{ backgroundColor: n.backgroundColor, color: n.textColor }}
      >
        {n.text}
      </span>
    ),
  },
  { key: 'icon', label: 'Icon', render: (n: AdminCheckoutNotice) => <span>{ICON_OPTIONS.find((i) => i.value === n.icon)?.label || n.icon}</span> },
  { key: 'priority', label: 'Priority', render: (n: AdminCheckoutNotice) => <span className="text-muted-foreground">{n.priority}</span> },
  {
    key: 'status',
    label: 'Status',
    render: (n: AdminCheckoutNotice) =>
      n.status === 'active' ? <Badge variant="success">Active</Badge> : <Badge variant="secondary">Inactive</Badge>,
  },
]

const AdminCheckoutNoticesPage = () => {
  return (
    <>
      <SEO title="Checkout Notices" description="Manage checkout notice messages." url="/admin/checkout-notices" />
      <CatalogCrudPage
      title="Checkout Notices"
      description="Banners shown at the top of the checkout page (delivery offers, payment reminders, etc.)"
      emptyMessage="No checkout notices. Add a notice to display at checkout."
      icon={<BellRing className="h-8 w-8" />}
      fields={[
        { key: 'text', label: 'Message Text', type: 'textarea', fullWidth: true, placeholder: 'e.g. Free delivery on orders over Tk 5,000' },
        { key: 'priority', label: 'Priority', type: 'number', placeholder: '1' },
        { key: 'backgroundColor', label: 'Background Color', type: 'hex' },
        { key: 'textColor', label: 'Text Color', type: 'hex' },
        { key: 'icon', label: 'Icon', type: 'select', options: ICON_OPTIONS },
        {
          key: 'status',
          label: 'Status',
          type: 'select',
          options: [
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
          ],
        },
      ]}
      columns={columns}
      api={{
        list: async () => {
          const rows = await store
            .dispatch(adminProductsApi.endpoints.getAdminCheckoutNotices.initiate(undefined, { forceRefetch: true }))
            .unwrap()
          return {
            data: rows,
            pagination: { page: 1, limit: rows.length, total: rows.length, totalPages: 1 },
          }
        },
        create: (payload) =>
          store.dispatch(adminProductsApi.endpoints.createCheckoutNotice.initiate(payload as never)).unwrap(),
        update: (id, payload) =>
          store
            .dispatch(adminProductsApi.endpoints.updateCheckoutNotice.initiate({ id, payload: payload as never }))
            .unwrap(),
        remove: async (id) => {
          await store.dispatch(adminProductsApi.endpoints.deleteCheckoutNotice.initiate(id)).unwrap()
          return undefined
        },
      }}
    />
    </>
  )
}

export default AdminCheckoutNoticesPage
