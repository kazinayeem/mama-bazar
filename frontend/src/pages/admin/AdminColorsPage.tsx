import { useCallback } from 'react'
import { Palette } from 'lucide-react'
import CatalogCrudPage, { type CatalogField, type CatalogColumn } from '@/components/admin/CatalogCrudPage'
import { toListResult, removeResult, moveResult } from '@/components/admin/masterDataAdapters'
import {
  useLazyGetAdminColorsAdminQuery,
  useCreateColorMutation,
  useUpdateColorMutation,
  useDeleteColorMutation,
  useMoveColorProductsMutation,
} from '@/store/services/adminProductsApi'
import type { Color } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const FIELDS: CatalogField[] = [
  { key: 'name', label: 'Name', type: 'text', placeholder: 'e.g. black', required: true },
  { key: 'displayName', label: 'Display Name', type: 'text', placeholder: 'e.g. Black (shown to customers)' },
  { key: 'hex', label: 'Hex Color', type: 'hex', placeholder: '#000000', required: true },
  { key: 'sortOrder', label: 'Sort Order', type: 'number' },
  { key: 'status', label: 'Status', type: 'select', options: [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'archived', label: 'Archived' },
  ] },
]

const AdminColorsPage = () => {
  const [trigger] = useLazyGetAdminColorsAdminQuery()
  const [createColor] = useCreateColorMutation()
  const [updateColor] = useUpdateColorMutation()
  const [deleteColor] = useDeleteColorMutation()
  const [moveColorProducts] = useMoveColorProductsMutation()

  const list = useCallback(
    async (params?: { page?: number; limit?: number; search?: string; status?: string }) => {
      const res = await trigger({ page: params?.page || 1, limit: params?.limit || 20, search: params?.search, status: params?.status })
      if (res.error) throw new Error('Failed to load colors')
      return toListResult(res.data!)
    },
    [trigger],
  )

  const columns: CatalogColumn<Color>[] = [
    {
      key: 'color',
      label: 'Color',
      render: (c) => (
        <div className="flex items-center gap-2.5">
          <span className="h-6 w-6 rounded-full border" style={{ backgroundColor: c.hex }} />
          <span className="font-medium">{c.displayName || c.name}</span>
          <span className="text-xs text-muted-foreground">({c.name})</span>
        </div>
      ),
    },
    { key: 'hex', label: 'Hex', render: (c) => <code className="text-xs">{c.hex}</code> },
    { key: 'sortOrder', label: 'Order', render: (c) => <span className="text-sm text-muted-foreground">{c.sortOrder}</span> },
    {
      key: 'status',
      label: 'Status',
      render: (c) => {
        const variant = c.status === 'active' ? ('success' as const) : c.status === 'archived' ? ('muted' as const) : ('warning' as const)
        return (
          <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${variant === 'success' ? 'bg-green-100 text-green-700' : variant === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-muted text-muted-foreground'}`}>
            {c.status}
          </span>
        )
      },
    },
  ]

  return (
    <>
      <SEO title="Manage Colors" description="Manage color options for products." url="/admin/colors" />
      <CatalogCrudPage<Color>
      title="Colors"
      description="Color options used by product variants"
      emptyMessage="No colors yet. Create your first color."
      icon={<Palette className="h-8 w-8" />}
      fields={FIELDS}
      columns={columns}
      api={{
        list,
        create: async (payload) => {
          const res = await createColor(payload)
          if (res.error) throw new Error('Create failed')
        },
        update: async (id, payload) => {
          const res = await updateColor({ id, payload })
          if (res.error) throw new Error('Update failed')
        },
        remove: async (id) => removeResult(await deleteColor(id)),
        move: async (id, targetId) => moveResult(await moveColorProducts({ id, targetId })),
      }}
    />
    </>
  )
}

export default AdminColorsPage
