import { Link } from 'react-router-dom'
import type { Brand } from '../../types/admin'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useRef, useState, useEffect, useCallback } from 'react'
import { resolveUrl } from '@/lib/apiConfig'

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
    el.scrollBy({ left: dir === 'left' ? -300 : 300, behavior: 'smooth' })
  }

  if (items.length === 0) return null

  return (
    <div className="relative">
      {canLeft && (
        <button
          aria-label="Scroll brands left"
          className="absolute -left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800"
          onClick={() => scroll('left')}
          type="button"
        >
          <ChevronLeft size={16} />
        </button>
      )}
      {canRight && (
        <button
          aria-label="Scroll brands right"
          className="absolute -right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-md text-slate-600 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800"
          onClick={() => scroll('right')}
          type="button"
        >
          <ChevronRight size={16} />
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
            className="group flex w-[130px] shrink-0 flex-col items-center justify-center gap-2.5 rounded-2xl border border-slate-100 bg-white px-4 py-5 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-card dark:border-slate-800 dark:bg-slate-900"
            style={{ scrollSnapAlign: 'start' }}
            to={`/shop?brand=${brand.slug}`}
          >
            {brand.logo ? (
              <span className="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-800">
                <img
                  alt={brand.name}
                  className="h-10 w-10 object-contain transition-transform duration-300 group-hover:scale-110"
                  loading="lazy"
                  src={resolveUrl(brand.logo)}
                />
              </span>
            ) : (
              <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 font-headline text-lg font-black text-primary">
                {brand.name.slice(0, 1)}
              </span>
            )}
            <p className="text-center text-xs font-bold text-slate-700 transition group-hover:text-primary dark:text-slate-200">
              {brand.name}
            </p>
          </Link>
        ))}
      </div>
    </div>
  )
}

export default BrandRow
