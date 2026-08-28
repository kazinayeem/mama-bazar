import { useCallback, useEffect, useRef, useState } from 'react'
import { ImagePlus, Loader2, MoreHorizontal, Pencil, Plus, Search, Trash2, X } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
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
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import SmartImage from '@/components/common/SmartImage'
import UsageAlertDialog from './UsageAlertDialog'
import MediaPicker from './MediaPicker'

export interface CatalogField {
  key: string
  label: string
  type: 'text' | 'number' | 'textarea' | 'select' | 'switch' | 'image' | 'hex' | 'date'
  placeholder?: string
  options?: Array<{ value: string; label: string }>
  fullWidth?: boolean
  required?: boolean
}

export interface CatalogColumn<T> {
  key: string
  label: string
  className?: string
  render?: (item: T) => React.ReactNode
}

export interface CatalogPagination {
  page: number
  limit: number
  total: number
  totalPages: number
}

export interface CatalogListResult<T> {
  data: T[]
  pagination?: CatalogPagination
}

export interface CatalogRemoveResult {
  usageCount?: number
  subCategories?: number
  message?: string
}

interface CatalogCrudPageProps<T> {
  title: string
  description: string
  emptyMessage: string
  icon: React.ReactNode
  fields: CatalogField[]
  columns: CatalogColumn<T>[]
  api: {
    list: (params?: { page?: number; limit?: number; search?: string; status?: string }) => Promise<CatalogListResult<T>>
    create: (payload: Record<string, unknown>) => Promise<unknown>
    update: (id: number, payload: Record<string, unknown>) => Promise<unknown>
    remove: (id: number) => Promise<CatalogRemoveResult | undefined>
    move?: (id: number, targetId: number | null) => Promise<unknown>
  }
  moveOptions?: Array<{ value: string; label: string }>
  itemKey?: (item: T) => string
  searchable?: boolean
  statusFilter?: boolean
}

const DEBOUNCE_MS = 400

