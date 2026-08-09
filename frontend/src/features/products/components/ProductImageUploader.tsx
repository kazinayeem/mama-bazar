import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useDropzone } from 'react-dropzone'
import {
  ArrowLeft,
  ArrowRight,
  Crown,
  ImagePlus,
  Loader2,
  RefreshCw,
  Trash2,
  X,
} from 'lucide-react'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { uploadFilesWithProgress } from '../lib/upload'

export interface ImageItem {
  id: string
  url: string
  status: 'existing' | 'uploading' | 'done' | 'error'
  progress: number
  error?: string
}

interface ProductImageUploaderProps {
  images: ImageItem[]
  onChange: (images: ImageItem[]) => void
  maxImages?: number
  folder?: string
  accept?: string[]
}

const newId = () => crypto.randomUUID()

const ProductImageUploader = ({
  images,
  onChange,
  maxImages = 10,
  folder = 'products',
  accept = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'],
}: ProductImageUploaderProps) => {
  const [draggingIndex, setDraggingIndex] = useState<number | null>(null)
  const remaining = maxImages - images.filter((i) => i.status !== 'error').length

  const imagesRef = useRef(images)
  useEffect(() => {
    imagesRef.current = images
  }, [images])

  const applyChange = useCallback(
    (produce: (current: ImageItem[]) => ImageItem[]) => {
      const next = produce(imagesRef.current)
      imagesRef.current = next
      onChange(next)
    },
    [onChange],
  )

  const updateImage = useCallback(
    (id: string, patch: Partial<ImageItem>) => {
      applyChange((current) => current.map((img) => (img.id === id ? { ...img, ...patch } : img)))
    },
    [applyChange],
  )

  const removeImage = useCallback(
    (id: string) => {
      applyChange((current) => current.filter((img) => img.id !== id))
    },
    [applyChange],
  )

  const reorder = useCallback(
    (from: number, to: number) => {
      if (to < 0 || to >= imagesRef.current.length || from === to) return
      applyChange((current) => {
        const next = [...current]
        const [moved] = next.splice(from, 1)
        next.splice(to, 0, moved)
        return next
      })
    },
    [applyChange],
  )

  const makeThumbnail = useCallback(
    (index: number) => {
      if (index === 0) return
      reorder(index, 0)
    },
    [reorder],
  )

  const replaceImage = useCallback(
    (index: number) => {
      const input = document.createElement('input')
      input.type = 'file'
      input.accept = accept.join(',')
      input.onchange = async () => {
        const file = input.files?.[0]
        if (!file) return
        try {
          const assets = await uploadFilesWithProgress([file], folder)
          if (assets[0]) {
            const current = imagesRef.current[index]
            if (current) {
              updateImage(current.id, { url: assets[0].url })
              toast.success('Image replaced')
            }
          }
        } catch (err) {
          toast.error(err instanceof Error ? err.message : 'Replace failed')
        }
      }
      input.click()
    },
    [accept, folder, updateImage],
  )

  const handleUpload = useCallback(
    async (files: File[]) => {
      const accepted = files.filter((f) => accept.includes(f.type) || f.type.startsWith('image/'))
      if (accepted.length === 0) {
        toast.error('Only image files are allowed')
        return
      }
      const slot = maxImages - imagesRef.current.length
      const toUpload = accepted.slice(0, Math.max(0, slot))
      if (accepted.length > slot) toast.warning(`Only ${slot} more image(s) can be added`)

      const uploadItems: ImageItem[] = toUpload.map((file) => ({
        id: newId(),
        url: URL.createObjectURL(file),
        status: 'uploading',
        progress: 0,
      }))
      applyChange((current) => [...current, ...uploadItems])

      for (let i = 0; i < toUpload.length; i++) {
        const file = toUpload[i]
        const item = uploadItems[i]
        try {
          const assets = await uploadFilesWithProgress([file], folder, (percent) => {
            updateImage(item.id, { progress: percent })
          })
          const url = assets[0]?.url
          if (url) {
            updateImage(item.id, { url, status: 'done', progress: 100 })
          } else {
            updateImage(item.id, { status: 'error', error: 'Upload returned no URL' })
          }
        } catch (err) {
          updateImage(item.id, {
            status: 'error',
            error: err instanceof Error ? err.message : 'Upload failed',
          })
        }
      }
    },
    [accept, folder, maxImages, applyChange, updateImage],
  )

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop: (files) => handleUpload(files),
    accept: accept.reduce<Record<string, string[]>>((acc, type) => {
      acc[type] = []
      return acc
    }, {}),
    disabled: remaining <= 0,
    multiple: true,
  })

  const uploadError = useMemo(() => {
    const failed = images.find((i) => i.status === 'error')
    return failed ? failed.error : null
  }, [images])

  return (
    <div className="space-y-3">
      {/* Dropzone */}
      <div
        {...getRootProps()}
        className={cn(
          'flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-8 text-center transition-colors',
          isDragActive
            ? 'border-primary bg-primary/5'
            : 'border-border hover:border-primary/60 hover:bg-muted/40',
          remaining <= 0 && 'cursor-not-allowed opacity-50 hover:border-border hover:bg-transparent',
        )}
      >
        <input {...getInputProps()} />
        {isDragActive ? (
          <>
            <ImagePlus className="h-8 w-8 text-primary" />
            <p className="text-sm font-medium text-primary">Drop images to upload</p>
          </>
        ) : (
          <>
            <ImagePlus className="h-8 w-8 text-muted-foreground" />
            <p className="text-sm font-medium">
              Drag &amp; drop product images, or <span className="text-primary">browse</span>
            </p>
            <p className="text-xs text-muted-foreground">
              JPEG · PNG · WebP · GIF — max 20MB each · {remaining} of {maxImages} slots left
            </p>
          </>
        )}
      </div>

      {uploadError && (
        <p className="text-xs text-destructive">A file failed to upload: {uploadError}. You can retry by replacing it.</p>
      )}

      {/* Grid */}
      {images.length > 0 && (
        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
          {images.map((img, index) => (
            <div
              key={img.id}
              draggable={img.status === 'done' || img.status === 'existing'}
              onDragStart={() => setDraggingIndex(index)}
              onDragOver={(e) => e.preventDefault()}
              onDrop={(e) => {
                e.preventDefault()
                if (draggingIndex !== null) reorder(draggingIndex, index)
                setDraggingIndex(null)
              }}
              onDragEnd={() => setDraggingIndex(null)}
              className={cn(
                'group relative aspect-square overflow-hidden rounded-lg border bg-muted',
                draggingIndex === index && 'opacity-40 ring-2 ring-primary',
              )}
            >
              <img src={img.url} alt="" className="h-full w-full object-cover" />

              {/* Thumbnail badge */}
              {index === 0 && (
                <span className="absolute left-1.5 top-1.5 flex items-center gap-1 rounded bg-primary px-1.5 py-0.5 text-[10px] font-bold text-primary-foreground shadow">
                  <Crown className="h-3 w-3" /> Main
                </span>
              )}

              {/* Upload progress overlay */}
              {img.status === 'uploading' && (
                <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/50">
                  <Loader2 className="h-5 w-5 animate-spin text-white" />
                  <div className="h-1.5 w-3/4 overflow-hidden rounded-full bg-white/25">
                    <div className="h-full bg-white transition-all" style={{ width: `${img.progress}%` }} />
                  </div>
                  <span className="text-[10px] font-medium text-white">{img.progress}%</span>
                </div>
              )}

              {/* Error state */}
              {img.status === 'error' && (
                <div className="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-destructive/80 p-2 text-center">
                  <X className="h-5 w-5 text-white" />
                  <p className="text-[10px] leading-tight text-white">Upload failed</p>
                </div>
              )}

              {/* Hover actions */}
              {(img.status === 'done' || img.status === 'existing') && (
                <div className="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/70 to-transparent px-1.5 pb-1.5 pt-6 opacity-0 transition-opacity group-hover:opacity-100">
                  <div className="flex items-center gap-0.5">
                    <button
                      type="button"
                      title="Move left"
                      disabled={index === 0}
                      onClick={() => reorder(index, index - 1)}
                      className="rounded bg-white/20 p-1 text-white transition hover:bg-white/40 disabled:opacity-30"
                    >
                      <ArrowLeft className="h-3 w-3" />
                    </button>
                    <button
                      type="button"
                      title="Move right"
                      disabled={index === images.length - 1}
                      onClick={() => reorder(index, index + 1)}
                      className="rounded bg-white/20 p-1 text-white transition hover:bg-white/40 disabled:opacity-30"
                    >
                      <ArrowRight className="h-3 w-3" />
                    </button>
                  </div>
                  <div className="flex items-center gap-0.5">
                    {index !== 0 && (
                      <button
                        type="button"
                        title="Set as main image"
                        onClick={() => makeThumbnail(index)}
                        className="rounded bg-white/20 p-1 text-white transition hover:bg-white/40"
                      >
                        <Crown className="h-3 w-3" />
                      </button>
                    )}
                    <button
                      type="button"
                      title="Replace"
                      onClick={() => replaceImage(index)}
                      className="rounded bg-white/20 p-1 text-white transition hover:bg-white/40"
                    >
                      <RefreshCw className="h-3 w-3" />
                    </button>
                    <button
                      type="button"
                      title="Delete"
                      onClick={() => removeImage(img.id)}
                      className="rounded bg-destructive/80 p-1 text-white transition hover:bg-destructive"
                    >
                      <Trash2 className="h-3 w-3" />
                    </button>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      <p className="text-xs text-muted-foreground">
        The first image is used as the product thumbnail. Drag tiles or use the arrows to reorder.
      </p>
    </div>
  )
}

export default ProductImageUploader
