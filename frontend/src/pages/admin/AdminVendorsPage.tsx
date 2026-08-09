import { useCallback } from 'react'
import { Store } from 'lucide-react'
import CatalogCrudPage, { type CatalogField, type CatalogColumn } from '@/components/admin/CatalogCrudPage'
import { toListResult, removeResult, moveResult } from '@/components/admin/masterDataAdapters'
import SmartImage from '@/components/common/SmartImage'
import {
  useLazyGetAdminVendorsAdminQuery,
  useCreateVendorMutation,
  useUpdateVendorMutation,
  useDeleteVendorMutation,
  useMoveVendorProductsMutation,
  useGetAdminVendorsQuery,
} from '@/store/services/adminProductsApi'
import type { Vendor } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const FIELDS: CatalogField[] = [
  { key: 'name', label: 'Vendor Name', type: 'text', placeholder: 'e.g. Tech Solutions Ltd', required: true },
  { key: 'logo', label: 'Logo', type: 'image' },
  { key: 'contact', label: 'Contact Person', type: 'text' },
  { key: 'phone', label: 'Phone', type: 'text' },
  { key: 'email', label: 'Email', type: 'text' },
  { key: 'address', label: 'Address', type: 'text', fullWidth: true },
  { key: 'description', label: 'Description', type: 'textarea', fullWidth: true },
  { key: 'notes', label: 'Notes', type: 'textarea', fullWidth: true },
  { key: 'status', label: 'Status', type: 'select', options: [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'archived', label: 'Archived' },
  ] },
]

const AdminVendorsPage = () => {
  const [trigger] = useLazyGetAdminVendorsAdminQuery()
  const { data: allVendors = [] } = useGetAdminVendorsQuery()
  const [createVendor] = useCreateVendorMutation()
  const [updateVendor] = useUpdateVendorMutation()
  const [deleteVendor] = useDeleteVendorMutation()
  const [moveVendorProducts] = useMoveVendorProductsMutation()

  const list = useCallback(
    async (params?: { page?: number; limit?: number; search?: string; status?: string }) => {
      const res = await trigger({ page: params?.page || 1, limit: params?.limit || 20, search: params?.search, status: params?.status })
      if (res.error) throw new Error('Failed to load vendors')
      return toListResult(res.data!)
    },
    [trigger],
  )

  const columns: CatalogColumn<Vendor>[] = [
    {
      key: 'name',
      label: 'Vendor',
      render: (v) => (
        <div className="flex items-center gap-2.5">
          <SmartImage src={v.logo || ''} alt="" className="h-9 w-9 rounded-md object-contain p-1" icon={<Store className="h-4 w-4" />} />
          <div>
            <p className="font-medium">{v.name}</p>
            <p className="text-xs text-muted-foreground">{v.contact || '—'}</p>
          </div>
        </div>
      ),
    },
    { key: 'phone', label: 'Phone', render: (v) => <span className="text-sm">{v.phone || '—'}</span> },
    { key: 'email', label: 'Email', render: (v) => <span className="text-sm text-muted-foreground">{v.email || '—'}</span> },
    {
      key: 'status',
      label: 'Status',
      render: (v) => {
        const variant = v.status === 'active' ? ('success' as const) : v.status === 'archived' ? ('muted' as const) : ('warning' as const)
        return (
          <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${variant === 'success' ? 'bg-green-100 text-green-700' : variant === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-muted text-muted-foreground'}`}>
            {v.status}
          </span>
        )
      },
    },
  ]

  return (
    <>
      <SEO title="Manage Vendors" description="Manage vendor information and listings." url="/admin/vendors" />
      <CatalogCrudPage<Vendor>
      title="Vendors"
      description="Vendors that supply your products"
      emptyMessage="No vendors yet. Create your first vendor."
      icon={<Store className="h-8 w-8" />}
      fields={FIELDS}
      columns={columns}
      api={{
        list,
        create: async (payload) => {
          const res = await createVendor(payload)
          if (res.error) throw new Error('Create failed')
        },
        update: async (id, payload) => {
          const res = await updateVendor({ id, payload })
          if (res.error) throw new Error('Update failed')
        },
        remove: async (id) => removeResult(await deleteVendor(id)),
        move: async (id, targetId) => moveResult(await moveVendorProducts({ id, targetId })),
      }}
      moveOptions={allVendors.map((v) => ({ value: String(v.id), label: v.name }))}
    />
    </>
  )
}

export default AdminVendorsPage
