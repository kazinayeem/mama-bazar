import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Download, Loader2, Plus, Trash2, Upload } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '@/components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import ProductFilters from '../components/ProductFilters'
import ProductTable from '../components/ProductTable'
import { DEFAULT_FILTERS, type ListFilters } from '../lib/types'
import { parseError } from '@/store/services/adminProductsApi'
import {
  useGetBrandsQuery,
  useGetCategoriesQuery,
  useGetCollectionsQuery,
} from '@/store/services/commerceApi'
import {
  adminProductsApi,
  useBulkProductActionMutation,
  useDeleteProductMutation,
  useDuplicateProductMutation,
  useExportProductsCsvMutation,
  useGetAdminProductsQuery,
  useGetAdminSuppliersQuery,
  useGetAdminVendorsQuery,
  useImportProductsCsvMutation,
  useUpdateProductMutation,
} from '@/store/services/adminProductsApi'
import { useAppDispatch } from '@/store/hooks'
import { store } from '@/store'
import type { AdminProduct, AdminProductFilters, AdminProductListResult, ProductBulkAction } from '@/types/admin'

const PAGE_SIZE = 10

const BULK_STATUS_MAP: Record<string, { productStatus: 'draft' | 'published' | 'hidden' | 'archived'; status: 'active' | 'inactive' }> = {
  publish: { productStatus: 'published', status: 'active' },
  draft: { productStatus: 'draft', status: 'inactive' },
  hide: { productStatus: 'hidden', status: 'inactive' },
  archive: { productStatus: 'archived', status: 'inactive' },
}

