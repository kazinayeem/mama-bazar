import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useRef, useState, useEffect, useCallback } from 'react'
import ProductCard from '../../components/common/ProductCard'
import type { Product } from '../../types'

interface ProductCarouselProps {
  products: Product[]
  onQuickView: (product: Product) => void
  maxItems?: number
}

// How many full cards to show per breakpoint
const getVisibleCount = () => {
  const w = window.innerWidth
  if (w >= 1280) return 5
  if (w >= 1024) return 4
  if (w >= 640) return 3
  return 2
}

const ProductCarousel = ({ products = [], onQuickView, maxItems = 5 }: ProductCarouselProps) => {
  const safeProducts = Array.isArray(products) ? products : []
  const displayProducts = safeProducts.slice(0, maxItems)
  const trackRef = useRef<HTMLDivElement>(null)
  const [canPrev, setCanPrev] = useState(false)
  const [canNext, setCanNext] = useState(false)
  const [visibleCount, setVisibleCount] = useState(getVisibleCount)

  const checkScroll = useCallback(() => {
    const el = trackRef.current
    if (!el) return
    setCanPrev(el.scrollLeft > 8)
    setCanNext(el.scrollLeft < el.scrollWidth - el.clientWidth - 8)
  }, [])

  useEffect(() => {
    const onResize = () => setVisibleCount(getVisibleCount())
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [])

  useEffect(() => {
    checkScroll()
    const el = trackRef.current
    if (!el) return
    el.addEventListener('scroll', checkScroll, { passive: true })
    return () => el.removeEventListener('scroll', checkScroll)
  }, [checkScroll, displayProducts])

  if (displayProducts.length === 0) return null

  const scrollBy = (direction: 'left' | 'right') => {
    const el = trackRef.current
    if (!el) return
    const cardWidth = el.scrollWidth / displayProducts.length
    el.scrollBy({ left: direction === 'left' ? -cardWidth * visibleCount : cardWidth * visibleCount, behavior: 'smooth' })
  }

  // Card min-width based on visible count
  const cardStyle: React.CSSProperties = {
    minWidth: `calc((100% - ${(visibleCount - 1) * 12}px) / ${visibleCount})`,
    maxWidth: `calc((100% - ${(visibleCount - 1) * 12}px) / ${visibleCount})`,
    scrollSnapAlign: 'start',
  }

  return (
    <div className="relative">
      {/* Prev button */}
      {canPrev && (
        <button
          aria-label="Previous products"
          className="absolute -left-4 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-emerald-500 hover:text-emerald-600"
          onClick={() => scrollBy('left')}
          type="button"
        >
          <ChevronLeft size={18} />
        </button>
      )}

      {/* Next button */}
      {canNext && (
        <button
          aria-label="Next products"
          className="absolute -right-4 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-emerald-500 hover:text-emerald-600"
          onClick={() => scrollBy('right')}
          type="button"
        >
          <ChevronRight size={18} />
        </button>
      )}

      {/* Track */}
      <div
        ref={trackRef}
        className="no-scrollbar flex items-stretch gap-3 overflow-x-auto"
        style={{ scrollSnapType: 'x mandatory' }}
      >
        {displayProducts.map((product, index) => (
          <div key={product.id} className="h-full" style={cardStyle}>
            <ProductCard index={index} onQuickView={onQuickView} product={product} />
          </div>
        ))}
      </div>
    </div>
  )
}

export default ProductCarousel
