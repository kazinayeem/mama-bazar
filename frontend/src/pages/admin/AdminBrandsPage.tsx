import { useCallback, useEffect, useState } from 'react'
import { Globe, Loader2, Pencil, Plus, Search, Store, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import SmartImage from '@/components/common/SmartImage'
import CropImageField from '@/components/admin/CropImageField'
import UsageAlertDialog from '@/components/admin/UsageAlertDialog'
import { useGetBrandsQuery } from '@/store/services/commerceApi'
import {
  useLazyGetAdminBrandsAdminQuery,
  useCreateBrandMutation,
  useUpdateBrandMutation,
  useDeleteBrandMutation,
  useMoveBrandProductsMutation,
} from '@/store/services/adminProductsApi'
import type { Brand } from '@/types/admin'
import { toListResult } from '@/components/admin/masterDataAdapters'
import { SEO } from '../../components/common/SEO'

const AdminBrandsPage = () => {
  const { data: allBrands = [] } = useGetBrandsQuery()
  const [trigger] = useLazyGetAdminBrandsAdminQuery()
  const [createBrand] = useCreateBrandMutation()
  const [updateBrand] = useUpdateBrandMutation()
  const [deleteBrand] = useDeleteBrandMutation()
  const [moveBrandProducts] = useMoveBrandProductsMutation()

  const [brands, setBrands] = useState<Brand[]>([])
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState('')
  const [search, setSearch] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [total, setTotal] = useState(0)

  const [dialogOpen, setDialogOpen] = useState(false)
  const [editing, setEditing] = useState<Brand | null>(null)
  const [saving, setSaving] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<Brand | null>(null)
  const [usage, setUsage] = useState<{ count: number } | null>(null)
  const [deleting, setDeleting] = useState(false)

  const [form, setForm] = useState<Record<string, unknown>>({})

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search.trim()), 400)
    return () => clearTimeout(timer)
  }, [search])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const load = useCallback(async (isInitial = false) => {
    if (isInitial) setLoading(true)
    setLoadError('')
    try {
      const res = await trigger({ page, search: debouncedSearch, status })
      if (res.error) throw new Error('Failed to load brands')
      const result = toListResult(res.data!)
      setBrands(result.data)
      setTotal(result.pagination?.total || 0)
      setTotalPages(result.pagination?.totalPages || 1)
    } catch (err) {
      setLoadError(err instanceof Error ? err.message : 'Failed to load')
    } finally {
      setLoading(false)
    }
  }, [trigger, page, debouncedSearch, status])

  useEffect(() => {
    load(true)
  }, [load])

  const openCreate = () => {
    setEditing(null)
    setForm({ status: 'active', featured: false, homepageVisibility: true, sortOrder: 0 })
    setDialogOpen(true)
  }

  const openEdit = (brand: Brand) => {
    setEditing(brand)
    setForm({
      name: brand.name,
      slug: brand.slug,
      logo: brand.logo || '',
      bannerImage: brand.bannerImage || '',
      description: brand.description || '',
      website: brand.website || '',
      countryOfOrigin: brand.countryOfOrigin || '',
      featured: brand.featured,
      homepageVisibility: brand.homepageVisibility ?? true,
      sortOrder: brand.sortOrder ?? 0,
      seoTitle: brand.seoTitle || '',
      seoDescription: brand.seoDescription || '',
      seoKeywords: brand.seoKeywords || '',
      status: brand.status || 'active',
    })
    setDialogOpen(true)
  }

  const handleSave = async () => {
    if (!String(form.name || '').trim()) {
      toast.error('Brand name is required')
      return
    }
    setSaving(true)
    try {
      const res = editing
        ? await updateBrand({ id: editing.id, payload: form })
        : await createBrand(form)
      if (res.error) throw new Error('Save failed')
      toast.success(editing ? 'Brand updated' : 'Brand created')
      setDialogOpen(false)
      load(false)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async (brand: Brand) => {
    const res = await deleteBrand(brand.id)
    if (res.error) {
      const info = (res.error as { data?: { data?: { usageCount?: number } } }).data?.data
      if (info?.usageCount) {
        setUsage({ count: info.usageCount })
        setDeleteTarget(brand)
        return
      }
      toast.error((res.error as { data?: { message?: string } }).data?.message || 'Delete failed')
      return
    }
    toast.success('Brand deleted')
    setBrands((prev) => prev.filter((b) => b.id !== brand.id))
    if (brands.length === 1 && page > 1) {
      setPage((p) => Math.max(1, p - 1))
    } else {
      load(false)
    }
  }

  const handleDelete = async (targetId: number | null) => {
    if (!deleteTarget) return
    setDeleting(true)
    const brandId = deleteTarget.id
    try {
      const moved = await moveBrandProducts({ id: brandId, targetId })
      if (moved.error) throw new Error('Move failed')
      const deleted = await deleteBrand(brandId)
      if (deleted.error) throw new Error('Delete failed')
      toast.success('Brand deleted')
      setDeleteTarget(null)
      setUsage(null)
      setBrands((prev) => prev.filter((b) => b.id !== brandId))
      if (brands.length === 1 && page > 1) {
        setPage((p) => Math.max(1, p - 1))
      } else {
        load(false)
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
      load(false)
    } finally {
      setDeleting(false)
    }
  }

  return (
    <AdminLayout>
      <SEO title="Manage Brands" description="Manage brand listings. Add, edit, and organize brands." url="/admin/brands" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Brands</h1>
            <p className="text-sm text-muted-foreground">{total} brand{total !== 1 ? 's' : ''} in your catalog</p>
          </div>
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" /> Add Brand
          </Button>
        </div>

        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div className="relative flex-1 sm:max-w-xs">
            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search brands…" className="pl-8" />
          </div>
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger className="w-full sm:w-44">
              <SelectValue placeholder="All statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">All statuses</SelectItem>
              <SelectItem value="active">Active</SelectItem>
              <SelectItem value="inactive">Inactive</SelectItem>
              <SelectItem value="archived">Archived</SelectItem>
            </SelectContent>
          </Select>
        </div>

        {loading && brands.length === 0 ? (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {Array.from({ length: 8 }).map((_, i) => (
              <Skeleton key={i} className="h-40 w-full" />
            ))}
          </div>
        ) : loadError ? (
          <Card>
            <CardContent className="py-16 text-center">
              <p className="text-sm text-destructive">{loadError}</p>
              <Button variant="outline" size="sm" className="mt-3" onClick={() => load(true)}>Retry</Button>
            </CardContent>
          </Card>
        ) : brands.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <div className="mx-auto mb-2 flex justify-center text-muted-foreground">
                <Store className="h-8 w-8" />
              </div>
              <p className="text-sm text-muted-foreground">
                {search || status ? 'No brands match your filters.' : 'No brands yet. Create your first brand.'}
              </p>
            </CardContent>
          </Card>
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {brands.map((brand) => (
              <Card key={brand.id} className="group overflow-hidden">
                <div className="flex h-28 items-center justify-center bg-muted/50 p-4">
                  <SmartImage
                    src={brand.logo || ''}
                    alt={brand.name}
                    className="max-h-20 max-w-full object-contain"
                    icon={<span className="text-3xl font-bold text-muted-foreground/30">{brand.name.charAt(0)}</span>}
                  />
                </div>
                <CardContent className="p-4">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p className="truncate font-semibold">{brand.name}</p>
                      <p className="text-xs text-muted-foreground">/{brand.slug}</p>
                    </div>
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" size="icon" onClick={() => openEdit(brand)}>
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" className="text-destructive" onClick={() => confirmDelete(brand)}>
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  <p className="mt-2 line-clamp-2 text-xs text-muted-foreground">{brand.description || 'No description'}</p>
                  <div className="mt-3 flex flex-wrap items-center gap-1.5">
                    <Badge variant={brand.status === 'active' ? 'success' : brand.status === 'archived' ? 'muted' : 'warning'}>
                      {brand.status}
                    </Badge>
                    {brand.featured && <Badge variant="warning">Featured</Badge>}
                    {brand.countryOfOrigin && (
                      <span className="text-xs text-muted-foreground">{brand.countryOfOrigin}</span>
                    )}
                    {brand.website && (
                      <a href={brand.website} target="_blank" rel="noreferrer" className="ml-auto text-muted-foreground hover:text-primary">
                        <Globe className="h-4 w-4" />
                      </a>
                    )}
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}

        {totalPages > 1 && (
          <div className="flex items-center justify-between">
            <p className="text-sm text-muted-foreground">
              {total} brand{total !== 1 ? 's' : ''} · page {page} of {totalPages}
            </p>
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" disabled={page <= 1 || loading} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                Previous
              </Button>
              <Button variant="outline" size="sm" disabled={page >= totalPages || loading} onClick={() => setPage((p) => Math.min(totalPages, p + 1))}>
                Next
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editing ? `Edit "${editing.name}"` : 'Add Brand'}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <Label>Brand Name *</Label>
              <Input value={String(form.name || '')} onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))} placeholder="Samsung" />
            </div>
            <div>
              <Label>Slug</Label>
              <Input value={String(form.slug || '')} onChange={(e) => setForm((prev) => ({ ...prev, slug: e.target.value }))} placeholder="auto-generated" />
            </div>
            <div className="sm:col-span-2">
              <CropImageField
                label="Logo"
                value={String(form.logo || '')}
                onChange={(url) => setForm((prev) => ({ ...prev, logo: url }))}
                aspectKey="square"
                folder="brands"
              />
            </div>
            <div>
              <Label>Website</Label>
              <Input value={String(form.website || '')} onChange={(e) => setForm((prev) => ({ ...prev, website: e.target.value }))} placeholder="https://…" />
            </div>
            <div>
              <Label>Country / Origin</Label>
              <Input value={String(form.countryOfOrigin || '')} onChange={(e) => setForm((prev) => ({ ...prev, countryOfOrigin: e.target.value }))} placeholder="South Korea" />
            </div>
            <div className="sm:col-span-2">
              <Label>Description</Label>
              <Textarea rows={3} value={String(form.description || '')} onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))} />
            </div>
            <div className="flex items-center gap-6">
              <label className="flex items-center gap-2 text-sm">
                <Switch checked={!!form.featured} onCheckedChange={(v) => setForm((prev) => ({ ...prev, featured: v }))} />
                Featured
              </label>
              <label className="flex items-center gap-2 text-sm">
                <Switch checked={form.homepageVisibility !== false} onCheckedChange={(v) => setForm((prev) => ({ ...prev, homepageVisibility: v }))} />
                Show on homepage
              </label>
            </div>
            <div>
              <Label>Sort Order</Label>
              <Input type="number" value={String(form.sortOrder ?? 0)} onChange={(e) => setForm((prev) => ({ ...prev, sortOrder: e.target.value }))} />
            </div>
            <div>
              <Label>Status</Label>
              <Select value={String(form.status || 'active')} onValueChange={(v) => setForm((prev) => ({ ...prev, status: v }))}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="archived">Archived</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="sm:col-span-2">
              <Label>SEO Title</Label>
              <Input value={String(form.seoTitle || '')} onChange={(e) => setForm((prev) => ({ ...prev, seoTitle: e.target.value }))} />
            </div>
            <div className="sm:col-span-2">
              <Label>SEO Description</Label>
              <Textarea rows={2} value={String(form.seoDescription || '')} onChange={(e) => setForm((prev) => ({ ...prev, seoDescription: e.target.value }))} />
            </div>
            <div className="sm:col-span-2">
              <Label>SEO Keywords</Label>
              <Input value={String(form.seoKeywords || '')} onChange={(e) => setForm((prev) => ({ ...prev, seoKeywords: e.target.value }))} />
            </div>
          </div>
          <DialogFooter className="sticky bottom-0 bg-background pt-4">
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleSave} disabled={saving}>
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {editing ? 'Save Changes' : 'Create Brand'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <UsageAlertDialog
        open={!!deleteTarget && !!usage}
        onOpenChange={(v) => {
          if (!v) {
            setDeleteTarget(null)
            setUsage(null)
          }
        }}
        entityName="brand"
        itemName={deleteTarget?.name || ''}
        usageCount={usage?.count || 0}
        moveOptions={allBrands.filter((b) => b.id !== deleteTarget?.id).map((b) => ({ value: String(b.id), label: b.name }))}
        busy={deleting}
        onConfirm={handleDelete}
      />
    </AdminLayout>
  )
}

export default AdminBrandsPage
