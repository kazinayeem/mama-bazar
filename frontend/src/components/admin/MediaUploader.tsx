import { useCallback, useEffect, useRef, useState } from 'react'
import { useDropzone } from 'react-dropzone'
import { AlertCircle, ImagePlus, Loader2, RefreshCw, RotateCcw, X } from 'lucide-react'
import { toast } from 'sonner'
import { cn } from '@/lib/utils'
import { uploadFilesWithProgress } from '../../features/products/lib/upload'

export interface MediaUploaderProps {
  /** Controlled image URLs */
  images: string[]
  onChange: (images: string[]) => void
  max?: number
  folder?: string
  /** Object-fit for thumbnails; 'cover' (default) or 'contain' for logos */
  fit?: 'cover' | 'contain'
  /** Human-readable label for the dropzone */
  label?: string
  multiple?: boolean
}

interface UploadState {
  id: number
  file: File
  percent: number
  status: 'uploading' | 'done' | 'error'
  error?: string
  preview: string
  url?: string
}

const MAX_SIZE = 5 * 1024 * 1024 // 5 MB
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif']

const validate = (file: File): string | null => {
  if (!ACCEPTED.includes(file.type)) return 'Only JPG, PNG, WEBP, GIF and AVIF images are allowed'
  if (file.size > MAX_SIZE) return 'File is larger than 5 MB'
  return null
}

/**
 * Reusable Cloudinary-backed image uploader: drag & drop, click to upload,
 * per-file progress, preview, replace, delete, retry, type/size validation.
 */
