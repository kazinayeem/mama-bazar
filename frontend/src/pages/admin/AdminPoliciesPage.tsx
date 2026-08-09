import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import {
  Archive,
  ExternalLink,
  FileText,
  Inbox,
  Loader2,
  Mail,
  MessageSquare,
  Pencil,
  Plus,
  Trash2,
} from 'lucide-react'

import AdminLayout from '@/components/layout/AdminLayout'
import RichTextEditor from '@/components/admin/RichTextEditor'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { adminApi } from '@/lib/adminApi'
import { cn } from '@/lib/utils'
import type { ContactMessage, PolicyPage } from '@/types'
import { SEO } from '../../components/common/SEO'

const SLUG_TO_PATH: Record<string, string> = {
  'return-refund': '/refund-policy',
  shipping: '/shipping-policy',
  privacy: '/privacy-policy',
  terms: '/terms-and-conditions',
  cookie: '/cookie-policy',
  payment: '/payment-policy',
  cancellation: '/cancellation-policy',
  warranty: '/warranty-policy',
  faq: '/faq',
  contact: '/contact',
}

const STATUS_STYLES: Record<string, string> = {
  published: 'bg-emerald-100 text-emerald-800',
  draft: 'bg-slate-100 text-slate-600',
}

interface PageDraft {
  id?: number
  slug: string
  title: string
  status: 'published' | 'draft'
  content: string
}

const formatDate = (ts: number | string) => {
  const d = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' })
}

