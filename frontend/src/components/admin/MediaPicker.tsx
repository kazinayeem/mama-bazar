import { useCallback, useEffect, useState } from 'react'
import { Check, File, Loader2, Upload, X } from 'lucide-react'
import { toast } from 'sonner'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ScrollArea } from '@/components/ui/scroll-area'
import { adminApi } from '@/lib/adminApi'
import type { MediaAsset } from '@/types/admin'

interface MediaPickerProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSelect: (assets: MediaAsset[]) => void
  multiple?: boolean
}

const MediaPicker = ({ open, onOpenChange, onSelect, multiple = true }: MediaPickerProps) => {
  const [assets, setAssets] = useState<MediaAsset[]>([])
  const [folders, setFolders] = useState<Array<{ name: string; count: number }>>([])
  const [activeFolder, setActiveFolder] = useState('all')
  const [search, setSearch] = useState('')
  const [selected, setSelected] = useState<MediaAsset[]>([])
  const [loading, setLoading] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)

  const load = useCallback(async (folder: string, term: string, pg: number) => {
    setLoading(true)
    try {
      const result = await adminApi.getMedia({
        page: pg,
        limit: 30,
        folder: folder === 'all' ? undefined : folder,
        search: term || undefined,
      })
      setAssets(result.data)
      setTotalPages(result.totalPages)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load media')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (open) {
      setSelected([])
      setActiveFolder('all')
      setSearch('')
      setPage(1)
      load('all', '', 1)
      adminApi.getMediaFolders().then(setFolders).catch(() => {})
    }
  }, [open, load])

  const handleUpload = async (files: FileList | null) => {
    if (!files || files.length === 0) return
    setUploading(true)
    try {
      const uploaded = await adminApi.uploadMedia(Array.from(files), 'products')
      if (multiple) {
        setSelected((prev) => [...prev, ...uploaded])
      } else {
        // Single-select mode: auto-select the first uploaded asset so "Use
        // selected" works right after uploading, without a manual click.
        setSelected(uploaded.slice(0, 1))
      }
      setAssets((prev) => [...uploaded, ...prev])
      toast.success(`${uploaded.length} file(s) uploaded`)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Upload failed')
    } finally {
      setUploading(false)
    }
  }

  const toggleSelect = (asset: MediaAsset) => {
    if (!multiple) {
      setSelected([asset])
      return
    }
    setSelected((prev) =>
      prev.some((a) => a.id === asset.id) ? prev.filter((a) => a.id !== asset.id) : [...prev, asset],
    )
  }

  const confirm = () => {
    if (selected.length === 0) return
    onSelect(selected)
    onOpenChange(false)
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Media Library</DialogTitle>
        </DialogHeader>

        <Tabs defaultValue="library">
          <TabsList>
            <TabsTrigger value="library">Library</TabsTrigger>
            <TabsTrigger value="upload">Upload</TabsTrigger>
          </TabsList>

          <TabsContent value="library" className="mt-3">
            <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center">
              <Input
                placeholder="Search files..."
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value)
                  setPage(1)
                  load(activeFolder, e.target.value, 1)
                }}
                className="h-8 flex-1"
              />
              <div className="flex items-center gap-1 overflow-x-auto">
                <Badge
                  variant={activeFolder === 'all' ? 'default' : 'secondary'}
                  className="cursor-pointer whitespace-nowrap"
                  onClick={() => {
                    setActiveFolder('all')
                    setPage(1)
                    load('all', search, 1)
                  }}
                >
                  All
                </Badge>
                {folders.map((f) => (
                  <Badge
                    key={f.name}
                    variant={activeFolder === f.name ? 'default' : 'secondary'}
                    className="cursor-pointer whitespace-nowrap"
                    onClick={() => {
                      setActiveFolder(f.name)
                      setPage(1)
                      load(f.name, search, 1)
                    }}
                  >
                    {f.name} ({f.count})
                  </Badge>
                ))}
              </div>
            </div>

            <ScrollArea className="h-72 rounded-md border p-3">
              {loading ? (
                <div className="flex h-full items-center justify-center">
                  <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                </div>
              ) : assets.length === 0 ? (
                <p className="flex h-full items-center justify-center text-sm text-muted-foreground">No files found</p>
              ) : (
                <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                  {assets.map((asset) => {
                    const isImage = asset.mimeType.startsWith('image/')
                    const isSelected = selected.some((a) => a.id === asset.id)
                    return (
                      <button
                        key={asset.id}
                        type="button"
                        onClick={() => toggleSelect(asset)}
                        className={`group relative aspect-square overflow-hidden rounded-md border bg-muted transition-colors ${
                          isSelected ? 'border-primary ring-2 ring-primary' : 'hover:border-primary/50'
                        }`}
                      >
                        {isImage ? (
                          <img src={asset.url} alt={asset.alt || asset.filename} className="h-full w-full object-cover" />
                        ) : (
                          <div className="flex h-full items-center justify-center">
                            <File className="h-8 w-8 text-muted-foreground" />
                          </div>
                        )}
                        {isSelected && (
                          <span className="absolute right-1 top-1 rounded-full bg-primary p-0.5 text-primary-foreground">
                            <Check className="h-3 w-3" />
                          </span>
                        )}
                      </button>
                    )
                  })}
                </div>
              )}
            </ScrollArea>

            <div className="mt-3 flex items-center justify-between">
              <span className="text-xs text-muted-foreground">{selected.length} selected</span>
              <div className="flex items-center gap-1">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page <= 1}
                  onClick={() => {
                    const p = page - 1
                    setPage(p)
                    load(activeFolder, search, p)
                  }}
                >
                  Prev
                </Button>
                <span className="px-2 text-xs text-muted-foreground">
                  {page} / {totalPages}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page >= totalPages}
                  onClick={() => {
                    const p = page + 1
                    setPage(p)
                    load(activeFolder, search, p)
                  }}
                >
                  Next
                </Button>
              </div>
            </div>
          </TabsContent>

          <TabsContent value="upload" className="mt-3">
            <label className="flex h-40 cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed text-muted-foreground transition-colors hover:border-primary hover:text-primary">
              {uploading ? (
                <Loader2 className="h-6 w-6 animate-spin" />
              ) : (
                <>
                  <Upload className="h-6 w-6" />
                  <span className="text-sm font-medium">Drop files here or click to browse</span>
                  <span className="text-xs">Max 20MB per file · images, video, PDF</span>
                </>
              )}
              <input
                type="file"
                multiple
                className="hidden"
                disabled={uploading}
                onChange={(e) => handleUpload(e.target.files)}
              />
            </label>
            <p className="mt-2 text-xs text-muted-foreground">
              Uploaded files are added to your selection automatically.
            </p>
          </TabsContent>
        </Tabs>

        <DialogFooter className="flex items-center justify-between gap-2 sm:justify-between">
          <div className="flex gap-2">
            {selected.map((s) => (
              <span key={s.id} className="relative inline-block">
                {s.mimeType.startsWith('image/') ? (
                  <img src={s.url} alt="" className="h-10 w-10 rounded border object-cover" />
                ) : (
                  <span className="flex h-10 w-10 items-center justify-center rounded border bg-muted">
                    <File className="h-4 w-4" />
                  </span>
                )}
                <button
                  type="button"
                  onClick={() => setSelected((prev) => prev.filter((a) => a.id !== s.id))}
                  className="absolute -right-1 -top-1 rounded-full bg-destructive p-0.5 text-destructive-foreground"
                >
                  <X className="h-3 w-3" />
                </button>
              </span>
            ))}
          </div>
          <Button onClick={confirm} disabled={selected.length === 0}>
            {multiple ? `Use ${selected.length} selected` : 'Use selected'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

export default MediaPicker
