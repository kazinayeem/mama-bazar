import { useState } from 'react'
import { ImageOff } from 'lucide-react'
import { cn } from '@/lib/utils'

interface SmartImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {
  /** Optional custom placeholder rendered instead of the default icon box */
  placeholder?: React.ReactNode
  /** Renders a square muted box instead of the broken-image icon */
  icon?: React.ReactNode
}

/**
 * Image with a clean fallback. Never shows broken-browser image icons:
 * null/undefined/empty src renders the placeholder, and onError swaps in the
 * placeholder too.
 */
const SmartImage = ({ src, alt = '', className, placeholder, icon, ...rest }: SmartImageProps) => {
  const [failed, setFailed] = useState(false)
  const hasSrc = typeof src === 'string' && src.trim().length > 0 && !failed

  if (!hasSrc) {
    return placeholder ? (
      <>{placeholder}</>
    ) : (
      <div
        className={cn('flex items-center justify-center bg-muted/50 text-muted-foreground/40', className)}
        aria-hidden
      >
        {icon ?? <ImageOff className="h-6 w-6" />}
      </div>
    )
  }

  return (
    <img
      src={src}
      alt={alt}
      loading="lazy"
      onError={() => setFailed(true)}
      className={className}
      {...rest}
    />
  )
}

export default SmartImage
