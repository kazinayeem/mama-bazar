import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useRef, useState, useEffect, useCallback } from 'react'
import { Link } from 'react-router-dom'
import { resolveUrl } from '@/lib/apiConfig'
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
          className="absolute -left-3 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
          onClick={() => scroll('left')}
          type="button"
        >
          <ChevronLeft size={16} />
        </button>
      )}

      {/* Right arrow */}
      {canScrollRight && (
        <button
          aria-label="Scroll categories right"
          className="absolute -right-3 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
          onClick={() => scroll('right')}
          type="button"
        >
          <ChevronRight size={16} />
        </button>
      )}

      {/* Scroll track */}
      <div
        ref={trackRef}
        className="no-scrollbar flex gap-3 overflow-x-auto pb-1 pt-1 scroll-smooth"
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
  const imgSrc = category.image ? resolveUrl(category.image) : null

  return (
    <Link
      aria-label={`Shop ${category.name}`}
      className="group flex shrink-0 flex-col items-center gap-2 rounded-xl border border-slate-100 bg-white px-3 py-3 transition hover:border-primary/30 hover:bg-primary/5 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-primary/30 dark:hover:bg-primary/10"
      style={{ scrollSnapAlign: 'start', minWidth: '80px', maxWidth: '96px' }}
      to={`/shop?category=${category.slug}`}
    >
      {/* Image circle */}
      <span className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-50 ring-1 ring-slate-100 transition group-hover:ring-primary/30 dark:bg-slate-800 dark:ring-slate-700">
        {imgSrc && !imgFailed ? (
          <img
            alt={category.name}
            className="h-full w-full object-cover"
            loading="lazy"
            onError={() => setImgFailed(true)}
            src={imgSrc}
          />
        ) : (
          <span className="font-headline text-xl font-black text-primary">
            {category.name.slice(0, 1).toUpperCase()}
          </span>
        )}
      </span>

      {/* Label */}
      <span className="line-clamp-2 text-center text-[11px] font-semibold leading-tight text-slate-700 transition group-hover:text-primary dark:text-slate-300">
        {category.name}
      </span>
    </Link>
  )
}

export default CategoryGrid
