import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  Download,
  Eye,
  Loader2,
  Pencil,
  Plus,
  ReceiptText,
  Search,
  Trash2,
  Wallet,
} from 'lucide-react'
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import ConfirmModal from '@/components/common/ConfirmModal'
import PaginationControls from '@/components/common/PaginationControls'
import { SEO } from '../../components/common/SEO'
import {
  useGetAdminExpensesQuery,
  useGetAdminExpenseCategoriesQuery,
  useGetExpenseTeamMembersQuery,
  useGetExpenseSummaryQuery,
  useCreateExpenseMutation,
  useUpdateExpenseMutation,
  useDeleteExpenseMutation,
  useExportExpensesCsvMutation,
} from '@/store/services/adminProductsApi'
import type { Expense, ExpenseFilters, ExpenseInput } from '@/types/admin'
import { currency, formatNumber } from '@/lib/format'

const PAGE_SIZE = 20

const statusVariant: Record<string, 'success' | 'warning' | 'destructive' | 'muted'> = {
  approved: 'success',
  pending: 'warning',
  rejected: 'destructive',
}

const dateToLocal = (d: Date) => {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const AdminExpensesPage = () => {
  const { data: summary } = useGetExpenseSummaryQuery()
  const { data: categories = [] } = useGetAdminExpenseCategoriesQuery()
  const { data: members = [] } = useGetExpenseTeamMembersQuery()
  const [createExpense] = useCreateExpenseMutation()
  const [updateExpense] = useUpdateExpenseMutation()
  const [deleteExpense] = useDeleteExpenseMutation()
  const [exportCsv] = useExportExpensesCsvMutation()

  const [search, setSearch] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [status, setStatus] = useState('')
  const [memberId, setMemberId] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [paymentMethod, setPaymentMethod] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [amountMin, setAmountMin] = useState('')
  const [amountMax, setAmountMax] = useState('')
  const [page, setPage] = useState(1)

  const [dialogOpen, setDialogOpen] = useState(false)
  const [viewing, setViewing] = useState<Expense | null>(null)
  const [editing, setEditing] = useState<Expense | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Expense | null>(null)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [exporting, setExporting] = useState(false)
  const [form, setForm] = useState<Record<string, unknown>>({})

  const filters = useMemo(
    () =>
      ({
        page,
        limit: PAGE_SIZE,
        search: debouncedSearch || undefined,
        status: status || undefined,
        memberId: memberId || undefined,
        categoryId: categoryId || undefined,
        paymentMethod: paymentMethod || undefined,
        dateFrom: dateFrom || undefined,
        dateTo: dateTo || undefined,
        amountMin: amountMin || undefined,
        amountMax: amountMax || undefined,
      }) satisfies ExpenseFilters,
    [page, debouncedSearch, status, memberId, categoryId, paymentMethod, dateFrom, dateTo, amountMin, amountMax],
  )

  const { data: result, isLoading, isFetching, refetch } = useGetAdminExpensesQuery(filters)

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search.trim()), 400)
    return () => clearTimeout(timer)
  }, [search])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status, memberId, categoryId, paymentMethod, dateFrom, dateTo, amountMin, amountMax])

  const hasFilters = Boolean(
    debouncedSearch || status || memberId || categoryId || paymentMethod || dateFrom || dateTo || amountMin || amountMax,
  )

  const clearFilters = () => {
    setSearch('')
    setStatus('')
    setMemberId('')
    setCategoryId('')
    setPaymentMethod('')
    setDateFrom('')
    setDateTo('')
    setAmountMin('')
    setAmountMax('')
    setPage(1)
  }

  const openCreate = () => {
    setEditing(null)
    setViewing(null)
    setForm({
      amount: '',
      paymentMethod: 'cash',
      status: 'approved',
      expenseDate: dateToLocal(new Date()),
    })
    setDialogOpen(true)
  }

  const openEdit = (expense: Expense) => {
    setEditing(expense)
    setViewing(null)
    setForm({
      title: expense.title,
      description: expense.description || '',
      categoryId: expense.categoryId ? String(expense.categoryId) : '',
      amount: expense.amount,
      paymentMethod: expense.paymentMethod || 'cash',
      memberId: expense.memberId ? String(expense.memberId) : '',
      expenseDate: expense.expenseDate ? expense.expenseDate.slice(0, 10) : dateToLocal(new Date()),
      referenceNumber: expense.referenceNumber || '',
      attachmentUrl: expense.attachmentUrl || '',
      notes: expense.notes || '',
      status: expense.status || 'approved',
    })
    setDialogOpen(true)
  }

  const handleSave = async () => {
    const title = String(form.title || '').trim()
    if (!title) {
      toast.error('Title is required')
      return
    }
    const amount = Number(form.amount)
    if (Number.isNaN(amount) || amount <= 0) {
      toast.error('Amount must be greater than 0')
      return
    }
    if (!form.expenseDate) {
      toast.error('Expense date is required')
      return
    }
    const payload: ExpenseInput = {
      title,
      description: String(form.description || '').trim() || null,
      categoryId: form.categoryId ? Number(form.categoryId) : null,
      amount,
      paymentMethod: String(form.paymentMethod || 'cash'),
      memberId: form.memberId ? Number(form.memberId) : null,
      expenseDate: String(form.expenseDate),
      referenceNumber: String(form.referenceNumber || '').trim() || null,
      attachmentUrl: String(form.attachmentUrl || '').trim() || null,
      notes: String(form.notes || '').trim() || null,
      status: (String(form.status) as ExpenseInput['status']) || 'approved',
    }
    setSaving(true)
    try {
      const res = editing ? await updateExpense({ id: editing.id, payload }) : await createExpense(payload)
      if (res.error) throw new Error((res.error as { data?: { message?: string } }).data?.message || 'Save failed')
      toast.success(editing ? 'Expense updated' : 'Expense created')
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
      const res = await deleteExpense(deleteTarget.id)
      if (res.error) throw new Error((res.error as { data?: { message?: string } }).data?.message || 'Delete failed')
      toast.success('Expense deleted')
      setDeleteTarget(null)
      refetch()
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
    } finally {
      setDeleting(false)
    }
  }

  const handleExport = async () => {
    setExporting(true)
    try {
      const res = await exportCsv(filters)
      if (res.error || !res.data?.csv) throw new Error('Export failed')
      const blob = new Blob(['\ufeff' + res.data.csv], { type: 'text/csv;charset=utf-8;' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `expenses-${dateToLocal(new Date())}.csv`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)
      toast.success(`Exported ${res.data.count} expense${res.data.count === 1 ? '' : 's'}`)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Export failed')
    } finally {
      setExporting(false)
    }
  }

  const summaryCards = [
    { label: 'Total Expenses', value: summary?.total ?? 0, sub: `${formatNumber(summary?.totalCount ?? 0)} expenses`, icon: Wallet },
    { label: 'This Month', value: summary?.thisMonth ?? 0, sub: `${formatNumber(summary?.thisMonthCount ?? 0)} expenses`, icon: ReceiptText },
    { label: 'This Week', value: summary?.thisWeek ?? 0, sub: `${formatNumber(summary?.thisWeekCount ?? 0)} expenses`, icon: ReceiptText },
    { label: 'Today', value: summary?.today ?? 0, sub: `${formatNumber(summary?.todayCount ?? 0)} expenses`, icon: ReceiptText },
  ]

  return (
    <AdminLayout>
      <SEO title="Expenses" description="Manage business expenses, costs and payment records." url="/admin/expenses" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Expenses</h1>
            <p className="text-sm text-muted-foreground">Track business spending and operating costs</p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" onClick={handleExport} disabled={exporting}>
              {exporting ? <Loader2 className="h-4 w-4 animate-spin" /> : <Download className="h-4 w-4" />}
              Export CSV
            </Button>
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" /> Add Expense
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          {summaryCards.map((card) => (
            <Card key={card.label}>
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <p className="text-xs text-muted-foreground">{card.label}</p>
                  <card.icon className="h-4 w-4 text-muted-foreground" />
                </div>
                <p className="mt-1 text-xl font-bold">{currency(card.value)}</p>
                <p className="text-xs text-muted-foreground">{card.sub}</p>
              </CardContent>
            </Card>
          ))}
        </div>

        <Card>
          <CardContent className="p-4">
            <div className="grid gap-3 lg:grid-cols-4">
              <div className="relative lg:col-span-2">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder="Search title, description, member, reference, vendor…"
                  className="pl-8"
                />
              </div>
              <Select value={status} onValueChange={setStatus}>
                <SelectTrigger>
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All statuses</SelectItem>
                  <SelectItem value="approved">Approved</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="rejected">Rejected</SelectItem>
                </SelectContent>
              </Select>
              <Select value={memberId} onValueChange={setMemberId}>
                <SelectTrigger>
                  <SelectValue placeholder="All members" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All members</SelectItem>
                  {members.map((m) => (
                    <SelectItem key={m.id} value={String(m.id)}>{m.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select value={categoryId} onValueChange={setCategoryId}>
                <SelectTrigger>
                  <SelectValue placeholder="All categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All categories</SelectItem>
                  {categories.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Select value={paymentMethod} onValueChange={setPaymentMethod}>
                <SelectTrigger>
                  <SelectValue placeholder="All methods" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All methods</SelectItem>
                  {['cash', 'bkash', 'nagad', 'rocket', 'card', 'bank', 'cheque', 'mobile banking', 'other'].map((m) => (
                    <SelectItem key={m} value={m}>{m}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} aria-label="From date" />
              <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} aria-label="To date" />
              <Input type="number" min="0" placeholder="Min amount" value={amountMin} onChange={(e) => setAmountMin(e.target.value)} aria-label="Min amount" />
              <Input type="number" min="0" placeholder="Max amount" value={amountMax} onChange={(e) => setAmountMax(e.target.value)} aria-label="Max amount" />
              <div className="lg:col-span-2 flex items-center gap-2">
                {hasFilters && (
                  <Button variant="ghost" size="sm" onClick={clearFilters}>Clear filters</Button>
                )}
                {(isFetching) && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />}
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-0">
            {isLoading ? (
              <div className="space-y-2 p-4">
                {Array.from({ length: 6 }).map((_, i) => (
                  <Skeleton key={i} className="h-12 w-full" />
                ))}
              </div>
            ) : !result?.data.length ? (
              <div className="py-16 text-center">
                <div className="mx-auto mb-2 flex justify-center text-muted-foreground">
                  <ReceiptText className="h-8 w-8" />
                </div>
                <p className="text-sm text-muted-foreground">
                  {hasFilters ? 'No expenses match your filters.' : 'No expenses yet. Record your first expense.'}
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Expense</TableHead>
                      <TableHead>Category</TableHead>
                      <TableHead>Member</TableHead>
                      <TableHead className="text-right">Amount</TableHead>
                      <TableHead>Method</TableHead>
                      <TableHead>Date</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {result.data.map((expense) => (
                      <TableRow key={expense.id}>
                        <TableCell>
                          <button type="button" className="text-left" onClick={() => setViewing(expense)}>
                            <p className="font-medium hover:underline">{expense.title}</p>
                            {expense.referenceNumber && (
                              <p className="text-xs text-muted-foreground">#{expense.referenceNumber}</p>
                            )}
                          </button>
                        </TableCell>
                        <TableCell>
                          <span className="text-sm">{expense.categoryName || '—'}</span>
                        </TableCell>
                        <TableCell>
                          <span className="text-sm">{expense.memberName || '—'}</span>
                        </TableCell>
                        <TableCell className="text-right font-semibold">{currency(expense.amount)}</TableCell>
                        <TableCell>
                          <span className="text-sm capitalize">{expense.paymentMethod || '—'}</span>
                        </TableCell>
                        <TableCell>
                          <span className="text-sm whitespace-nowrap">
                            {expense.expenseDate ? expense.expenseDate.slice(0, 10) : '—'}
                          </span>
                        </TableCell>
                        <TableCell>
                          <Badge variant={statusVariant[expense.status] || 'muted'}>{expense.status}</Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center justify-end gap-1">
                            <Button variant="ghost" size="icon" onClick={() => setViewing(expense)} title="View">
                              <Eye className="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="icon" onClick={() => openEdit(expense)} title="Edit">
                              <Pencil className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-destructive"
                              onClick={() => setDeleteTarget(expense)}
                              title="Delete"
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>

        {(result?.totalPages ?? 1) > 1 && (
          <div className="flex flex-wrap items-center justify-between gap-2">
            <p className="text-sm text-muted-foreground">
              {result?.total ?? 0} expense{(result?.total ?? 0) === 1 ? '' : 's'} · page {result?.page ?? 1} of {result?.totalPages ?? 1}
            </p>
            <PaginationControls currentPage={result?.page ?? 1} totalPages={result?.totalPages ?? 1} onPageChange={setPage} />
          </div>
        )}

        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editing ? `Edit "${editing.title}"` : 'Add Expense'}</DialogTitle>
            </DialogHeader>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <Label>Title *</Label>
                <Input
                  value={String(form.title || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
                  placeholder="Facebook Ads"
                />
              </div>
              <div>
                <Label>Amount *</Label>
                <Input
                  type="number"
                  min="0"
                  step="0.01"
                  value={String(form.amount ?? '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, amount: e.target.value }))}
                  placeholder="5000"
                />
              </div>
              <div>
                <Label>Expense Date *</Label>
                <Input
                  type="date"
                  value={String(form.expenseDate || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, expenseDate: e.target.value }))}
                />
              </div>
              <div>
                <Label>Category</Label>
                <Select
                  value={String(form.categoryId || '')}
                  onValueChange={(v) => setForm((prev) => ({ ...prev, categoryId: v }))}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    {categories.map((c) => (
                      <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Member</Label>
                <Select value={String(form.memberId || '')} onValueChange={(v) => setForm((prev) => ({ ...prev, memberId: v }))}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select member" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    {members.map((m) => (
                      <SelectItem key={m.id} value={String(m.id)}>{m.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Payment Method</Label>
                <Select
                  value={String(form.paymentMethod || 'cash')}
                  onValueChange={(v) => setForm((prev) => ({ ...prev, paymentMethod: v }))}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {['cash', 'bkash', 'nagad', 'rocket', 'card', 'bank', 'cheque', 'mobile banking', 'other'].map((m) => (
                      <SelectItem key={m} value={m}>{m}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Status</Label>
                <Select value={String(form.status || 'approved')} onValueChange={(v) => setForm((prev) => ({ ...prev, status: v }))}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="approved">Approved</SelectItem>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="rejected">Rejected</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Reference Number</Label>
                <Input
                  value={String(form.referenceNumber || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, referenceNumber: e.target.value }))}
                  placeholder="INV-2026-001"
                />
              </div>
              <div>
                <Label>Attachment URL</Label>
                <Input
                  value={String(form.attachmentUrl || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, attachmentUrl: e.target.value }))}
                  placeholder="https://…"
                />
              </div>
              <div className="sm:col-span-2">
                <Label>Description</Label>
                <Textarea
                  rows={2}
                  value={String(form.description || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
                />
              </div>
              <div className="sm:col-span-2">
                <Label>Notes</Label>
                <Textarea
                  rows={2}
                  value={String(form.notes || '')}
                  onChange={(e) => setForm((prev) => ({ ...prev, notes: e.target.value }))}
                />
              </div>
            </div>
            <DialogFooter className="sticky bottom-0 bg-background pt-4">
              <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
              <Button onClick={handleSave} disabled={saving}>
                {saving && <Loader2 className="h-4 w-4 animate-spin" />}
                {editing ? 'Save Changes' : 'Create Expense'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <Dialog open={!!viewing} onOpenChange={(v) => { if (!v) setViewing(null) }}>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>Expense Details</DialogTitle>
            </DialogHeader>
            {viewing && (
              <div className="space-y-3">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="text-lg font-semibold">{viewing.title}</p>
                    <p className="text-xs text-muted-foreground">
                      {viewing.expenseDate ? viewing.expenseDate.slice(0, 10) : '—'}
                      {viewing.referenceNumber ? ` · #${viewing.referenceNumber}` : ''}
                    </p>
                  </div>
                  <Badge variant={statusVariant[viewing.status] || 'muted'}>{viewing.status}</Badge>
                </div>
                <p className="text-3xl font-bold">{currency(viewing.amount)}</p>
                <div className="grid grid-cols-2 gap-3 rounded-lg border p-3 text-sm">
                  <div>
                    <p className="text-xs text-muted-foreground">Category</p>
                    <p>{viewing.categoryName || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Member</p>
                    <p>{viewing.memberName || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Payment Method</p>
                    <p className="capitalize">{viewing.paymentMethod || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Vendor</p>
                    <p>{viewing.vendor || '—'}</p>
                  </div>
                  {viewing.createdByName && (
                    <div className="col-span-2">
                      <p className="text-xs text-muted-foreground">Recorded By</p>
                      <p>{viewing.createdByName}</p>
                    </div>
                  )}
                  {viewing.createdAt && (
                    <div className="col-span-2">
                      <p className="text-xs text-muted-foreground">Created At</p>
                      <p>{new Date(viewing.createdAt).toLocaleString()}</p>
                    </div>
                  )}
                </div>
                {viewing.description && (
                  <div>
                    <p className="text-xs text-muted-foreground">Description</p>
                    <p className="text-sm">{viewing.description}</p>
                  </div>
                )}
                {viewing.notes && (
                  <div>
                    <p className="text-xs text-muted-foreground">Notes</p>
                    <p className="text-sm">{viewing.notes}</p>
                  </div>
                )}
                {viewing.attachmentUrl && (
                  <a href={viewing.attachmentUrl} target="_blank" rel="noreferrer" className="text-sm text-primary hover:underline">
                    View attachment
                  </a>
                )}
              </div>
            )}
            <DialogFooter>
              <Button variant="outline" onClick={() => setViewing(null)}>Close</Button>
              {viewing && <Button onClick={() => openEdit(viewing)}>Edit</Button>}
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <ConfirmModal
          open={!!deleteTarget}
          title="Delete expense?"
          message={`This will permanently delete "${deleteTarget?.title}". This action cannot be undone.`}
          confirmText="Delete"
          loading={deleting}
          onConfirm={confirmDelete}
          onCancel={() => setDeleteTarget(null)}
        />
      </div>
    </AdminLayout>
  )
}

export default AdminExpensesPage