const AdminProductListPage = () => {
  const navigate = useNavigate()
  const dispatch = useAppDispatch()
  const fileInputRef = useRef<HTMLInputElement>(null)

  const [filters, setFilters] = useState<ListFilters>(DEFAULT_FILTERS)
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [selected, setSelected] = useState<Set<number>>(new Set())
  const [deleteTarget, setDeleteTarget] = useState<AdminProduct | null>(null)
  const [bulkDeleteOpen, setBulkDeleteOpen] = useState(false)
  const [importing, setImporting] = useState(false)

  // Debounce search so every keystroke doesn't hit the API
  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(filters.search.trim()), 400)
    return () => clearTimeout(timer)
  }, [filters.search])

  const patch = useCallback((p: Partial<ListFilters>) => {
    setFilters((prev) => ({ ...prev, ...p }))
  }, [])

  const queryArgs = useMemo(
    () => ({
      page: filters.page,
      limit: PAGE_SIZE,
      search: debouncedSearch || undefined,
      category: filters.category || undefined,
      brand: filters.brand || undefined,
      supplier: filters.supplier || undefined,
      vendor: filters.vendor || undefined,
      collection: filters.collection || undefined,
      stock: (filters.stock || undefined) as AdminProductFilters['stock'],
      productStatus: filters.productStatus || undefined,
      label: filters.label || undefined,
      minPrice: filters.minPrice || undefined,
      maxPrice: filters.maxPrice || undefined,
      dateFrom: filters.dateFrom || undefined,
      dateTo: filters.dateTo || undefined,
      sort: filters.sort,
      status: 'all',
    }),
    [filters, debouncedSearch],
  )

  const { data, isFetching, isLoading, isError, error, refetch } = useGetAdminProductsQuery(queryArgs)

  const { data: categories = [], isLoading: categoriesLoading } = useGetCategoriesQuery()
  const { data: brands = [], isLoading: brandsLoading } = useGetBrandsQuery()
  const { data: suppliers = [], isLoading: suppliersLoading } = useGetAdminSuppliersQuery()
  const { data: vendors = [], isLoading: vendorsLoading } = useGetAdminVendorsQuery()
  const { data: collections = [], isLoading: collectionsLoading } = useGetCollectionsQuery()

  const [bulkAction, { isLoading: bulkBusy }] = useBulkProductActionMutation()
  const [deleteProduct] = useDeleteProductMutation()
  const [duplicateProduct] = useDuplicateProductMutation()
  const [exportCsv, { isLoading: exporting }] = useExportProductsCsvMutation()
  const [importCsv] = useImportProductsCsvMutation()
  const [updateProduct] = useUpdateProductMutation()

  const products = data?.data || []
  const total = data?.total || 0
  const totalPages = data?.totalPages || 1

  // ---------- Optimistic cache helpers ----------
  const updateCachedLists = useCallback(
    (updater: (draft: AdminProductListResult) => void) => {
      const cachedArgs = adminProductsApi.util.selectCachedArgsForQuery(store.getState(), 'getAdminProducts')
      const undos: Array<() => void> = []
      cachedArgs.forEach((args) => {
        const patchResult = dispatch(
          adminProductsApi.util.updateQueryData('getAdminProducts', args, (draft) => {
            updater(draft)
          }),
        )
        undos.push(patchResult.undo)
      })
      return undos
    },
    [dispatch],
  )

  const optimisticBulk = useCallback(
    async (action: ProductBulkAction, ids: number[]) => {
      let undos: Array<() => void> = []
      if (action !== 'delete') {
        const target = BULK_STATUS_MAP[action]
        if (target) {
          undos = updateCachedLists((draft) => {
            draft.data = draft.data.map((p) =>
              ids.includes(p.id) ? { ...p, productStatus: target.productStatus, status: target.status } : p,
            )
          })
        }
      } else {
        undos = updateCachedLists((draft) => {
          draft.data = draft.data.filter((p) => !ids.includes(p.id))
          draft.total = Math.max(0, draft.total - ids.length)
        })
      }
      try {
        const result = await bulkAction({ action, ids }).unwrap()
        toast.success(`${result.affected} product(s) ${action === 'delete' ? 'deleted' : `${action}ed`}`)
        return result
      } catch (err) {
        undos.forEach((u) => u())
        throw err
      }
    },
    [bulkAction, updateCachedLists],
  )

  const handleBulk = async (action: ProductBulkAction) => {
    if (selected.size === 0) return
    if (action === 'delete') {
      setBulkDeleteOpen(true)
      return
    }
    try {
      await optimisticBulk(action, Array.from(selected))
      setSelected(new Set())
    } catch (err) {
      toast.error(parseError(err))
    }
  }

  const handleBulkDelete = async () => {
    try {
      await optimisticBulk('delete', Array.from(selected))
      setSelected(new Set())
      setBulkDeleteOpen(false)
    } catch (err) {
      toast.error(parseError(err))
    }
  }

  const handleDelete = async () => {
    if (!deleteTarget) return
    const undos = updateCachedLists((draft) => {
      draft.data = draft.data.filter((p) => p.id !== deleteTarget.id)
      draft.total = Math.max(0, draft.total - 1)
    })
    try {
      await deleteProduct(deleteTarget.id).unwrap()
      toast.success('Product deleted')
      setDeleteTarget(null)
    } catch (err) {
      undos.forEach((u) => u())
      toast.error(parseError(err))
    }
  }

  const handleDuplicate = async (product: AdminProduct) => {
    try {
      const copy = await duplicateProduct(product.id).unwrap()
      toast.success(`Duplicated as "${copy.title}"`)
    } catch (err) {
      toast.error(parseError(err))
    }
  }

  const handleToggleFeatured = async (product: AdminProduct, featured: boolean) => {
    const undos = updateCachedLists((draft) => {
      draft.data = draft.data.map((p) => (p.id === product.id ? { ...p, isFeatured: featured } : p))
    })
    try {
      await updateProduct({ id: product.id, payload: { isFeatured: featured } }).unwrap()
    } catch (err) {
      undos.forEach((u) => u())
      toast.error(parseError(err))
    }
  }

  const handleExport = async () => {
    try {
      const csv = await exportCsv({
        search: filters.search || undefined,
        category: filters.category || undefined,
        brand: filters.brand || undefined,
        supplier: filters.supplier || undefined,
        vendor: filters.vendor || undefined,
        collection: filters.collection || undefined,
        productStatus: filters.productStatus || undefined,
        label: filters.label || undefined,
      }).unwrap()
      const blob = new Blob([csv], { type: 'text/csv' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `products-${new Date().toISOString().slice(0, 10)}.csv`
      a.click()
      URL.revokeObjectURL(url)
      toast.success('CSV exported')
    } catch (err) {
      toast.error(parseError(err))
    }
  }

  const handleImport = async (file: File | null) => {
    if (!file) return
    setImporting(true)
    try {
      const csv = await file.text()
      const result = await importCsv(csv).unwrap()
      toast.success(`${result.imported} product(s) imported`)
    } catch (err) {
      toast.error(parseError(err))
    } finally {
      setImporting(false)
      if (fileInputRef.current) fileInputRef.current.value = ''
    }
  }

  const allSelected = products.length > 0 && products.every((p) => selected.has(p.id))
  const someSelected = products.some((p) => selected.has(p.id)) && !allSelected

  const toggleAll = (checked: boolean) => {
    if (checked) {
      setSelected((prev) => {
        const next = new Set(prev)
        products.forEach((p) => next.add(p.id))
        return next
      })
    } else {
      setSelected((prev) => {
        const next = new Set(prev)
        products.forEach((p) => next.delete(p.id))
        return next
      })
    }
  }

  const toggleSelected = (id: number) => {
    setSelected((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  return (
    <AdminLayout>
      <div className="mx-auto w-full max-w-[1400px] space-y-4">
        {/* Header */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Products</h1>
            <p className="text-sm text-muted-foreground">{total} products · manage your catalog</p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <input
              ref={fileInputRef}
              type="file"
              accept=".csv"
              className="hidden"
              onChange={(e) => handleImport(e.target.files?.[0] || null)}
            />
            <Button variant="outline" disabled={exporting} onClick={handleExport}>
              {exporting ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Download className="mr-1 h-4 w-4" />}
              Export CSV
            </Button>
            <Button variant="outline" disabled={importing} onClick={() => fileInputRef.current?.click()}>
              {importing ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Upload className="mr-1 h-4 w-4" />}
              Import CSV
            </Button>
            <Button onClick={() => navigate('/admin/products/create')}>
              <Plus className="mr-1 h-4 w-4" /> Add Product
            </Button>
          </div>
        </div>

        {/* Search + filters */}
        <Card>
          <CardContent className="p-3">
            <ProductFilters
              filters={filters}
              onChange={patch}
              onClear={() => setFilters(DEFAULT_FILTERS)}
              reference={{
                categories,
                brands,
                suppliers,
                vendors,
                collections,
                loading: categoriesLoading || brandsLoading || suppliersLoading || vendorsLoading || collectionsLoading,
              }}
            />
          </CardContent>
        </Card>

        {/* Bulk action bar */}
        {selected.size > 0 && (
          <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/40 p-3">
            <Badge variant="secondary">{selected.size} selected</Badge>
            <Button size="sm" disabled={bulkBusy} onClick={() => handleBulk('publish')}>
              Publish
            </Button>
            <Button size="sm" variant="outline" disabled={bulkBusy} onClick={() => handleBulk('draft')}>
              Move to Draft
            </Button>
            <Button size="sm" variant="outline" disabled={bulkBusy} onClick={() => handleBulk('hide')}>
              Hide
            </Button>
            <Button size="sm" variant="outline" disabled={bulkBusy} onClick={() => handleBulk('archive')}>
              Archive
            </Button>
            <Button size="sm" variant="destructive" disabled={bulkBusy} onClick={() => handleBulk('delete')}>
              <Trash2 className="mr-1 h-3.5 w-3.5" /> Delete
            </Button>
            <Button size="sm" variant="ghost" className="ml-auto" onClick={() => setSelected(new Set())}>
              Clear
            </Button>
          </div>
        )}

        {/* Table */}
        <ProductTable
          products={products}
          loading={isLoading || isFetching}
          error={isError ? parseError(error) : undefined}
          onRetry={refetch}
          selected={selected}
          onToggleSelected={toggleSelected}
          onToggleAll={toggleAll}
          allSelected={allSelected}
          someSelected={someSelected}
          onView={(p) => navigate(`/admin/products/${p.id}`)}
          onEdit={(p) => navigate(`/admin/products/${p.id}/edit`)}
          onDuplicate={handleDuplicate}
          onDelete={setDeleteTarget}
          onToggleFeatured={handleToggleFeatured}
        />

        {/* Pagination */}
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
          <p className="text-xs text-muted-foreground">
            Page {data?.page || 1} of {totalPages} · {total} products
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={filters.page <= 1}
              onClick={() => patch({ page: filters.page - 1 })}
            >
              Previous
            </Button>
            <div className="flex items-center gap-1">
              {Array.from({ length: Math.min(totalPages, 7) }, (_, i) => {
                const start = Math.max(1, Math.min(filters.page - 3, totalPages - 6))
                const page = start + i
                return (
                  <Button
                    key={page}
                    variant={page === filters.page ? 'default' : 'outline'}
                    size="icon"
                    className="h-8 w-8"
                    onClick={() => patch({ page })}
                  >
                    {page}
                  </Button>
                )
              })}
            </div>
            <Button
              variant="outline"
              size="sm"
              disabled={filters.page >= totalPages}
              onClick={() => patch({ page: filters.page + 1 })}
            >
              Next
            </Button>
          </div>
        </div>
      </div>

      {/* Delete confirmation */}
      <AlertDialog open={!!deleteTarget} onOpenChange={(v) => !v && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete product?</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete "{deleteTarget?.title}". This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog open={bulkDeleteOpen} onOpenChange={(v) => !v && setBulkDeleteOpen(false)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete {selected.size} product(s)?</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete all selected products. This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleBulkDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminLayout>
  )
}

export default AdminProductListPage
