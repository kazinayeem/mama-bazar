import { useCallback } from 'react'
import { FolderOpen } from 'lucide-react'
import CatalogCrudPage, { type CatalogField, type CatalogColumn } from '@/components/admin/CatalogCrudPage'
import { toListResult, removeResult, moveResult } from '@/components/admin/masterDataAdapters'
import SmartImage from '@/components/common/SmartImage'
import {
  useLazyGetAdminCollectionsAdminQuery,
  useCreateCollectionMutation,
  useUpdateCollectionMutation,
  useDeleteCollectionMutation,
  useMoveCollectionProductsMutation,
  useGetAdminCollectionsQuery,
} from '@/store/services/adminProductsApi'
import type { Collection } from '@/types/admin'
import { SEO } from '../../components/common/SEO'

const FIELDS: CatalogField[] = [
  { key: 'name', label: 'Name', type: 'text', placeholder: 'e.g. Summer Sale', required: true },
  { key: 'slug', label: 'Slug', type: 'text', placeholder: 'auto-generated' },
  { key: 'image', label: 'Image', type: 'image' },
  { key: 'banner', label: 'Banner', type: 'image' },
  { key: 'description', label: 'Description', type: 'textarea', fullWidth: true },
  { key: 'startDate', label: 'Start Date', type: 'date' },
  { key: 'endDate', label: 'End Date', type: 'date' },
  { key: 'sortOrder', label: 'Sort Order', type: 'number' },
  { key: 'featured', label: 'Featured', type: 'switch', placeholder: 'Highlight on storefront' },
  { key: 'homepageVisibility', label: 'Show on homepage', type: 'switch', placeholder: 'Display in homepage collections' },
  { key: 'status', label: 'Status', type: 'select', options: [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'archived', label: 'Archived' },
  ] },
]

const AdminCollectionsPage = () => {
  const [trigger] = useLazyGetAdminCollectionsAdminQuery()
  const { data: allCollections = [] } = useGetAdminCollectionsQuery()
  const [createCollection] = useCreateCollectionMutation()
  const [updateCollection] = useUpdateCollectionMutation()
  const [deleteCollection] = useDeleteCollectionMutation()
  const [moveCollectionProducts] = useMoveCollectionProductsMutation()

  const list = useCallback(
    async (params?: { page?: number; limit?: number; search?: string; status?: string }) => {
      const res = await trigger({ page: params?.page || 1, limit: params?.limit || 20, search: params?.search, status: params?.status })
      if (res.error) throw new Error('Failed to load collections')
      return toListResult(res.data!)
    },
    [trigger],
  )

  const columns: CatalogColumn<Collection>[] = [
    {
      key: 'name',
      label: 'Collection',
      render: (c) => (
        <div className="flex items-center gap-2.5">
          <SmartImage src={c.image || ''} alt="" className="h-9 w-9 rounded-md object-cover" icon={<FolderOpen className="h-4 w-4" />} />
          <div>
            <p className="font-medium">{c.name}</p>
            <p className="text-xs text-muted-foreground">/{c.slug}</p>
          </div>
        </div>
      ),
    },
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
    { key: 'sortOrder', label: 'Order', render: (c) => <span className="text-sm text-muted-foreground">{c.sortOrder}</span> },
    {
      key: 'featured',
      label: 'Featured',
      render: (c) => (c.featured ? <span className="text-xs font-medium text-amber-600">Featured</span> : <span className="text-xs text-muted-foreground">—</span>),
    },
  ]

  return (
    <>
      <SEO title="Manage Collections" description="Create and manage product collections." url="/admin/collections" />
      <CatalogCrudPage<Collection>
      title="Collections"
      description="Curated product collections shown on the storefront"
      emptyMessage="No collections yet. Create your first collection."
      icon={<FolderOpen className="h-8 w-8" />}
      fields={FIELDS}
      columns={columns}
      api={{
        list,
        create: async (payload) => {
          const res = await createCollection(payload)
          if (res.error) throw new Error('Create failed')
        },
        update: async (id, payload) => {
          const res = await updateCollection({ id, payload })
          if (res.error) throw new Error('Update failed')
        },
        remove: async (id) => removeResult(await deleteCollection(id)),
        move: async (id, targetId) => moveResult(await moveCollectionProducts({ id, targetId })),
      }}
      moveOptions={allCollections.map((c) => ({ value: String(c.id), label: c.name }))}
    />
    </>
  )
}

export default AdminCollectionsPage
