import { useState } from 'react'
import { AlertTriangle, Loader2, ListOrdered, Pencil, Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
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
import ConfirmModal from '@/components/common/ConfirmModal'
import { SEO } from '../../components/common/SEO'
import {
  useGetAdminExpenseCategoriesQuery,
  useCreateExpenseCategoryMutation,
  useUpdateExpenseCategoryMutation,
  useDeleteExpenseCategoryMutation,
} from '@/store/services/adminProductsApi'
import type { ExpenseCategory } from '@/types/admin'

const AdminExpenseCategoriesPage = () => {
  const { data: categories = [], isLoading, refetch } = useGetAdminExpenseCategoriesQuery()
  const [createCategory] = useCreateExpenseCategoryMutation()
  const [updateCategory] = useUpdateExpenseCategoryMutation()
  const [deleteCategory] = useDeleteExpenseCategoryMutation()

  const [dialogOpen, setDialogOpen] = useState(false)
  const [editing, setEditing] = useState<ExpenseCategory | null>(null)
  const [saving, setSaving] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<ExpenseCategory | null>(null)
  const [deleting, setDeleting] = useState(false)
  const [usageCount, setUsageCount] = useState(0)
  const [usageMessage, setUsageMessage] = useState('')
  const [form, setForm] = useState<Record<string, unknown>>({})

  const sorted = [...categories].sort((a, b) => (a.sortOrder ?? 0) - (b.sortOrder ?? 0) || a.name.localeCompare(b.name))

  const openCreate = () => {
    setEditing(null)
    setForm({ status: 'active', sortOrder: (categories.length + 1) * 10 })
    setDialogOpen(true)
  }

  const openEdit = (category: ExpenseCategory) => {
    setEditing(category)
    setForm({
      name: category.name,
      description: category.description || '',
      sortOrder: category.sortOrder ?? 0,
      status: category.status || 'active',
    })
    setDialogOpen(true)
  }

  const handleSave = async () => {
    const name = String(form.name || '').trim()
    if (!name) {
      toast.error('Category name is required')
      return
    }
    setSaving(true)
    try {
      const res = editing
        ? await updateCategory({ id: editing.id, payload: { ...form, name } })
        : await createCategory({ ...form, name })
      if (res.error) throw new Error((res.error as { data?: { message?: string } }).data?.message || 'Save failed')
      toast.success(editing ? 'Category updated' : 'Category created')
      setDialogOpen(false)
      refetch()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const confirmDelete = async () => {
    if (!deleteTarget) return
    setDeleting(true)
    try {
      const res = await deleteCategory(deleteTarget.id)
      if (res.error || !res.data?.success) {
        const info = res.data
        if (info?.usageCount && info.usageCount > 0) {
          setUsageCount(info.usageCount)
          setUsageMessage(info.message || `Category is used by ${info.usageCount} expense(s)`)
          return
        }
        toast.error(info?.message || 'Delete failed')
        return
      }
      toast.success('Category deleted')
      setDeleteTarget(null)
      refetch()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
    } finally {
      setDeleting(false)
    }
  }

  return (
    <AdminLayout>
      <SEO title="Expense Categories" description="Manage expense categories used across the business." url="/admin/expenses/categories" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Expense Categories</h1>
            <p className="text-sm text-muted-foreground">{categories.length} categories for organizing expenses</p>
          </div>
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" /> Add Category
          </Button>
        </div>

        {isLoading ? (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 6 }).map((_, i) => (
              <Skeleton key={i} className="h-28 w-full" />
            ))}
          </div>
        ) : sorted.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <div className="mx-auto mb-2 flex justify-center text-muted-foreground">
                <ListOrdered className="h-8 w-8" />
              </div>
              <p className="text-sm text-muted-foreground">No categories yet. Create your first expense category.</p>
            </CardContent>
          </Card>
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {sorted.map((category) => (
              <Card key={category.id}>
                <CardContent className="p-4">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p className="truncate font-semibold">{category.name}</p>
                      <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                        {category.description || 'No description'}
                      </p>
                    </div>
                    <div className="flex items-center gap-1">
                      <Button variant="ghost" size="icon" onClick={() => openEdit(category)}>
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setDeleteTarget(category)}>
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  <div className="mt-3 flex items-center gap-1.5">
                    <Badge variant={category.status === 'active' ? 'success' : 'muted'}>{category.status}</Badge>
                    <span className="ml-auto text-xs text-muted-foreground">Order {category.sortOrder ?? 0}</span>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}

        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogContent className="max-h-[90vh] max-w-lg overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editing ? `Edit "${editing.name}"` : 'Add Expense Category'}</DialogTitle>
            </DialogHeader>
            <div className="grid gap-4">
              <div>
                <Label>Category Name *</Label>
                <Input
                  value={String(form.name || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                  placeholder="Marketing"
                />
              </div>
              <div>
                <Label>Description</Label>
                <Textarea
                  rows={3}
                  value={String(form.description || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label>Sort Order</Label>
                  <Input
                    type="number"
                    value={String(form.sortOrder ?? 0)}
                    onChange={(e) => setForm((prev) => ({ ...prev, sortOrder: e.target.value }))}
                  />
                </div>
                <div>
                  <Label>Status</Label>
                  <Select value={String(form.status || 'active')} onValueChange={(v) => setForm((prev) => ({ ...prev, status: v }))}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="inactive">Inactive</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
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

        <ConfirmModal
          open={!!deleteTarget && !usageCount}
          title="Delete category?"
          message={`This will permanently delete "${deleteTarget?.name}". Categories in use cannot be deleted.`}
          confirmText="Delete"
          loading={deleting}
          onConfirm={confirmDelete}
          onCancel={() => setDeleteTarget(null)}
        />

        <Dialog open={usageCount > 0} onOpenChange={(v) => { if (!v) setUsageCount(0) }}>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle>Category in use</DialogTitle>
            </DialogHeader>
            <div className="flex items-start gap-3">
              <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-warning" />
              <div>
                <p className="text-sm">
                  <span className="font-medium">{deleteTarget?.name}</span> is used by{' '}
                  <span className="font-medium">{usageCount}</span> expense{usageCount === 1 ? '' : 's'}.
                </p>
                <p className="mt-1 text-sm text-muted-foreground">{usageMessage}</p>
                <p className="mt-2 text-sm text-muted-foreground">
                  Edit the expenses to use a different category before deleting, or delete the related expenses first.
                </p>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => { setUsageCount(0); setDeleteTarget(null) }}>Got it</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AdminLayout>
  )
}

export default AdminExpenseCategoriesPage
