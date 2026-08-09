import { AnimatePresence, motion } from 'framer-motion'
import { ArrowRight, ChevronLeft, ChevronRight, ImageOff } from 'lucide-react'
import { useCallback, useEffect, useMemo, useState, type CSSProperties } from 'react'
import { Link } from 'react-router-dom'
import type { HomepageHeroSlide } from '../../types/homepage'

interface HeroCarouselProps {
  slides: HomepageHeroSlide[]
  loading?: boolean
}

const AUTOPLAY_MS = 5000

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:5000'

const resolveUrl = (url?: string) => {
  if (!url) return ''
  const absolute = url.startsWith('/') ? `${API_BASE}${url}` : url
  if (/^https:\/\/res\.cloudinary\.com\//.test(absolute)) {
    return absolute.replace(
      /^(https:\/\/res\.cloudinary\.com\/[^/]+\/image\/upload\/)(\/?v\d+\/.*)$/,
      '$1f_auto,q_auto$2',
    )
  }
  return absolute
}

const pickImage = (slide: HomepageHeroSlide) => {
  const desktop = resolveUrl(slide.desktopImage)
  const tablet = resolveUrl(slide.tabletImage) || desktop
  const mobile = resolveUrl(slide.mobileImage) || tablet
  return { desktop, tablet, mobile }
}

