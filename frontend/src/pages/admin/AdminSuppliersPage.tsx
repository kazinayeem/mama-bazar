import { useCallback } from 'react'
import { Truck } from 'lucide-react'
import CatalogCrudPage, { type CatalogField, type CatalogColumn } from '@/components/admin/CatalogCrudPage'
import { toListResult, removeResult, moveResult } from '@/components/admin/masterDataAdapters'
import SmartImage from '@/components/common/SmartImage'
import {
  useLazyGetAdminSuppliersAdminQuery,
  useCreateSupplierMutation,
  useUpdateSupplierMutation,
  useDeleteSupplierMutation,
  useMoveSupplierProductsMutation,
  useGetAdminSuppliersQuery,
} from '@/store/services/adminProductsApi'
import type { Supplier } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const FIELDS: CatalogField[] = [
  { key: 'name', label: 'Supplier Name', type: 'text', placeholder: 'e.g. Dhaka Electronics Imports', required: true },
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

const AdminSuppliersPage = () => {
  const [trigger] = useLazyGetAdminSuppliersAdminQuery()
  const { data: allSuppliers = [] } = useGetAdminSuppliersQuery()
  const [createSupplier] = useCreateSupplierMutation()
  const [updateSupplier] = useUpdateSupplierMutation()
  const [deleteSupplier] = useDeleteSupplierMutation()
  const [moveSupplierProducts] = useMoveSupplierProductsMutation()

  const list = useCallback(
    async (params?: { page?: number; limit?: number; search?: string; status?: string }) => {
      const res = await trigger(
        { page: params?.page || 1, limit: params?.limit || 20, search: params?.search, status: params?.status },
        true,
      )
      if (res.error) throw new Error('Failed to load suppliers')
      return toListResult(res.data!)
    },
    [trigger],
  )

  const columns: CatalogColumn<Supplier>[] = [
    {
      key: 'name',
      label: 'Supplier',
      render: (s) => (
        <div className="flex items-center gap-2.5">
          <SmartImage src={s.logo || ''} alt="" className="h-9 w-9 rounded-md object-contain p-1" icon={<Truck className="h-4 w-4" />} />
          <div>
            <p className="font-medium">{s.name}</p>
            <p className="text-xs text-muted-foreground">{s.contact || '—'}</p>
          </div>
        </div>
      ),
    },
    { key: 'phone', label: 'Phone', render: (s) => <span className="text-sm">{s.phone || '—'}</span> },
    { key: 'email', label: 'Email', render: (s) => <span className="text-sm text-muted-foreground">{s.email || '—'}</span> },
    {
      key: 'status',
      label: 'Status',
      render: (s) => {
        const variant = s.status === 'active' ? ('success' as const) : s.status === 'archived' ? ('muted' as const) : ('warning' as const)
        return (
          <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${variant === 'success' ? 'bg-green-100 text-green-700' : variant === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-muted text-muted-foreground'}`}>
            {s.status}
          </span>
        )
      },
    },
  ]

  return (
    <>
      <SEO title="Manage Suppliers" description="Manage supplier information." url="/admin/suppliers" />
      <CatalogCrudPage<Supplier>
      title="Suppliers"
      description="Suppliers that source your products"
      emptyMessage="No suppliers yet. Create your first supplier."
      icon={<Truck className="h-8 w-8" />}
      fields={FIELDS}
      columns={columns}
      api={{
        list,
        create: async (payload) => {
          const res = await createSupplier(payload)
          if (res.error) throw new Error('Create failed')
        },
        update: async (id, payload) => {
          const res = await updateSupplier({ id, payload })
          if (res.error) throw new Error('Update failed')
        },
        remove: async (id) => removeResult(await deleteSupplier(id)),
        move: async (id, targetId) => moveResult(await moveSupplierProducts({ id, targetId })),
      }}
      moveOptions={allSuppliers.map((s) => ({ value: String(s.id), label: s.name }))}
    />
    </>
  )
}

export default AdminSuppliersPage
