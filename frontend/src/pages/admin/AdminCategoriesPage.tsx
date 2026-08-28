import { useCallback, useEffect, useMemo, useState } from 'react'
import { ChevronDown, ChevronRight, Loader2, Pencil, Plus, Search, Tags, Trash2 } from 'lucide-react'
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
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
import { useGetCategoriesQuery } from '@/store/services/commerceApi'
import {
  useLazyGetAdminCategoriesAdminQuery,
  useCreateCategoryMutation,
  useUpdateCategoryMutation,
  useDeleteCategoryMutation,
  useMoveCategoryProductsMutation,
} from '@/store/services/adminProductsApi'
import type { Category } from '@/types'
import { toListResult } from '@/components/admin/masterDataAdapters'
import { SEO } from '../../components/common/SEO'

interface TreeRow extends Category {
  depth: number
  hasChildren: boolean
}

const buildTreeRows = (categories: Category[]): TreeRow[] => {
  const byParent = new Map<number | 'root', Category[]>()
  categories.forEach((c) => {
    const key = c.parentId ?? 'root'
    if (!byParent.has(key)) byParent.set(key, [])
    byParent.get(key)!.push(c)
  })
  const sortRows = (list: Category[]) => list.sort((a, b) => (a.sortOrder ?? 0) - (b.sortOrder ?? 0) || a.name.localeCompare(b.name))

  const rows: TreeRow[] = []
  const walk = (parentKey: number | 'root', depth: number) => {
    for (const node of sortRows(byParent.get(parentKey) || [])) {
      const children = byParent.get(node.id) || []
      rows.push({ ...node, depth, hasChildren: children.length > 0 })
      if (children.length) walk(node.id, depth + 1)
    }
  }
  walk('root', 0)
  return rows
}

const ADMIN_PAGE_SIZE = 20