const HeroSlide = ({ slide, priority }: { slide: HomepageHeroSlide; priority?: boolean }) => {
  const images = pickImage(slide)
  const [imageFailed, setImageFailed] = useState(false)
  const textColor = slide.textColor || '#ffffff'
  const isExternal = (url?: string) => !!url && /^https?:\/\//.test(url)

  const renderButton = (text: string, url: string, primary: boolean) => {
    const className = primary
      ? 'inline-flex items-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-accent-600 active:scale-95'
      : 'inline-flex items-center gap-2 rounded-lg border border-white/40 bg-white/15 px-5 py-2.5 text-sm font-bold backdrop-blur transition hover:bg-white/25 active:scale-95'

    const inner = (
      <>
        <span>{text}</span>
        {primary && <ArrowRight size={15} />}
      </>
    )
    if (isExternal(url)) {
      return (
        <a className={className} href={url} rel="noopener noreferrer" style={!primary ? { color: textColor } : undefined} target="_blank">
          {inner}
        </a>
      )
    }
    return (
      <Link className={className} style={!primary ? { color: textColor } : undefined} to={url}>
        {inner}
      </Link>
    )
  }

  return (
    <div className="relative h-full w-full overflow-hidden">
      {imageFailed ? (
        <div className="absolute inset-0 bg-gradient-to-br from-primary-950 via-slate-900 to-slate-950">
          <div className="absolute right-4 top-4 flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/60 backdrop-blur">
            <ImageOff size={12} />
            Image unavailable
          </div>
        </div>
      ) : (
        <picture>
          <source media="(min-width: 1024px)" srcSet={images.desktop} />
          <source media="(min-width: 640px)" srcSet={images.tablet} />
          <img
            alt={slide.title || slide.badge || 'Promotional banner'}
            className="absolute inset-0 h-full w-full object-cover object-center"
            draggable={false}
            fetchPriority={priority ? 'high' : 'auto'}
            loading={priority ? 'eager' : 'lazy'}
            onError={() => setImageFailed(true)}
            src={images.mobile}
          />
        </picture>
      )}

      {/* Subtle overlay — left-to-right gradient, not a full dark box */}
      {slide.overlay !== false && (
        <div
          className="absolute inset-0"
          style={{
            background:
              slide.alignment === 'right'
                ? 'linear-gradient(270deg, rgba(2,6,23,0.65) 0%, rgba(2,6,23,0.15) 50%, rgba(2,6,23,0) 75%)'
                : slide.alignment === 'center'
                  ? 'linear-gradient(180deg, rgba(2,6,23,0.3) 0%, rgba(2,6,23,0.08) 50%, rgba(2,6,23,0.32) 100%)'
                  : 'linear-gradient(90deg, rgba(2,6,23,0.65) 0%, rgba(2,6,23,0.15) 50%, rgba(2,6,23,0) 75%)',
            opacity: slide.overlayOpacity ?? 0.7,
          } as CSSProperties}
        />
      )}

      {/* Content */}
      <div
        className={`relative z-10 flex h-full flex-col justify-center px-6 py-8 sm:px-10 lg:px-16 ${
          slide.alignment === 'center'
            ? 'items-center text-center'
            : slide.alignment === 'right'
              ? 'items-end text-right'
              : 'items-start text-left'
        }`}
      >
        <div className="max-w-sm sm:max-w-md">
          {slide.badge && (
            <span
              className="mb-3 inline-flex items-center rounded-full bg-accent px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white"
            >
              {slide.badge}
            </span>
          )}

          {slide.title && (
            <h2
              className="font-headline text-2xl font-black leading-tight tracking-tight sm:text-3xl lg:text-4xl"
              style={{ color: textColor, textShadow: '0 1px 8px rgba(2,6,23,0.4)' }}
            >
              {slide.title}
            </h2>
          )}

          {slide.subtitle && (
            <p className="mt-2 text-sm font-semibold sm:text-base" style={{ color: textColor }}>
              {slide.subtitle}
            </p>
          )}

          {slide.description && (
            <p className="mt-2 text-xs leading-6 opacity-90 sm:text-sm" style={{ color: textColor }}>
              {slide.description}
            </p>
          )}

          {(slide.primaryButtonText || slide.secondaryButtonText) && (
            <div className="mt-5 flex flex-wrap gap-2.5">
              {slide.primaryButtonText && slide.primaryButtonUrl &&
                renderButton(slide.primaryButtonText, slide.primaryButtonUrl, true)}
              {slide.secondaryButtonText && slide.secondaryButtonUrl &&
                renderButton(slide.secondaryButtonText, slide.secondaryButtonUrl, false)}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

const HeroCarousel = ({ slides, loading }: HeroCarouselProps) => {
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)
  const [direction, setDirection] = useState(1)

  const count = slides.length
  const safeIndex = count > 0 ? index % count : 0

  const go = useCallback(
    (next: number, dir?: number) => {
      if (count === 0) return
      setDirection(dir ?? (next > safeIndex ? 1 : -1))
      setIndex(((next % count) + count) % count)
    },
    [count, safeIndex],
  )

  const next = useCallback(() => go(safeIndex + 1, 1), [go, safeIndex])
  const prev = useCallback(() => go(safeIndex - 1, -1), [go, safeIndex])

  useEffect(() => {
    if (count <= 1 || paused) return
    const timer = setTimeout(next, AUTOPLAY_MS)
    return () => clearTimeout(timer)
  }, [count, paused, next, safeIndex])

  const variants = useMemo(
    () => ({
      enter: (dir: number) => ({ x: dir > 0 ? '100%' : '-100%', opacity: 0 }),
      center: { x: 0, opacity: 1 },
      exit: (dir: number) => ({ x: dir > 0 ? '-100%' : '100%', opacity: 0 }),
    }),
    [],
  )

  if (loading) {
    return (
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="h-[260px] w-full animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800 sm:h-[320px] lg:h-[400px]" />
      </div>
    )
  }

  if (count === 0) return null

  return (
    <section
      aria-label="Promotions"
      className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
    >
      <div
        className="group relative select-none overflow-hidden rounded-2xl"
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
      >
        {/* Fixed compact height — the critical fix */}
        <div className="relative h-[260px] w-full overflow-hidden sm:h-[320px] lg:h-[400px]">
          <AnimatePresence custom={direction} initial={false} mode="popLayout">
            <motion.div
              key={slides[safeIndex].id}
              aria-hidden={safeIndex !== index}
              className="absolute inset-0 h-full w-full"
              custom={direction}
              variants={variants}
              initial="enter"
              animate="center"
              exit="exit"
              transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }}
              drag="x"
              dragConstraints={{ left: 0, right: 0 }}
              dragElastic={0.06}
              onDragEnd={(_, info) => {
                if (info.offset.x < -50) next()
                else if (info.offset.x > 50) prev()
              }}
            >
              <HeroSlide priority={safeIndex === 0} slide={slides[safeIndex]} />
            </motion.div>
          </AnimatePresence>

          {/* Arrows */}
          {count > 1 && (
            <>
              <button
                aria-label="Previous slide"
                className="absolute left-3 top-1/2 z-20 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 active:scale-95 sm:h-9 sm:w-9 lg:opacity-0 lg:group-hover:opacity-100"
                onClick={prev}
                type="button"
              >
                <ChevronLeft size={18} />
              </button>
              <button
                aria-label="Next slide"
                className="absolute right-3 top-1/2 z-20 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white backdrop-blur transition hover:bg-white/40 active:scale-95 sm:h-9 sm:w-9 lg:opacity-0 lg:group-hover:opacity-100"
                onClick={next}
                type="button"
              >
                <ChevronRight size={18} />
              </button>
            </>
          )}

          {/* Dots */}
          {count > 1 && (
            <div className="absolute inset-x-0 bottom-3 z-20 flex items-center justify-center gap-1.5">
              {slides.map((slide, dotIndex) => (
                <button
                  aria-label={`Go to slide ${dotIndex + 1}`}
                  className="flex h-4 items-center"
                  key={slide.id}
                  onClick={() => go(dotIndex, dotIndex > safeIndex ? 1 : -1)}
                  type="button"
                >
                  <span
                    className={`block h-1.5 rounded-full transition-all duration-300 ${
                      dotIndex === safeIndex ? 'w-6 bg-white' : 'w-1.5 bg-white/50'
                    }`}
                  />
                </button>
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  )
}

export default HeroCarousel