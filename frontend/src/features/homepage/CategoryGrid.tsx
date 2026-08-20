import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useRef, useState, useEffect, useCallback } from 'react'
import { Link } from 'react-router-dom'
import { resolveUrl } from '@/lib/apiConfig'
import { getCloudinaryCategoryUrl } from '@/lib/cloudinary'
import type { Category } from '../../types'

interface CategoryGridProps {
  items: Category[]
  columns?: number
}

const CategoryGrid = ({ items }: CategoryGridProps) => {
  const trackRef = useRef<HTMLDivElement>(null)
  const [canScrollLeft, setCanScrollLeft] = useState(false)
  const [canScrollRight, setCanScrollRight] = useState(false)

  const checkScroll = useCallback(() => {
    const el = trackRef.current
    if (!el) return
    setCanScrollLeft(el.scrollLeft > 8)
    setCanScrollRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 8)
  }, [])

  useEffect(() => {
    checkScroll()
    const el = trackRef.current
    if (!el) return
    el.addEventListener('scroll', checkScroll, { passive: true })
    window.addEventListener('resize', checkScroll)
    return () => {
      el.removeEventListener('scroll', checkScroll)
      window.removeEventListener('resize', checkScroll)
    }
  }, [checkScroll, items])

  const scroll = (direction: 'left' | 'right') => {
    const el = trackRef.current
    if (!el) return
    const amount = el.clientWidth * 0.75
    el.scrollBy({ left: direction === 'left' ? -amount : amount, behavior: 'smooth' })
  }

  if (items.length === 0) return null

  return (
    <div className="relative">
      {/* Left arrow */}
      {canScrollLeft && (
        <button
          aria-label="Scroll categories left"
          className="absolute -left-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-brand-green-100 bg-white text-brand-green-600 shadow-md transition hover:scale-105 hover:border-brand-green-400 hover:bg-brand-green-50 active:scale-95"
          onClick={() => scroll('left')}
          type="button"
        >
          <ChevronLeft size={17} />
        </button>
      )}

      {/* Right arrow */}
      {canScrollRight && (
        <button
          aria-label="Scroll categories right"
          className="absolute -right-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-brand-green-100 bg-white text-brand-green-600 shadow-md transition hover:scale-105 hover:border-brand-green-400 hover:bg-brand-green-50 active:scale-95"
          onClick={() => scroll('right')}
          type="button"
        >
          <ChevronRight size={17} />
        </button>
      )}

      {/* Scroll track */}
      <div
        ref={trackRef}
        className="no-scrollbar flex gap-3 overflow-x-auto pb-3 pt-1.5 scroll-smooth"
        style={{ scrollSnapType: 'x mandatory' }}
      >
        {items.map((category) => (
          <CategoryChip key={category.slug} category={category} />
        ))}
      </div>
    </div>
  )
}

const CategoryChip = ({ category }: { category: Category }) => {
  const [imgFailed, setImgFailed] = useState(false)
  const imgSrc = category.image ? getCloudinaryCategoryUrl(resolveUrl(category.image)) : null

  return (
    <Link
      aria-label={`Shop ${category.name}`}
      className="group flex w-[138px] shrink-0 flex-col items-center gap-2.5 rounded-2xl border border-brand-green-100 bg-white px-3 py-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-green-300 hover:bg-brand-green-50 hover:shadow-card sm:w-[150px]"
      style={{ scrollSnapAlign: 'start' }}
      to={`/shop?category=${category.slug}`}
    >
      {/* Image circle */}
      <span className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-green-50 ring-1 ring-brand-green-100 transition duration-300 group-hover:scale-105 group-hover:ring-brand-green-300 sm:h-[68px] sm:w-[68px]">
        {imgSrc && !imgFailed ? (
          <img
            alt={category.name}
            className="h-full w-full object-cover"
            loading="lazy"
            onError={() => setImgFailed(true)}
            src={imgSrc}
          />
        ) : (
          <span className="font-headline text-2xl font-black text-brand-green-700">
            {category.name.slice(0, 1).toUpperCase()}
          </span>
        )}
      </span>

      {/* Label */}
      <span className="line-clamp-1 text-center text-xs font-bold leading-tight text-slate-800 transition group-hover:text-brand-green-600">
        {category.name}
      </span>

      {/* Product count */}
      {typeof category.productCount === 'number' && category.productCount > 0 && (
        <span className="rounded-full bg-brand-green-50 px-2 py-0.5 text-[10px] font-semibold text-brand-green-600 transition group-hover:bg-brand-green-100 group-hover:text-brand-green-700">
          {category.productCount} products
        </span>
      )}
    </Link>
  )
}

export default CategoryGrid