const AdminPoliciesPage = () => {
  const navigate = useNavigate()
  const [tab, setTab] = useState('pages')
  const [pages, setPages] = useState<PolicyPage[]>([])
  const [messages, setMessages] = useState<ContactMessage[]>([])
  const [loading, setLoading] = useState(true)
  const [editorOpen, setEditorOpen] = useState(false)
  const [saving, setSaving] = useState(false)
  const [draft, setDraft] = useState<PageDraft>({ slug: '', title: '', status: 'published', content: '' })

  const loadAll = async () => {
    try {
      const [p, m] = await Promise.all([adminApi.getPolicyPages(), adminApi.getContactMessages()])
      setPages(p)
      setMessages(m)
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'তথ্য লোড করা যায়নি')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadAll()
  }, [])

  const newCount = useMemo(() => messages.filter((m) => m.status === 'new').length, [messages])

  const openCreate = () => {
    setDraft({ slug: '', title: '', status: 'published', content: '' })
    setEditorOpen(true)
  }

  const openEdit = (p: PolicyPage) => {
    setDraft({ id: p.id, slug: p.slug, title: p.title, status: p.status, content: p.content })
    setEditorOpen(true)
  }

  const savePage = async () => {
    if (!draft.title.trim()) {
      toast.error('টাইটেল লিখুন')
      return
    }
    if (!draft.slug.trim()) {
      toast.error('স্লাগ লিখুন (যেমন: shipping)')
      return
    }
    if (!draft.content.trim()) {
      toast.error('কনটেন্ট লিখুন')
      return
    }
    setSaving(true)
    try {
      if (draft.id) {
        await adminApi.updatePolicyPage(draft.id, {
          title: draft.title,
          status: draft.status,
          content: draft.content,
        })
        toast.success('পেজ আপডেট হয়েছে')
      } else {
        await adminApi.createPolicyPage({ slug: draft.slug, title: draft.title, status: draft.status, content: draft.content })
        toast.success('নতুন পেজ তৈরি হয়েছে')
      }
      setEditorOpen(false)
      await loadAll()
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'সেভ করা যায়নি')
    } finally {
      setSaving(false)
    }
  }

  const toggleStatus = async (p: PolicyPage) => {
    const next = p.status === 'published' ? 'draft' : 'published'
    try {
      await adminApi.updatePolicyPage(p.id, { status: next })
      setPages((list) => list.map((x) => (x.id === p.id ? { ...x, status: next } : x)))
      toast.success(next === 'published' ? 'প্রকাশিত হয়েছে' : 'ড্রাফটে রাখা হয়েছে')
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'ব্যর্থ হয়েছে')
    }
  }

  const removePage = async (p: PolicyPage) => {
    if (!window.confirm(`"${p.title}" পেজটি মুছে ফেলবেন?`)) return
    try {
      await adminApi.deletePolicyPage(p.id)
      setPages((list) => list.filter((x) => x.id !== p.id))
      toast.success('পেজ মুছে ফেলা হয়েছে')
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'মুছে ফেলা যায়নি')
    }
  }

  const openOnSite = (slug: string) => {
    const path = SLUG_TO_PATH[slug] || `/${slug}`
    navigate(path)
  }

  const setMessageStatus = async (m: ContactMessage, status: ContactMessage['status']) => {
    try {
      await adminApi.setContactMessageStatus(m.id, status)
      setMessages((list) => list.map((x) => (x.id === m.id ? { ...x, status } : x)))
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'ব্যর্থ হয়েছে')
    }
  }

  return (
    <AdminLayout>
      <SEO title="Manage Policies" description="Manage store policies and pages." url="/admin/policies" />
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Policies & Messages</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Edit store policy pages (shown to customers) and read messages submitted through the
            contact form.
          </p>
        </div>

        <Tabs value={tab} onValueChange={setTab}>
          <TabsList>
            <TabsTrigger value="pages" className="gap-2">
              <FileText className="h-4 w-4" /> Pages ({pages.length})
            </TabsTrigger>
            <TabsTrigger value="messages" className="gap-2">
              <MessageSquare className="h-4 w-4" /> Messages ({messages.length})
              {newCount > 0 && <Badge variant="destructive" className="ml-1">{newCount}</Badge>}
            </TabsTrigger>
          </TabsList>

          <TabsContent value="pages" className="mt-4">
            <Card>
              <CardHeader className="flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base">Policy Pages</CardTitle>
                <Button size="sm" onClick={openCreate} className="gap-2">
                  <Plus className="h-4 w-4" /> New Page
                </Button>
              </CardHeader>
              <CardContent>
                {loading ? (
                  <div className="flex justify-center py-10">
                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                  </div>
                ) : pages.length === 0 ? (
                  <div className="flex flex-col items-center gap-2 py-10 text-muted-foreground">
                    <Inbox className="h-8 w-8" />
                    <p className="text-sm">No policy pages yet. Create your first one.</p>
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Title</TableHead>
                          <TableHead>Slug</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead className="whitespace-nowrap">Last Updated</TableHead>
                          <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {pages.map((p) => (
                          <TableRow key={p.id}>
                            <TableCell>
                              <div className="flex items-center gap-2">
                                <span className="font-medium">{p.title}</span>
                                <button
                                  className="text-muted-foreground transition hover:text-primary"
                                  title="Open on site"
                                  onClick={() => openOnSite(p.slug)}
                                >
                                  <ExternalLink className="h-3.5 w-3.5" />
                                </button>
                              </div>
                            </TableCell>
                            <TableCell>
                              <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{p.slug}</code>
                            </TableCell>
                            <TableCell>
                              <button
                                className={cn(
                                  'rounded-full px-2.5 py-0.5 text-xs font-semibold transition hover:opacity-80',
                                  STATUS_STYLES[p.status],
                                )}
                                onClick={() => toggleStatus(p)}
                                title="Click to toggle"
                              >
                                {p.status === 'published' ? 'Published' : 'Draft'}
                              </button>
                            </TableCell>
                            <TableCell className="whitespace-nowrap text-muted-foreground">
                              {formatDate(p.lastUpdated)}
                            </TableCell>
                            <TableCell className="text-right">
                              <div className="flex justify-end gap-1">
                                <Button variant="ghost" size="icon" title="Edit" onClick={() => openEdit(p)}>
                                  <Pencil className="h-4 w-4" />
                                </Button>
                                <Button
                                  variant="ghost"
                                  size="icon"
                                  title="Delete"
                                  className="text-destructive hover:text-destructive"
                                  onClick={() => removePage(p)}
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
          </TabsContent>

          <TabsContent value="messages" className="mt-4">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Mail className="h-4 w-4" /> Contact Messages
                </CardTitle>
              </CardHeader>
              <CardContent>
                {loading ? (
                  <div className="flex justify-center py-10">
                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                  </div>
                ) : messages.length === 0 ? (
                  <div className="flex flex-col items-center gap-2 py-10 text-muted-foreground">
                    <Inbox className="h-8 w-8" />
                    <p className="text-sm">No messages yet.</p>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {messages.map((m) => (
                      <div
                        key={m.id}
                        className={cn(
                          'rounded-lg border p-4',
                          m.status === 'new' ? 'border-primary/40 bg-primary/5' : 'border-border',
                          m.status === 'archived' && 'opacity-60',
                        )}
                      >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                          <div className="min-w-0">
                            <p className="font-medium">
                              {m.name}{' '}
                              <span className="ml-1 text-sm font-normal text-muted-foreground">
                                {m.phone}
                                {m.email ? ` · ${m.email}` : ''}
                              </span>
                            </p>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                              {formatDate(Number(m.createdAt))}
                            </p>
                          </div>
                          <div className="flex items-center gap-1">
                            {m.status === 'new' && (
                              <Button variant="outline" size="sm" onClick={() => setMessageStatus(m, 'read')}>
                                Mark read
                              </Button>
                            )}
                            {m.status !== 'archived' ? (
                              <Button
                                variant="ghost"
                                size="icon"
                                title="Archive"
                                onClick={() => setMessageStatus(m, 'archived')}
                              >
                                <Archive className="h-4 w-4" />
                              </Button>
                            ) : (
                              <Button
                                variant="ghost"
                                size="sm"
                                title="Restore"
                                onClick={() => setMessageStatus(m, 'read')}
                              >
                                Restore
                              </Button>
                            )}
                          </div>
                        </div>
                        <p className="mt-3 whitespace-pre-wrap text-sm leading-6">{m.message}</p>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>

      <Dialog open={editorOpen} onOpenChange={setEditorOpen}>
        <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{draft.id ? 'Edit Policy Page' : 'New Policy Page'}</DialogTitle>
            <DialogDescription>
              Content is saved as HTML. Use the toolbar for headings and lists. Leave slug{" "}
              <code className="rounded bg-muted px-1 text-xs">{'{{SHIPPING_METHODS}}'}</code> placeholders
              intact to keep dynamic sections.
            </DialogDescription>
          </DialogHeader>

          <div className="grid gap-4 py-2">
            <div className="grid gap-3 sm:grid-cols-2">
              <div>
                <Label htmlFor="page-title">Title</Label>
                <Input
                  id="page-title"
                  className="mt-1.5"
                  value={draft.title}
                  onChange={(e) => setDraft({ ...draft, title: e.target.value })}
                  placeholder="e.g. রিটার্ন ও রিফান্ড নীতিমালা"
                />
              </div>
              <div>
                <Label htmlFor="page-slug">Slug</Label>
                <Input
                  id="page-slug"
                  className="mt-1.5 font-mono"
                  value={draft.slug}
                  disabled={!!draft.id}
                  onChange={(e) =>
                    setDraft({ ...draft, slug: e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '-') })
                  }
                  placeholder="e.g. return-refund"
                />
              </div>
            </div>
            <div>
              <Label>Status</Label>
              <Select
                value={draft.status}
                onValueChange={(v) => setDraft({ ...draft, status: v as 'published' | 'draft' })}
              >
                <SelectTrigger className="mt-1.5 w-full sm:w-48">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="published">Published</SelectItem>
                  <SelectItem value="draft">Draft</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Content</Label>
              <div className="mt-1.5">
                <RichTextEditor value={draft.content} onChange={(content) => setDraft({ ...draft, content })} minHeight={260} />
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setEditorOpen(false)}>
              Cancel
            </Button>
            <Button onClick={savePage} disabled={saving} className="gap-2">
              {saving && <Loader2 className="h-4 w-4 animate-spin" />}
              {draft.id ? 'Save Changes' : 'Create Page'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AdminLayout>
  )
}

export default AdminPoliciesPage