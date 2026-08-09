import { useCallback } from 'react'
import { Ruler } from 'lucide-react'
import CatalogCrudPage, { type CatalogField, type CatalogColumn } from '@/components/admin/CatalogCrudPage'
import { toListResult, removeResult, moveResult } from '@/components/admin/masterDataAdapters'
import {
  useLazyGetAdminSizesAdminQuery,
  useCreateSizeMutation,
  useUpdateSizeMutation,
  useDeleteSizeMutation,
  useMoveSizeProductsMutation,
} from '@/store/services/adminProductsApi'
import type { Size } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const SIZE_TYPES = [
  { value: 'clothing', label: 'Clothing' },
  { value: 'shoes', label: 'Shoes' },
  { value: 'general', label: 'General' },
  { value: 'custom', label: 'Custom' },
]

const FIELDS: CatalogField[] = [
  { key: 'name', label: 'Name', type: 'text', placeholder: 'e.g. M', required: true },
  { key: 'type', label: 'Type', type: 'select', options: SIZE_TYPES },
  { key: 'sortOrder', label: 'Sort Order', type: 'number' },
  { key: 'status', label: 'Status', type: 'select', options: [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'archived', label: 'Archived' },
  ] },
]

const AdminSizesPage = () => {
  const [trigger] = useLazyGetAdminSizesAdminQuery()
  const [createSize] = useCreateSizeMutation()
  const [updateSize] = useUpdateSizeMutation()
  const [deleteSize] = useDeleteSizeMutation()
  const [moveSizeProducts] = useMoveSizeProductsMutation()

  const list = useCallback(
    async (params?: { page?: number; limit?: number; search?: string; status?: string }) => {
      const res = await trigger({ page: params?.page || 1, limit: params?.limit || 20, search: params?.search, status: params?.status })
      if (res.error) throw new Error('Failed to load sizes')
      return toListResult(res.data!)
    },
    [trigger],
  )

  const columns: CatalogColumn<Size>[] = [
    { key: 'name', label: 'Name', render: (s) => <span className="font-medium">{s.name}</span> },
    {
      key: 'type',
      label: 'Type',
      render: (s) => (
        <span className="rounded-full bg-muted px-2 py-0.5 text-xs capitalize text-muted-foreground">{s.type}</span>
      ),
    },
    { key: 'sortOrder', label: 'Order', render: (s) => <span className="text-sm text-muted-foreground">{s.sortOrder}</span> },
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
      <SEO title="Manage Sizes" description="Manage size options for products." url="/admin/sizes" />
      <CatalogCrudPage<Size>
        title="Sizes"
        description="Size options used by product variants"
        emptyMessage="No sizes yet. Create your first size."
        icon={<Ruler className="h-8 w-8" />}
        fields={FIELDS}
        columns={columns}
        api={{
          list,
          create: async (payload) => {
            const res = await createSize(payload)
            if (res.error) throw new Error('Create failed')
          },
          update: async (id, payload) => {
            const res = await updateSize({ id, payload })
            if (res.error) throw new Error('Update failed')
          },
          remove: async (id) => removeResult(await deleteSize(id)),
          move: async (id, targetId) => moveResult(await moveSizeProducts({ id, targetId })),
        }}
      />
    </>
  )
}

export default AdminSizesPage
