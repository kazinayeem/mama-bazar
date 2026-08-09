import { useCallback, useRef, useState } from 'react'
import Cropper, { type Area, type Point } from 'react-easy-crop'
import { Check, Loader2, ZoomIn } from 'lucide-react'
import { toast } from 'sonner'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

const readFile = (src: string): Promise<HTMLImageElement> =>
  new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error('Could not load the image'))
    img.src = src
  })

const cropImage = async (src: string, crop: Area, fileName: string): Promise<File> => {
  const image = await readFile(src)
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(crop.width)
  canvas.height = Math.round(crop.height)
  const ctx = canvas.getContext('2d')
  if (!ctx) throw new Error('Canvas not supported')
  ctx.drawImage(image, crop.x, crop.y, crop.width, crop.height, 0, 0, canvas.width, canvas.height)
  return new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => {
        if (!blob) {
          reject(new Error('Could not encode the cropped image'))
          return
        }
        resolve(new File([blob], fileName.replace(/\.[^.]+$/, '') + '-cropped.jpg', { type: 'image/jpeg' }))
      },
      'image/jpeg',
      0.92,
    )
  })
}

interface ImageCropperDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  /** Source image URL (object URL or remote URL) */
  imageSrc: string
  fileName: string
  aspect: number
  aspectLabel: string
  onCropped: (file: File) => Promise<void> | void
}

/**
 * Client-side cropping dialog. Produces a cropped JPEG file matching the
 * requested aspect ratio (1:1 categories/brands/products, 16:9 banners,
 * 4:5 mobile hero). The caller uploads the result to Cloudinary.
 */
const ImageCropperDialog = ({
  open,
  onOpenChange,
  imageSrc,
  fileName,
  aspect,
  aspectLabel,
  onCropped,
}: ImageCropperDialogProps) => {
  const [crop, setCrop] = useState<Point>({ x: 0, y: 0 })
  const [zoom, setZoom] = useState(1)
  const [croppedArea, setCroppedArea] = useState<Area | null>(null)
  const [busy, setBusy] = useState(false)
  const zoomRef = useRef<HTMLInputElement>(null)

  const onCropComplete = useCallback((_area: Area, croppedPixels: Area) => {
    setCroppedArea(croppedPixels)
  }, [])

  const handleConfirm = async () => {
    if (!croppedArea) return
    setBusy(true)
    try {
      const file = await cropImage(imageSrc, croppedArea, fileName)
      await onCropped(file)
      onOpenChange(false)
    } catch (err) {
      setBusy(false)
      toast.error(err instanceof Error ? err.message : 'Could not crop the image')
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center justify-between gap-4">
            Crop image
            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
              {aspectLabel}
            </span>
          </DialogTitle>
        </DialogHeader>

        <div className="relative h-64 w-full overflow-hidden rounded-lg bg-black/80">
          <Cropper
            image={imageSrc}
            crop={crop}
            zoom={zoom}
            aspect={aspect || undefined}
            onCropChange={setCrop}
            onZoomChange={setZoom}
            onCropComplete={onCropComplete}
            showGrid
          />
        </div>

        <div className="space-y-2">
          <Label className="flex items-center gap-1.5 text-xs">
            <ZoomIn className="h-3.5 w-3.5" /> Zoom
          </Label>
          <input
            ref={zoomRef}
            type="range"
            min={1}
            max={4}
            step={0.05}
            value={zoom}
            onChange={(e) => setZoom(Number(e.target.value))}
            className="w-full accent-primary"
          />
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleConfirm} disabled={busy || !croppedArea}>
            {busy ? <Loader2 className="mr-1 h-4 w-4 animate-spin" /> : <Check className="mr-1 h-4 w-4" />}
            Apply crop
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

export default ImageCropperDialog