const MediaUploader = ({
  images,
  onChange,
  max = 10,
  folder = 'general',
  fit = 'cover',
  label = 'Upload images',
  multiple = true,
}: MediaUploaderProps) => {
  const [uploads, setUploads] = useState<UploadState[]>([])
  const [processing, setProcessing] = useState(false)
  const [checkingDims, setCheckingDims] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)
  const idRef = useRef(0)
  const imagesRef = useRef(images)

  useEffect(() => {
    imagesRef.current = images
  }, [images])

  const updateUpload = (id: number, patch: Partial<UploadState>) =>
    setUploads((prev) => prev.map((u) => (u.id === id ? { ...u, ...patch } : u)))

  const uploadFile = async (file: File) => {
    const id = ++idRef.current
    const preview = URL.createObjectURL(file)
    const state: UploadState = { id, file, percent: 0, status: 'uploading', preview }
    setUploads((prev) => [...prev, state])

    try {
      const assets = await uploadFilesWithProgress([file], folder, (percent) => {
        updateUpload(id, { percent })
      })
      const url = assets[0]?.url
      if (!url) throw new Error('Upload returned no URL')
      updateUpload(id, { status: 'done', url, percent: 100 })
      const next = [...imagesRef.current, url]
      imagesRef.current = next
      onChange(next)
    } catch (err) {
      updateUpload(id, {
        status: 'error',
        error: err instanceof Error ? err.message : 'Upload failed',
      })
    }
  }

  const onDrop = useCallback(
    async (files: File[]) => {
      const remaining = max - images.length - uploads.filter((u) => u.status !== 'error').length
      if (files.length > remaining) {
        toast.error(`You can add up to ${max} images`)
        files = files.slice(0, remaining)
      }
      if (!files.length) return

      setProcessing(true)
      for (const file of files) {
        const invalid = validate(file)
        if (invalid) {
          toast.error(invalid)
          continue
        }
        if (!multiple && images.length > 0) {
          onChange([])
        }
        await uploadFile(file)
      }
      setProcessing(false)
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [images.length, max, multiple, onChange],
  )

  const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    onDrop,
    accept: { 'image/*': ACCEPTED },
    disabled: processing,
    noClick: true,
    noKeyboard: true,
  })

  const removeImage = (index: number) => {
    const next = images.filter((_, i) => i !== index)
    onChange(next)
  }

  const removeUpload = (index: number) => {
    setUploads((prev) => prev.filter((_, i) => i !== index))
  }

  const checkDimension = async (url: string): Promise<string | null> => {
    if (!url) return null
    return new Promise((resolve) => {
      const img = new Image()
      img.onload = () => {
        if (img.naturalWidth < 64 || img.naturalHeight < 64) {
          resolve('Image is too small (min 64×64)')
        } else {
          resolve(null)
        }
      }
      img.onerror = () => resolve('Could not load image')
      img.src = url
    })
  }

  const validateExisting = async () => {
    setCheckingDims(true)
    const errors: string[] = []
    for (const url of images) {
      const err = await checkDimension(url)
      if (err) errors.push(`${url.slice(0, 40)}… — ${err}`)
    }
    setCheckingDims(false)
    if (errors.length) {
      toast.error(errors.join('\n'))
    } else {
      toast.success('All images look good')
    }
  }

  const slots = max - images.length

  return (
    <div className="space-y-3">
      <div
        {...getRootProps()}
        onClick={open}
        className={cn(
          'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-6 text-center transition-colors',
          isDragActive ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-primary/50',
          processing && 'pointer-events-none opacity-60',
        )}
      >
        <input {...getInputProps()} ref={inputRef} />
        {processing ? (
          <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
        ) : (
          <ImagePlus className="h-6 w-6 text-muted-foreground" />
        )}
        <div className="text-sm">
          <p className="font-medium">
            {isDragActive ? 'Drop images here' : `Drag & drop or click to ${label.toLowerCase()}`}
          </p>
          <p className="mt-1 text-xs text-muted-foreground">
            JPG, PNG, WEBP, GIF · max 5 MB · {images.length}/{max} uploaded
          </p>
        </div>
      </div>

      {uploads.some((u) => u.status === 'error' || u.status === 'uploading') && (
        <div className="space-y-2">
          {uploads.map((u, i) => (
            <div key={i} className="flex items-center gap-3 rounded-md border p-2">
              <img src={u.preview} alt="" className="h-10 w-10 rounded object-cover" />
              <div className="min-w-0 flex-1">
                <p className="truncate text-xs font-medium">{u.file.name}</p>
                {u.status === 'error' ? (
                  <p className="text-xs text-destructive">
                    {u.error}
                    <button
                      type="button"
                      onClick={() => {
                        setUploads((prev) => prev.filter((_, j) => j !== i))
                        uploadFile(u.file)
                      }}
                      className="ml-2 inline-flex items-center gap-1 underline"
                    >
                      <RotateCcw className="h-3 w-3" /> Retry
                    </button>
                  </p>
                ) : (
                  <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                    <div className="h-full bg-primary transition-all" style={{ width: `${u.percent}%` }} />
                  </div>
                )}
              </div>
              <button type="button" onClick={() => removeUpload(i)} className="text-muted-foreground hover:text-destructive">
                <X className="h-4 w-4" />
              </button>
            </div>
          ))}
        </div>
      )}

      {images.length > 0 && (
        <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
          {images.map((url, i) => (
            <div key={url + i} className="group relative aspect-square overflow-hidden rounded-md border bg-muted">
              <img src={url} alt="" className={cn('h-full w-full', fit === 'contain' ? 'object-contain p-2' : 'object-cover')} />
              <button
                type="button"
                onClick={() => removeImage(i)}
                className="absolute right-1 top-1 rounded-full bg-black/60 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
                title="Remove"
              >
                <X className="h-3 w-3" />
              </button>
              {i === 0 && (
                <span className="absolute bottom-1 left-1 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-medium text-white">
                  Main
                </span>
              )}
            </div>
          ))}
        </div>
      )}

      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        {images.length > 0 && (
          <button type="button" onClick={validateExisting} className="inline-flex items-center gap-1 hover:text-primary">
            <RefreshCw className="h-3 w-3" />
            {checkingDims ? 'Checking…' : 'Validate images'}
          </button>
        )}
        {slots > 0 && (
          <button type="button" onClick={open} className="inline-flex items-center gap-1 hover:text-primary">
            <ImagePlus className="h-3 w-3" /> Add more
          </button>
        )}
      </div>

      <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
        <AlertCircle className="h-3 w-3" />
        Uploaded images are stored on Cloudinary, not on the server filesystem.
      </p>
    </div>
  )
}

export default MediaUploader