const CatalogCrudPage = <T extends { id: number }>({
  title,
  description,
  emptyMessage,
  icon,
  fields,
  columns,
  api,
  moveOptions = [],
  itemKey,
  searchable = true,
  statusFilter = true,
}: CatalogCrudPageProps<T>) => {
  const [items, setItems] = useState<T[]>([])
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState('')
  const [dialogOpen, setDialogOpen] = useState(false)
  const [editing, setEditing] = useState<T | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<T | null>(null)
  const [usage, setUsage] = useState<{ count: number; subCategories: number } | null>(null)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [form, setForm] = useState<Record<string, unknown>>({})
  const [pickerFor, setPickerFor] = useState<string | null>(null)

  const [search, setSearch] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [pagination, setPagination] = useState<CatalogPagination | null>(null)

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search.trim()), DEBOUNCE_MS)
    return () => clearTimeout(timer)
  }, [search])

  const apiRef = useRef(api)
  apiRef.current = api

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const load = useCallback(async (isInitial = false) => {
    if (isInitial) setLoading(true)
    setLoadError('')
    try {
      const result = await apiRef.current.list({
        page,
        limit: pagination?.limit || 20,
        search: debouncedSearch || undefined,
        status: status || undefined,
      })
      if (Array.isArray(result)) {
        setItems(result as unknown as T[])
        setPagination(null)
      } else {
        setItems(result.data)
        setPagination(result.pagination || null)
      }
    } catch (err) {
      setLoadError(err instanceof Error ? err.message : 'Failed to load')
    } finally {
      setLoading(false)
    }
  }, [page, debouncedSearch, status, pagination?.limit])

  useEffect(() => {
    load(true)
  }, [load])

  const openCreate = () => {
    const initial: Record<string, unknown> = { status: 'active' }
    fields.forEach((f) => {
      if (f.type === 'switch') initial[f.key] = false
      if (f.type === 'number') initial[f.key] = 0
    })
    setForm(initial)
    setEditing(null)
    setDialogOpen(true)
  }

  const openEdit = (item: T) => {
    const initial: Record<string, unknown> = {}
    fields.forEach((f) => {
      const raw = (item as Record<string, unknown>)[f.key]
      if (f.type === 'switch') {
        initial[f.key] = !!raw
      } else if (f.type === 'number') {
        initial[f.key] = typeof raw === 'number' ? raw : Number(raw || 0)
      } else if (f.type === 'date') {
        initial[f.key] = raw ?? null
      } else {
        // Nullable DB columns return null — normalize to '' so string fields
        // never render "null" or submit a rejected `null` to the API.
        initial[f.key] = raw ?? ''
      }
    })
    setForm(initial)
    setEditing(item)
    setDialogOpen(true)
  }

  const handleSave = async () => {
    const missing = fields.find((f) => f.required && !form[f.key])
    if (missing) {
      toast.error(`${missing.label} is required`)
      return
    }
    setSaving(true)
    try {
      // An empty image field means "no image" — send null so it persists as
      // cleared, otherwise zod (string) would reject it.
      const payload: Record<string, unknown> = {}
      for (const [key, value] of Object.entries(form)) {
        payload[key] = value === '' && fields.some((f) => f.key === key && f.type === 'image') ? null : value
      }
      if (editing) {
        await api.update(editing.id, payload)
        toast.success(`${title.slice(0, -1)} updated`)
      } else {
        await api.create(payload)
        toast.success(`${title.slice(0, -1)} created`)
      }
      setDialogOpen(false)
      load()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = async (targetId: number | null) => {
    if (!deleteTarget) return
    setDeleting(true)
    const targetIdNum = deleteTarget.id
    try {
      if (targetId !== null && api.move) {
        await api.move(targetIdNum, targetId)
      }
      await api.remove(targetIdNum)
      toast.success(`${title.slice(0, -1)} deleted`)
      setDeleteTarget(null)
      setUsage(null)
      setItems((prev) => prev.filter((i) => i.id !== targetIdNum))
      if (items.length === 1 && page > 1) {
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

  const confirmDelete = async (item: T) => {
    try {
      const result = await api.remove(item.id)
      if (result?.usageCount) {
        setUsage({ count: result.usageCount, subCategories: result.subCategories || 0 })
        setDeleteTarget(item)
        return
      }
      toast.success(`${title.slice(0, -1)} deleted`)
      setItems((prev) => prev.filter((i) => i.id !== item.id))
      if (items.length === 1 && page > 1) {
        setPage((p) => Math.max(1, p - 1))
      } else {
        load(false)
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
      load(false)
    }
  }

  const renderField = (field: CatalogField) => {
    const value = form[field.key]
    const cls = field.fullWidth ? 'sm:col-span-2' : ''
    switch (field.type) {
      case 'number':
        return (
          <div key={field.key} className={cls}>
            <Label>{field.label}</Label>
            <Input
              type="number"
              placeholder={field.placeholder}
              value={value === undefined ? '' : String(value)}
              onChange={(e) => setForm((prev) => ({ ...prev, [field.key]: e.target.value }))}
            />
          </div>
        )
      case 'date':
        return (
          <div key={field.key} className={cls}>
            <Label>{field.label}</Label>
            <Input
              type="datetime-local"
              placeholder={field.placeholder}
              value={value ? String(value).slice(0, 16) : ''}
              onChange={(e) => setForm((prev) => ({ ...prev, [field.key]: e.target.value || null }))}
            />
          </div>
        )
      case 'textarea':
        return (
          <div key={field.key} className={cls}>
            <Label>{field.label}</Label>
            <Textarea
              rows={3}
              placeholder={field.placeholder}
              value={value === undefined ? '' : String(value)}
              onChange={(e) => setForm((prev) => ({ ...prev, [field.key]: e.target.value }))}
            />
          </div>
        )
      case 'select':
        return (
          <div key={field.key} className={cls}>
            <Label>{field.label}</Label>
            <Select
              value={value == null || value === '' ? '' : String(value)}
              onValueChange={(v) => setForm((prev) => ({ ...prev, [field.key]: v }))}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder={field.placeholder} />
              </SelectTrigger>
              <SelectContent>
                {(field.options || []).map((o) => (
                  <SelectItem key={o.value} value={o.value}>
                    {o.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        )
      case 'switch':
        return (
          <div key={field.key} className="flex items-center justify-between rounded-md border p-3">
            <div>
              <p className="text-sm font-medium">{field.label}</p>
              {field.placeholder && <p className="text-xs text-muted-foreground">{field.placeholder}</p>}
            </div>
            <Switch checked={!!value} onCheckedChange={(v) => setForm((prev) => ({ ...prev, [field.key]: v }))} />
          </div>
        )
      case 'image':
        return (
          <div key={field.key} className={cls}>
            <Label>{field.label}</Label>
            {value ? (
              <div className="group relative w-24 overflow-hidden rounded-md border bg-muted">
                <SmartImage src={String(value)} alt="" className="aspect-square w-full object-cover" />
                <button
                  type="button"
                  onClick={() => setForm((prev) => ({ ...prev, [field.key]: '' }))}
                  className="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
                >
                  <X className="h-3 w-3" />
                </button>
              </div>
            ) : (
              <button
                type="button"
                onClick={() => setPickerFor(field.key)}
                className="flex h-24 w-24 flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed text-muted-foreground transition-colors hover:border-primary hover:text-primary"
              >
                <ImagePlus className="h-5 w-5" />
                <span className="text-xs">Pick image</span>
              </button>
            )}
          </div>
        )
      case 'hex':
        return (
          <div key={field.key} className={cls}>
            <Label>{field.label}</Label>
            <div className="flex items-center gap-2">
              <input
                type="color"
                value={/^#[0-9a-fA-F]{6}$/.test(String(value || '')) ? String(value) : '#000000'}
                onChange={(e) => setForm((prev) => ({ ...prev, [field.key]: e.target.value }))}
                className="h-9 w-10 cursor-pointer rounded border bg-transparent p-1"
              />
              <Input
                placeholder="#RRGGBB"
                value={value === undefined ? '' : String(value)}
                onChange={(e) => setForm((prev) => ({ ...prev, [field.key]: e.target.value }))}
              />
            </div>
          </div>
        )
      default:
        return (
          <div key={field.key} className={cls}>
            <Label>
              {field.label}
              {field.required && <span className="ml-0.5 text-destructive">*</span>}
            </Label>
            <Input
              placeholder={field.placeholder}
              value={value === undefined ? '' : String(value)}
              onChange={(e) => setForm((prev) => ({ ...prev, [field.key]: e.target.value }))}
            />
          </div>
        )
    }
  }

  return (
    <AdminLayout>
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
            <p className="text-sm text-muted-foreground">{description}</p>
          </div>
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" /> Add {title.slice(0, -1)}
          </Button>
        </div>

        {(searchable || statusFilter) && (
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
            {searchable && (
              <div className="relative flex-1 sm:max-w-xs">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder={`Search ${title.toLowerCase()}…`}
                  className="pl-8"
                />
              </div>
            )}
            {statusFilter && (
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
            )}
          </div>
        )}

        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  {columns.map((col) => (
                    <TableHead key={col.key} className={col.className}>
                      {col.label}
                    </TableHead>
                  ))}
                  <TableHead className="w-[70px] text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading && items.length === 0 ? (
                  Array.from({ length: 5 }).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell colSpan={columns.length + 1}>
                        <Skeleton className="h-10 w-full" />
                      </TableCell>
                    </TableRow>
                  ))
                ) : loadError ? (
                  <TableRow>
                    <TableCell colSpan={columns.length + 1} className="py-12 text-center">
                      <p className="text-sm text-destructive">{loadError}</p>
                      <Button variant="outline" size="sm" className="mt-3" onClick={() => load(true)}>
                        Retry
                      </Button>
                    </TableCell>
                  </TableRow>
                ) : items.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={columns.length + 1} className="py-12 text-center">
                      <div className="mx-auto mb-2 flex justify-center text-muted-foreground">{icon}</div>
                      <p className="text-sm text-muted-foreground">{emptyMessage}</p>
                    </TableCell>
                  </TableRow>
                ) : (
                  items.map((item) => (
                    <TableRow key={itemKey ? itemKey(item) : String(item.id)}>
                      {columns.map((col) => (
                        <TableCell key={col.key} className={col.className}>
                          {col.render ? col.render(item) : String((item as Record<string, unknown>)[col.key] ?? '—')}
                        </TableCell>
                      ))}
                      <TableCell className="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => openEdit(item)} className="cursor-pointer">
                              <Pencil className="mr-2 h-4 w-4" /> Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onClick={() => confirmDelete(item)}
                              className="cursor-pointer text-destructive focus:text-destructive"
                            >
                              <Trash2 className="mr-2 h-4 w-4" /> Delete
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        {pagination && pagination.totalPages > 1 && (
          <div className="flex items-center justify-between">
            <p className="text-sm text-muted-foreground">
              {pagination.total} item{pagination.total !== 1 ? 's' : ''} · page {pagination.page} of {pagination.totalPages}
            </p>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={page <= 1 || loading}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                Previous
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={page >= pagination.totalPages || loading}
                onClick={() => setPage((p) => Math.min(pagination.totalPages, p + 1))}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editing ? `Edit ${title.slice(0, -1)}` : `Add ${title.slice(0, -1)}`}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-3 sm:grid-cols-2">{fields.map(renderField)}</div>
          <DialogFooter className="sticky bottom-0 bg-background pt-4">
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={handleSave} disabled={saving}>
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {editing ? 'Save Changes' : 'Create'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <MediaPicker
        open={!!pickerFor}
        onOpenChange={(v) => !v && setPickerFor(null)}
        onSelect={(assets) => {
          if (pickerFor && assets[0]) {
            setForm((prev) => ({ ...prev, [pickerFor]: assets[0].url }))
          }
          setPickerFor(null)
        }}
        multiple={false}
      />

      <UsageAlertDialog
        open={!!deleteTarget && !!usage}
        onOpenChange={(v) => {
          if (!v) {
            setDeleteTarget(null)
            setUsage(null)
          }
        }}
        entityName={title.slice(0, -1).toLowerCase()}
        itemName={deleteTarget ? String((deleteTarget as Record<string, unknown>).name || '') : ''}
        usageCount={usage?.count || 0}
        subCategories={usage?.subCategories || 0}
        moveOptions={moveOptions}
        moveDisabled={!api.move}
        busy={deleting}
        onConfirm={handleDelete}
      />
    </AdminLayout>
  )
}

export default CatalogCrudPage
