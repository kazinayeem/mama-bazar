import { AnimatePresence, motion, useReducedMotion, type PanInfo } from 'framer-motion'
import { ChevronLeft, ChevronRight, Expand, X } from 'lucide-react'
import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { resolveUrl } from '../../lib/apiConfig'
import { EASE } from '../../lib/motion'

interface ProductGalleryProps {
  images: string[]
  title: string
  currentSrc: string
  activeIndex: number
  onSelect: (index: number) => void
}

const controlClass =
  'flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/25 active:scale-95'

const ProductGallery = ({ images, title, currentSrc, activeIndex, onSelect }: ProductGalleryProps) => {
  const reduceMotion = useReducedMotion()
  const [viewerOpen, setViewerOpen] = useState(false)
  const [viewerIndex, setViewerIndex] = useState(0)
  const dragTriggered = useRef(false)

  const resolvedImages = useMemo(() => images.map(resolveUrl), [images])
  const resolvedCurrent = resolveUrl(currentSrc)
  const highlightedIndex = resolvedImages.findIndex((image) => image === resolvedCurrent)

  const openViewer = useCallback(() => {
    const start = highlightedIndex !== -1 ? highlightedIndex : Math.min(activeIndex, resolvedImages.length - 1)
    setViewerIndex(start)
    setViewerOpen(true)
  }, [highlightedIndex, activeIndex, resolvedImages.length])

  const closeViewer = useCallback(() => setViewerOpen(false), [])

  const next = useCallback(
    () => setViewerIndex((i) => (i + 1) % resolvedImages.length),
    [resolvedImages.length],
  )

  const prev = useCallback(
    () => setViewerIndex((i) => (i - 1 + resolvedImages.length) % resolvedImages.length),
    [resolvedImages.length],
  )

  useEffect(() => {
    if (!viewerOpen) return
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') closeViewer()
      else if (event.key === 'ArrowRight') next()
      else if (event.key === 'ArrowLeft') prev()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [viewerOpen, closeViewer, next, prev])

  useEffect(() => {
    if (!viewerOpen) return
    const previous = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      document.body.style.overflow = previous
    }
  }, [viewerOpen])

  const handleDragEnd = (_event: unknown, info: PanInfo) => {
    if (resolvedImages.length < 2) return
    if (Math.abs(info.offset.x) < 8) return
    dragTriggered.current = true
    if (info.offset.x < -60) next()
    else if (info.offset.x > 60) prev()
  }

  const transition = { duration: reduceMotion ? 0 : 0.25, ease: EASE }
  const hasMultiple = resolvedImages.length > 1

  return (
    <>
      <div className="overflow-hidden rounded-2xl border border-slate-200/70 bg-[#F8F8F8]">
        {resolvedImages.length > 0 ? (
          <button
            aria-haspopup="dialog"
            aria-label="Open product image"
            className="group relative block w-full cursor-zoom-in focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
            onClick={openViewer}
            type="button"
          >
            <img
              alt={`${title} - Mama Bazar`}
              className="aspect-square w-full object-contain"
              loading="eager"
              src={resolvedCurrent}
            />
            <span className="pointer-events-none absolute bottom-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-within:opacity-100">
              <Expand size={15} />
            </span>
          </button>
        ) : (
          <div className="flex aspect-square w-full items-center justify-center text-sm font-semibold text-slate-600">
            No image available
          </div>
        )}
      </div>

      {hasMultiple && (
        <div className="mt-4 grid grid-cols-4 gap-3">
          {resolvedImages.map((image, index) => (
            <button
              aria-label={`View image ${index + 1}`}
              className={`overflow-hidden rounded-xl transition ${
                highlightedIndex === index ? 'ring-2 ring-primary ring-offset-2' : 'opacity-70 hover:opacity-100'
              }`}
              key={image}
              onClick={() => onSelect(index)}
              type="button"
            >
              <img alt={`${title} ${index + 1}`} className="aspect-square w-full object-cover" loading="lazy" src={image} />
            </button>
          ))}
        </div>
      )}

      <AnimatePresence>
        {viewerOpen && (
          <motion.div
            aria-label={`${title} images`}
            aria-modal="true"
            animate={{ opacity: 1 }}
            className="fixed inset-0 z-[400] flex flex-col bg-slate-950/95 backdrop-blur-sm"
            exit={{ opacity: 0 }}
            initial={{ opacity: 0 }}
            onClick={() => {
              if (dragTriggered.current) {
                dragTriggered.current = false
                return
              }
              closeViewer()
            }}
            role="dialog"
            transition={transition}
          >
            <div className="flex shrink-0 items-center justify-end p-4">
              <button
                aria-label="Close image viewer"
                autoFocus
                className={`${controlClass} bg-white/10`}
                onClick={closeViewer}
                type="button"
              >
                <X size={22} />
              </button>
            </div>

            {hasMultiple && (
              <>
                <button
                  aria-label="Previous image"
                  className={`${controlClass} absolute left-3 top-1/2 -translate-y-1/2 sm:left-5`}
                  onClick={prev}
                  type="button"
                >
                  <ChevronLeft size={26} />
                </button>
                <button
                  aria-label="Next image"
                  className={`${controlClass} absolute right-3 top-1/2 -translate-y-1/2 sm:right-5`}
                  onClick={next}
                  type="button"
                >
                  <ChevronRight size={26} />
                </button>
              </>
            )}

            <motion.div
              animate={{ opacity: 1, scale: 1 }}
              className="flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-16"
              drag={hasMultiple ? 'x' : false}
              dragConstraints={{ left: 0, right: 0 }}
              dragElastic={0.12}
              exit={{ opacity: 0, scale: 0.97 }}
              initial={{ opacity: 0, scale: 0.95 }}
              onDragEnd={handleDragEnd}
              onDragStart={() => {
                dragTriggered.current = false
              }}
              transition={transition}
            >
              <AnimatePresence initial={false} mode="wait">
                <motion.img
                  alt={`${title} image ${viewerIndex + 1}`}
                  animate={{ opacity: 1, scale: 1 }}
                  className="max-h-full max-w-full object-contain"
                  exit={{ opacity: 0, scale: 0.97 }}
                  initial={{ opacity: 0, scale: 0.95 }}
                  key={resolvedImages[viewerIndex]}
                  onClick={(event) => event.stopPropagation()}
                  src={resolvedImages[viewerIndex]}
                  transition={transition}
                />
              </AnimatePresence>
            </motion.div>

            {hasMultiple && (
              <p className="shrink-0 pb-5 text-center text-sm font-semibold text-white/70">
                {viewerIndex + 1} / {resolvedImages.length}
              </p>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </>
  )
}

export default ProductGallery