const AdminCategoriesPage = () => {
  const { data: allCategories = [] } = useGetCategoriesQuery()
  const [trigger] = useLazyGetAdminCategoriesAdminQuery()
  const [createCategory] = useCreateCategoryMutation()
  const [updateCategory] = useUpdateCategoryMutation()
  const [deleteCategory] = useDeleteCategoryMutation()
  const [moveCategoryProducts] = useMoveCategoryProductsMutation()

  const [rows, setRows] = useState<TreeRow[]>([])
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState('')
  const [search, setSearch] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [total, setTotal] = useState(0)
  const [collapsed, setCollapsed] = useState<Set<number>>(new Set())

  const [dialogOpen, setDialogOpen] = useState(false)
  const [editing, setEditing] = useState<Category | null>(null)
  const [saving, setSaving] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<Category | null>(null)
  const [usage, setUsage] = useState<{ count: number; subCategories: number } | null>(null)
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
      const res = await trigger(
        {
          page,
          limit: ADMIN_PAGE_SIZE,
          search: debouncedSearch || undefined,
          status: status || undefined,
        },
        true,
      )
      if (res.error) throw new Error('Failed to load categories')
      const result = toListResult(res.data!)
      setRows(buildTreeRows(result.data))
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

  const excludedParentIds = useMemo(() => {
    if (!editing) return new Set<number>()
    const excluded = new Set<number>([editing.id])
    const collect = (id: number) => {
      allCategories.filter((c) => c.parentId === id).forEach((c) => {
        excluded.add(c.id)
        collect(c.id)
      })
    }
    collect(editing.id)
    return excluded
  }, [editing, allCategories])

  const parentOptions = useMemo(
    () => allCategories.filter((c) => !excludedParentIds.has(c.id)),
    [allCategories, excludedParentIds],
  )

  const visibleRows = useMemo(() => {
    if (collapsed.size === 0) return rows
    return rows.filter((r) => {
      let parentId = r.parentId
      while (parentId) {
        if (collapsed.has(parentId)) return false
        parentId = allCategories.find((c) => c.id === parentId)?.parentId ?? null
      }
      return true
    })
  }, [rows, collapsed, allCategories])

  const openCreate = (parentId?: number) => {
    setEditing(null)
    setForm({ status: 'active', sortOrder: 0, featured: false, homepageVisibility: true, parentId: parentId ?? 'none' })
    setDialogOpen(true)
  }

  const openEdit = (category: Category) => {
    setEditing(category)
    setForm({
      name: category.name,
      slug: category.slug,
      parentId: category.parentId ? String(category.parentId) : 'none',
      description: category.description || '',
      image: category.image || '',
      icon: category.icon || '',
      banner: category.banner || '',
      thumbnail: category.thumbnail || '',
      featured: category.featured ?? false,
      homepageVisibility: category.homepageVisibility ?? true,
      sortOrder: category.sortOrder ?? 0,
      seoTitle: category.seoTitle || '',
      seoDescription: category.seoDescription || '',
      seoKeywords: category.seoKeywords || '',
      status: category.status || 'active',
    })
    setDialogOpen(true)
  }

  const handleSave = async () => {
    if (!String(form.name || '').trim()) {
      toast.error('Category name is required')
      return
    }
    setSaving(true)
    try {
      const payload = {
        ...form,
        parentId: form.parentId === 'none' || !form.parentId ? null : Number(form.parentId),
      }
      const res = editing
        ? await updateCategory({ id: editing.id, payload })
        : await createCategory(payload)
      if (res.error) throw new Error('Save failed')
      toast.success(editing ? 'Category updated' : 'Category created')
      setDialogOpen(false)
      load(false)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async (category: Category) => {
    const res = await deleteCategory(category.id)
    if (res.error) {
      const info = (res.error as { data?: { data?: { usageCount?: number; subCategories?: number } } }).data?.data
      if (info && (info.usageCount !== undefined || info.subCategories !== undefined)) {
        setUsage({ count: info.usageCount || 0, subCategories: info.subCategories || 0 })
        setDeleteTarget(category)
        return
      }
      toast.error((res.error as { data?: { message?: string } }).data?.message || 'Delete failed')
      return
    }
    toast.success('Category deleted')
    setRows((prev) => prev.filter((c) => c.id !== category.id))
    if (rows.length === 1 && page > 1) {
      setPage((p) => Math.max(1, p - 1))
    } else {
      load(false)
    }
  }

  const handleDelete = async (targetId: number | null) => {
    if (!deleteTarget) return
    setDeleting(true)
    const categoryId = deleteTarget.id
    try {
      const moved = await moveCategoryProducts({ id: categoryId, targetId })
      if (moved.error) throw new Error('Move failed')
      const deleted = await deleteCategory(categoryId)
      if (deleted.error) throw new Error('Delete failed')
      toast.success('Category deleted')
      setDeleteTarget(null)
      setUsage(null)
      setRows((prev) => prev.filter((c) => c.id !== categoryId))
      if (rows.length === 1 && page > 1) {
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

  const parentName = (id?: number | null) => allCategories.find((c) => c.id === id)?.name

  const renderParentSelect = () => (
    <div>
      <Label>Parent Category</Label>
      <Select value={String(form.parentId ?? 'none')} onValueChange={(v) => setForm((prev) => ({ ...prev, parentId: v }))}>
        <SelectTrigger className="w-full">
          <SelectValue placeholder="Top-level (no parent)" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="none">Top-level (no parent)</SelectItem>
          {parentOptions.map((c) => (
            <SelectItem key={c.id} value={String(c.id)}>
              {c.parentId ? `${parentName(c.parentId)} / ` : ''}
              {c.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  )

  return (
    <AdminLayout>
      <SEO title="Manage Categories" description="Organize your product categories. Add, edit, and manage categories." url="/admin/categories" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Categories</h1>
            <p className="text-sm text-muted-foreground">
              {total} categor{total !== 1 ? 'ies' : 'y'} · hierarchical (category → sub-category → child)
            </p>
          </div>
          <Button onClick={() => openCreate()}>
            <Plus className="h-4 w-4" /> Add Category
          </Button>
        </div>

        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div className="relative flex-1 sm:max-w-xs">
            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search categories…" className="pl-8" />
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

        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Category</TableHead>
                  <TableHead>Parent</TableHead>
                  <TableHead className="text-center">Featured</TableHead>
                  <TableHead className="text-center">Homepage</TableHead>
                  <TableHead>Order</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="w-[100px] text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading && rows.length === 0 ? (
                  Array.from({ length: 5 }).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell colSpan={7}>
                        <Skeleton className="h-10 w-full" />
                      </TableCell>
                    </TableRow>
                  ))
                ) : loadError ? (
                  <TableRow>
                    <TableCell colSpan={7} className="py-12 text-center">
                      <p className="text-sm text-destructive">{loadError}</p>
                      <Button variant="outline" size="sm" className="mt-3" onClick={() => load(true)}>
                        Retry
                      </Button>
                    </TableCell>
                  </TableRow>
                ) : visibleRows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="py-12 text-center">
                      <div className="mx-auto mb-2 flex justify-center text-muted-foreground">
                        <Tags className="h-8 w-8" />
                      </div>
                      <p className="text-sm text-muted-foreground">
                        {search || status ? 'No categories match your filters.' : 'No categories yet. Create your first category.'}
                      </p>
                    </TableCell>
                  </TableRow>
                ) : (
                  visibleRows.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell>
                        <div className="flex items-center gap-2" style={{ paddingLeft: `${row.depth * 20}px` }}>
                          {row.hasChildren ? (
                            <button
                              type="button"
                              onClick={() =>
                                setCollapsed((prev) => {
                                  const next = new Set(prev)
                                  if (next.has(row.id)) next.delete(row.id)
                                  else next.add(row.id)
                                  return next
                                })
                              }
                              className="text-muted-foreground hover:text-foreground"
                            >
                              {collapsed.has(row.id) ? <ChevronRight className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                            </button>
                          ) : (
                            <span className="w-4" />
                          )}
                          <SmartImage
                            src={row.image || ''}
                            alt=""
                            className="h-8 w-8 rounded-md object-cover"
                            icon={<Tags className="h-3.5 w-3.5" />}
                          />
                          <span className="font-medium">{row.name}</span>
                          <Badge variant="muted" className="ml-1 hidden sm:inline-flex">
                            /{row.slug}
                          </Badge>
                        </div>
                      </TableCell>
                      <TableCell>
                        {row.parentId ? (
                          <span className="text-sm text-muted-foreground">{parentName(row.parentId) || '—'}</span>
                        ) : (
                          <span className="text-xs text-muted-foreground/60">Top-level</span>
                        )}
                      </TableCell>
                      <TableCell className="text-center">
                        {row.featured ? <Badge variant="warning">Featured</Badge> : <span className="text-xs text-muted-foreground">—</span>}
                      </TableCell>
                      <TableCell className="text-center">
                        {row.homepageVisibility === false ? (
                          <span className="text-xs text-muted-foreground">Hidden</span>
                        ) : (
                          <span className="text-xs font-medium text-green-600">Visible</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <span className="text-sm text-muted-foreground">{row.sortOrder ?? 0}</span>
                      </TableCell>
                      <TableCell>
                        <Badge variant={row.status === 'active' ? 'success' : row.status === 'archived' ? 'muted' : 'warning'}>
                          {row.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="icon" title="Add sub-category" onClick={() => openCreate(row.id)}>
                            <Plus className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" onClick={() => openEdit(row)}>
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" className="text-destructive" onClick={() => confirmDelete(row)}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        {totalPages > 1 && (
          <div className="flex items-center justify-between">
            <p className="text-sm text-muted-foreground">
              {total} categor{total !== 1 ? 'ies' : 'y'} · page {page} of {totalPages}
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
            <DialogTitle>{editing ? `Edit "${editing.name}"` : 'Add Category'}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <Label>Category Name *</Label>
              <Input value={String(form.name || '')} onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))} placeholder="Electronics" />
            </div>
            <div>
              <Label>Slug</Label>
              <Input value={String(form.slug || '')} onChange={(e) => setForm((prev) => ({ ...prev, slug: e.target.value }))} placeholder="auto-generated" />
            </div>
            <div className="sm:col-span-2">{renderParentSelect()}</div>
            <div className="sm:col-span-2">
              <CropImageField
                label="Category Image"
                value={String(form.image || '')}
                onChange={(url) => setForm((prev) => ({ ...prev, image: url }))}
                aspectKey="square"
                folder="categories"
              />
            </div>
            <div className="sm:col-span-2">
              <CropImageField
                label="Banner (16:9)"
                value={String(form.banner || '')}
                onChange={(url) => setForm((prev) => ({ ...prev, banner: url }))}
                aspectKey="banner"
                folder="categories"
              />
            </div>
            <div>
              <Label>Icon URL</Label>
              <Input value={String(form.icon || '')} onChange={(e) => setForm((prev) => ({ ...prev, icon: e.target.value }))} placeholder="https://…" />
            </div>
            <div>
              <Label>Thumbnail URL</Label>
              <Input value={String(form.thumbnail || '')} onChange={(e) => setForm((prev) => ({ ...prev, thumbnail: e.target.value }))} placeholder="https://…" />
            </div>
            <div className="sm:col-span-2">
              <Label>Description</Label>
              <Textarea rows={2} value={String(form.description || '')} onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))} />
            </div>
            <div className="flex items-center gap-6">
              <label className="flex items-center gap-2 text-sm">
                <Switch
                  checked={!!form.featured}
                  onCheckedChange={(v) => setForm((prev) => ({ ...prev, featured: v }))}
                />
                Featured
              </label>
              <label className="flex items-center gap-2 text-sm">
                <Switch
                  checked={form.homepageVisibility !== false}
                  onCheckedChange={(v) => setForm((prev) => ({ ...prev, homepageVisibility: v }))}
                />
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
              <Input value={String(form.seoKeywords || '')} onChange={(e) => setForm((prev) => ({ ...prev, seoKeywords: e.target.value }))} placeholder="electronics, gadgets, smart devices" />
            </div>
          </div>
          <DialogFooter className="sticky bottom-0 bg-background pt-4">
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleSave} disabled={saving}>
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {editing ? 'Save Changes' : 'Create Category'}
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
        entityName="category"
        itemName={deleteTarget?.name || ''}
        usageCount={usage?.count || 0}
        subCategories={usage?.subCategories || 0}
        moveOptions={allCategories
          .filter((c) => c.id !== deleteTarget?.id)
          .map((c) => ({ value: String(c.id), label: `${c.parentId ? `${parentName(c.parentId)} / ` : ''}${c.name}` }))}
        busy={deleting}
        onConfirm={handleDelete}
      />
    </AdminLayout>
  )
}

export default AdminCategoriesPage
