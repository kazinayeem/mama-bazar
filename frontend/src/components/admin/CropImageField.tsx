import { useRef, useState } from 'react'
import { ImagePlus, Loader2, X } from 'lucide-react'
import { toast } from 'sonner'
import { Label } from '@/components/ui/label'
import SmartImage from '@/components/common/SmartImage'
import ImageCropperDialog from './ImageCropperDialog'
import { ASPECT_PRESETS } from './aspectPresets'
import { uploadFilesWithProgress } from '../../features/products/lib/upload'

const MAX_SIZE = 5 * 1024 * 1024
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif']

interface CropImageFieldProps {
  label: string
  value: string
  onChange: (url: string) => void
  aspectKey?: keyof typeof ASPECT_PRESETS
  folder?: string
  hint?: string
}

/**
 * Image field with upload → crop → Cloudinary flow for entities with
 * recommended aspect ratios (category 1:1, banner 16:9, mobile hero 4:5).
 */
const CropImageField = ({
  label,
  value,
  onChange,
  aspectKey = 'square',
  folder = 'general',
  hint,
}: CropImageFieldProps) => {
  const inputRef = useRef<HTMLInputElement>(null)
  const [pendingFile, setPendingFile] = useState<File | null>(null)
  const [pendingUrl, setPendingUrl] = useState('')
  const [uploading, setUploading] = useState(false)

  const aspect = ASPECT_PRESETS[aspectKey]

  const handleFile = (file: File | undefined) => {
    if (!file) return
    if (!ACCEPTED.includes(file.type)) {
      toast.error('Only JPG, PNG, WEBP, GIF and AVIF images are allowed')
      return
    }
    if (file.size > MAX_SIZE) {
      toast.error('File is larger than 5 MB')
      return
    }
    setPendingFile(file)
    setPendingUrl(URL.createObjectURL(file))
  }

  const handleCropped = async (cropped: File) => {
    setUploading(true)
    try {
      const assets = await uploadFilesWithProgress([cropped], folder)
      const url = assets[0]?.url
      if (!url) throw new Error('Upload returned no URL')
      onChange(url)
      toast.success('Image uploaded to Cloudinary')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Upload failed')
    } finally {
      setUploading(false)
      setPendingFile(null)
      setPendingUrl('')
      URL.revokeObjectURL(pendingUrl)
    }
  }

  return (
    <div className="space-y-2">
      <Label>{label}</Label>
      <div className="flex items-center gap-3">
        <div className="relative h-20 w-20 overflow-hidden rounded-lg border bg-muted">
          {uploading ? (
            <div className="flex h-full items-center justify-center">
              <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
            </div>
          ) : (
            <SmartImage
              src={value}
              alt=""
              className="h-full w-full object-cover"
              icon={<ImagePlus className="h-6 w-6" />}
            />
          )}
        </div>
        <div className="flex flex-col gap-2">
          <div className="flex gap-2">
            <input
              ref={inputRef}
              type="file"
              accept={ACCEPTED.join(',')}
              className="hidden"
              onChange={(e) => {
                handleFile(e.target.files?.[0])
                e.target.value = ''
              }}
            />
            <button
              type="button"
              onClick={() => inputRef.current?.click()}
              className="inline-flex h-8 items-center gap-1 rounded-md border px-2.5 text-xs font-medium transition-colors hover:border-primary hover:text-primary"
            >
              <ImagePlus className="h-3.5 w-3.5" /> Upload & crop
            </button>
            {value && (
              <button
                type="button"
                onClick={() => onChange('')}
                className="inline-flex h-8 items-center gap-1 rounded-md border px-2.5 text-xs font-medium text-destructive transition-colors hover:border-destructive"
              >
                <X className="h-3.5 w-3.5" /> Remove
              </button>
            )}
          </div>
          <p className="text-[11px] text-muted-foreground">
            {hint || `${aspect.label} recommended`} · JPG/PNG/WEBP · max 5 MB
          </p>
        </div>
      </div>

      <ImageCropperDialog
        open={!!pendingFile}
        onOpenChange={(v) => {
          if (!v) {
            setPendingFile(null)
            setPendingUrl('')
          }
        }}
        imageSrc={pendingUrl}
        fileName={pendingFile?.name || 'image'}
        aspect={aspect.value}
        aspectLabel={aspect.label}
        onCropped={handleCropped}
      />
    </div>
  )
}

export default CropImageField
