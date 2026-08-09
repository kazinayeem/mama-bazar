import { Link } from 'react-router-dom'
import type { Brand } from '../../types/admin'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useRef, useState, useEffect, useCallback } from 'react'

interface BrandRowProps {
  items: Brand[]
}

const BrandRow = ({ items }: BrandRowProps) => {
  const trackRef = useRef<HTMLDivElement>(null)
  const [canLeft, setCanLeft] = useState(false)
  const [canRight, setCanRight] = useState(false)

  const checkScroll = useCallback(() => {
    const el = trackRef.current
    if (!el) return
    setCanLeft(el.scrollLeft > 8)
    setCanRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 8)
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

  const scroll = (dir: 'left' | 'right') => {
    const el = trackRef.current
    if (!el) return
    el.scrollBy({ left: dir === 'left' ? -280 : 280, behavior: 'smooth' })
  }

  if (items.length === 0) return null

  return (
    <div className="relative">
      {canLeft && (
        <button
          aria-label="Scroll brands left"
          className="absolute -left-3 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800"
          onClick={() => scroll('left')}
          type="button"
        >
          <ChevronLeft size={15} />
        </button>
      )}
      {canRight && (
        <button
          aria-label="Scroll brands right"
          className="absolute -right-3 top-1/2 z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800"
          onClick={() => scroll('right')}
          type="button"
        >
          <ChevronRight size={15} />
        </button>
      )}

      <div
        ref={trackRef}
        className="no-scrollbar flex gap-3 overflow-x-auto py-1"
        style={{ scrollSnapType: 'x mandatory' }}
      >
        {items.map((brand) => (
          <Link
            key={brand.slug}
            className="group flex shrink-0 flex-col items-center gap-2 rounded-xl border border-slate-100 bg-white px-4 py-3 transition hover:border-primary/30 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900"
            style={{ scrollSnapAlign: 'start', minWidth: '110px' }}
            to={`/shop?brand=${brand.slug}`}
          >
            {brand.logo ? (
              <img alt={brand.name} className="h-10 w-10 rounded-lg object-contain" loading="lazy" src={brand.logo} />
            ) : (
              <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 font-headline text-base font-black text-primary">
                {brand.name.slice(0, 1)}
              </span>
            )}
            <p className="text-center text-[11px] font-semibold text-slate-700 group-hover:text-primary dark:text-slate-300">
              {brand.name}
            </p>
          </Link>
        ))}
      </div>
    </div>
  )
}

export default BrandRow
