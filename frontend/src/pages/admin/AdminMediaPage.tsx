import { useCallback, useEffect, useRef, useState } from 'react'
import { Check, Cloud, Copy, File, Loader2, Search, Trash2, Upload } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '../../components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
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
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Skeleton } from '@/components/ui/skeleton'
import { adminApi } from '@/lib/adminApi'
import type { MediaAsset } from '@/types/admin'
import { SEO } from '../../components/common/SEO'
import { resolveAbsoluteUrl } from '@/lib/apiConfig'

const AdminMediaPage = () => {
  const [assets, setAssets] = useState<MediaAsset[]>([])
  const [folders, setFolders] = useState<Array<{ name: string; count: number }>>([])
  const [activeFolder, setActiveFolder] = useState('all')
  const [search, setSearch] = useState('')
  const [loading, setLoading] = useState(true)
  const [uploading, setUploading] = useState(false)
  const [page, setPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [totalPages, setTotalPages] = useState(1)
  const [selected, setSelected] = useState<MediaAsset[]>([])
  const [preview, setPreview] = useState<MediaAsset | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<MediaAsset | null>(null)
  const [copied, setCopied] = useState<string | null>(null)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const load = useCallback(async (folder: string, term: string, pg: number, isInitial = false) => {
    if (isInitial) setLoading(true)
    try {
      const result = await adminApi.getMedia({
        page: pg,
        limit: 48,
        folder: folder === 'all' ? undefined : folder,
        search: term || undefined,
      })
      setAssets(result.data)
      setTotal(result.total)
      setTotalPages(result.totalPages)
      setFolders(await adminApi.getMediaFolders())
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load media')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load('all', '', 1, true)
  }, [load])

  const handleUpload = async (files: FileList | null) => {
    if (!files || files.length === 0) return
    setUploading(true)
    try {
      const uploaded = await adminApi.uploadMedia(Array.from(files), 'general')
      toast.success(`${uploaded.length} file(s) uploaded`)
      setSelected((prev) => [...prev, ...uploaded])
      load(activeFolder, search, page)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Upload failed')
    } finally {
      setUploading(false)
    }
  }

  const copyUrl = async (asset: MediaAsset) => {
    const url = resolveAbsoluteUrl(asset.url)
    try {
      await navigator.clipboard.writeText(url)
      setCopied(asset.id.toString())
      setTimeout(() => setCopied(null), 1500)
      toast.success('URL copied')
    } catch {
      toast.error('Copy failed')
    }
  }

  const handleDelete = async () => {
    if (!deleteTarget) return
    const mediaId = deleteTarget.id
    try {
      await adminApi.deleteMedia(mediaId)
      toast.success('File deleted')
      setDeleteTarget(null)
      setSelected((prev) => prev.filter((a) => a.id !== mediaId))
      setAssets((prev) => prev.filter((a) => a.id !== mediaId))
      if (assets.length === 1 && page > 1) {
        setPage((p) => Math.max(1, p - 1))
        load(activeFolder, search, Math.max(1, page - 1), false)
      } else {
        load(activeFolder, search, page, false)
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Delete failed')
      load(activeFolder, search, page, false)
    }
  }

  return (
    <AdminLayout>
      <SEO title="Media Library" description="Upload and manage media files." url="/admin/media" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Media Library</h1>
            <p className="text-sm text-muted-foreground">{total} files · images, videos and documents</p>
          </div>
          <div className="flex items-center gap-2">
            {selected.length > 0 && (
              <Badge variant="secondary">{selected.length} selected</Badge>
            )}
            <Button onClick={() => fileInputRef.current?.click()} disabled={uploading}>
              {uploading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
              Upload
            </Button>
            <input
              ref={fileInputRef}
              type="file"
              multiple
              className="hidden"
              onChange={(e) => handleUpload(e.target.files)}
            />
          </div>
        </div>

        <Card>
          <CardContent className="p-3">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search files..."
                  value={search}
                  onChange={(e) => {
                    setSearch(e.target.value)
                    setPage(1)
                    load(activeFolder, e.target.value, 1)
                  }}
                  className="pl-9"
                />
              </div>
              <div className="flex items-center gap-1 overflow-x-auto">
                <Badge
                  variant={activeFolder === 'all' ? 'default' : 'secondary'}
                  className="cursor-pointer whitespace-nowrap"
                  onClick={() => { setActiveFolder('all'); setPage(1); load('all', search, 1) }}
                >
                  All
                </Badge>
                {folders.map((f) => (
                  <Badge
                    key={f.name}
                    variant={activeFolder === f.name ? 'default' : 'secondary'}
                    className="cursor-pointer whitespace-nowrap"
                    onClick={() => { setActiveFolder(f.name); setPage(1); load(f.name, search, 1) }}
                  >
                    {f.name} ({f.count})
                  </Badge>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>

        {loading && assets.length === 0 ? (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            {Array.from({ length: 12 }).map((_, i) => (
              <Skeleton key={i} className="aspect-square w-full" />
            ))}
          </div>
        ) : assets.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <p className="text-sm text-muted-foreground">No files found. Upload your first asset.</p>
            </CardContent>
          </Card>
        ) : (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            {assets.map((asset) => {
              const isImage = asset.mimeType.startsWith('image/')
              const isSelected = selected.some((a) => a.id === asset.id)
              return (
                <div
                  key={asset.id}
                  className={`group relative aspect-square cursor-pointer overflow-hidden rounded-lg border bg-muted transition-all ${
                    isSelected ? 'border-primary ring-2 ring-primary' : 'hover:shadow-md'
                  }`}
                  onClick={() => {
                    setPreview(asset)
                    setSelected((prev) =>
                      isSelected ? prev.filter((a) => a.id !== asset.id) : [...prev, asset],
                    )
                  }}
                >
                  {isImage ? (
                    <img src={asset.url} alt={asset.alt || asset.filename} className="h-full w-full object-cover" />
                  ) : (
                    <div className="flex h-full items-center justify-center">
                      <File className="h-10 w-10 text-muted-foreground" />
                    </div>
                  )}
                  <div className="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 transition-opacity group-hover:opacity-100">
                    <button
                      type="button"
                      onClick={(e) => { e.stopPropagation(); copyUrl(asset) }}
                      className="rounded bg-white/20 p-1.5 text-white hover:bg-white/30"
                    >
                      {copied === asset.id.toString() ? <Check className="h-3.5 w-3.5" /> : <Copy className="h-3.5 w-3.5" />}
                    </button>
                    <button
                      type="button"
                      onClick={(e) => { e.stopPropagation(); setDeleteTarget(asset) }}
                      className="rounded bg-white/20 p-1.5 text-white hover:bg-destructive"
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </button>
                  </div>
                  {isSelected && (
                    <span className="absolute right-2 top-2 rounded-full bg-primary p-1 text-primary-foreground">
                      <Check className="h-3 w-3" />
                    </span>
                  )}
                  {asset.provider === 'cloudinary' && (
                    <span className="absolute left-2 top-2 rounded bg-black/60 p-1 text-white">
                      <Cloud className="h-3 w-3" />
                    </span>
                  )}
                </div>
              )
            })}
          </div>
        )}

        {totalPages > 1 && (
          <div className="flex items-center justify-between">
            <span className="text-xs text-muted-foreground">Page {page} of {totalPages}</span>
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => { const p = page - 1; setPage(p); load(activeFolder, search, p) }}>
                Previous
              </Button>
              <Button variant="outline" size="sm" disabled={page >= totalPages} onClick={() => { const p = page + 1; setPage(p); load(activeFolder, search, p) }}>
                Next
              </Button>
            </div>
          </div>
        )}
      </div>

      {/* Preview */}
      <Dialog open={!!preview} onOpenChange={(v) => !v && setPreview(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{preview?.filename}</DialogTitle>
          </DialogHeader>
          {preview && (
            <div className="space-y-3">
              {preview.mimeType.startsWith('image/') ? (
                <img src={preview.url} alt={preview.alt || preview.filename} className="w-full rounded-md" />
              ) : (
                <div className="flex h-40 items-center justify-center rounded-md bg-muted">
                  <File className="h-12 w-12 text-muted-foreground" />
                </div>
              )}
              <div className="grid grid-cols-2 gap-2 text-xs">
                <div className="rounded-md bg-muted p-2">
                  <p className="text-muted-foreground">Size</p>
                  <p className="font-medium">{(preview.size / 1024).toFixed(1)} KB</p>
                </div>
                <div className="rounded-md bg-muted p-2">
                  <p className="text-muted-foreground">Type</p>
                  <p className="font-medium">{preview.mimeType}</p>
                </div>
                {preview.width && preview.height && (
                  <div className="rounded-md bg-muted p-2">
                    <p className="text-muted-foreground">Dimensions</p>
                    <p className="font-medium">{preview.width} × {preview.height}</p>
                  </div>
                )}
                <div className="rounded-md bg-muted p-2">
                  <p className="text-muted-foreground">Provider</p>
                  <p className="font-medium capitalize">{preview.provider}</p>
                </div>
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => preview && copyUrl(preview)}>
              <Copy className="h-4 w-4" /> Copy URL
            </Button>
            <Button onClick={() => { if (preview) setDeleteTarget(preview); setPreview(null) }} variant="destructive">
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleteTarget} onOpenChange={(v) => !v && setDeleteTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete file?</AlertDialogTitle>
            <AlertDialogDescription>
              "{deleteTarget?.filename}" will be permanently deleted from storage.
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
    </AdminLayout>
  )
}

export default AdminMediaPage
