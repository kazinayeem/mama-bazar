import { AnimatePresence, motion, useReducedMotion } from 'framer-motion'
import { ArrowRight, ChevronLeft, ChevronRight, ImageOff } from 'lucide-react'
import { useCallback, useEffect, useMemo, useState, type CSSProperties } from 'react'
import { Link } from 'react-router-dom'
import { resolveImageUrl } from '@/lib/cloudinary'
import type { HomepageHeroSlide } from '../../types/homepage'

interface HeroCarouselProps {
  slides: HomepageHeroSlide[]
  loading?: boolean
}

const AUTOPLAY_MS = 5000

/**
 * Hero slide images are stored as Cloudinary `secure_url`s. Those URLs are
 * already valid and load without any transformation, so they are used as-is —
 * injecting transforms via string manipulation is what previously produced
 * malformed paths that Cloudinary rejected with HTTP 400.
 */
const resolveHeroUrl = (url?: string) => resolveImageUrl(url)

const pickImage = (slide: HomepageHeroSlide) => {
  const desktop = resolveHeroUrl(slide.desktopImage)
  const tablet = resolveHeroUrl(slide.tabletImage) || desktop
  const mobile = resolveHeroUrl(slide.mobileImage) || tablet
  return { desktop, tablet, mobile }
}

const HeroSlide = ({ slide, priority }: { slide: HomepageHeroSlide; priority?: boolean }) => {
  const images = pickImage(slide)
  const [imageFailed, setImageFailed] = useState(false)
  const textColor = slide.textColor || '#ffffff'
  const isExternal = (url?: string) => !!url && /^https?:\/\//.test(url)

  const renderButton = (text: string, url: string, primary: boolean) => {
    /* Cinematic track CTAs: white-stroked pill on dark. Mint is reserved for the light track. */
    const className = primary
      ? 'inline-flex items-center gap-2 rounded-full border-2 border-white bg-transparent px-6 py-2.5 text-sm font-bold text-white transition hover:bg-white/10 active:scale-95'
      : 'inline-flex items-center gap-2 rounded-full border border-white/40 bg-white/15 px-6 py-2.5 text-sm font-bold backdrop-blur transition hover:bg-white/25 active:scale-95'

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
                ? 'linear-gradient(270deg, rgba(2,6,23,0.7) 0%, rgba(2,6,23,0.18) 50%, rgba(2,6,23,0) 75%)'
                : slide.alignment === 'center'
                  ? 'linear-gradient(180deg, rgba(2,6,23,0.35) 0%, rgba(2,6,23,0.1) 50%, rgba(2,6,23,0.35) 100%)'
                  : 'linear-gradient(90deg, rgba(2,6,23,0.7) 0%, rgba(2,6,23,0.18) 50%, rgba(2,6,23,0) 75%)',
            opacity: slide.overlayOpacity ?? 0.65,
          } as CSSProperties}
        />
      )}

      {/* Content */}
      <div
        className={`relative z-10 flex h-full flex-col justify-center px-6 py-8 sm:px-12 lg:px-20 ${
          slide.alignment === 'center'
            ? 'items-center text-center'
            : slide.alignment === 'right'
              ? 'items-end text-right'
              : 'items-start text-left'
        }`}
      >
        <div className="max-w-md sm:max-w-lg">
          {slide.badge && (
            <span className="mb-3 inline-flex items-center rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-white backdrop-blur">
              {slide.badge}
            </span>
          )}

          {slide.title && (
            <h2
              className="font-headline text-3xl font-light leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl"
              style={{ color: textColor, textShadow: '0 2px 12px rgba(2,6,23,0.45)' }}
            >
              {slide.title}
            </h2>
          )}

          {slide.subtitle && (
            <p className="mt-3 text-sm font-semibold sm:text-base lg:text-lg" style={{ color: textColor }}>
              {slide.subtitle}
            </p>
          )}

          {slide.description && (
            <p className="mt-2 max-w-md text-xs leading-6 opacity-90 sm:text-sm" style={{ color: textColor }}>
              {slide.description}
            </p>
          )}

          {(slide.primaryButtonText || slide.secondaryButtonText) && (
            <div className="mt-6 flex flex-wrap gap-3">
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
  const reduceMotion = useReducedMotion()

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
    if (count <= 1 || paused || reduceMotion) return
    const timer = setTimeout(next, AUTOPLAY_MS)
    return () => clearTimeout(timer)
  }, [count, paused, next, safeIndex, reduceMotion])

  const variants = useMemo(
    () => ({
      enter: (dir: number) => ({ x: dir > 0 ? '100%' : '-100%', opacity: 0 }),
      center: { x: 0, opacity: 1 },
      exit: (dir: number) => ({ x: dir > 0 ? '-100%' : '100%', opacity: 0 }),
    }),
    [],
  )

  const slideTransition = reduceMotion
    ? { duration: 0.01, ease: 'easeOut' as const }
    : { duration: 0.55, ease: [0.22, 1, 0.36, 1] as const }

  if (loading) {
    return (
      <div className="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
        <div className="h-[300px] w-full animate-pulse rounded-3xl bg-slate-100 sm:h-[400px] lg:h-[480px] dark:bg-slate-800" />
      </div>
    )
  }

  if (count === 0) return null

  return (
    <section aria-label="Promotions" className="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
      <div
        className="group relative select-none overflow-hidden rounded-2xl shadow-lift sm:rounded-3xl"
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
      >
        {/* Fixed compact height — the critical fix */}
        <div className="relative h-[300px] w-full overflow-hidden sm:h-[400px] lg:h-[480px]">
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
              transition={slideTransition}
              drag={reduceMotion ? false : 'x'}
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
                className="absolute left-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-lg backdrop-blur transition hover:bg-white active:scale-95 sm:left-5 lg:opacity-0 lg:group-hover:opacity-100 dark:bg-slate-800/90 dark:text-white"
                onClick={prev}
                type="button"
              >
                <ChevronLeft size={20} />
              </button>
              <button
                aria-label="Next slide"
                className="absolute right-3 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-lg backdrop-blur transition hover:bg-white active:scale-95 sm:right-5 lg:opacity-0 lg:group-hover:opacity-100 dark:bg-slate-800/90 dark:text-white"
                onClick={next}
                type="button"
              >
                <ChevronRight size={20} />
              </button>
            </>
          )}

          {/* Dots */}
          {count > 1 && (
            <div className="absolute inset-x-0 bottom-4 z-20 flex items-center justify-center gap-2">
              {slides.map((slide, dotIndex) => (
                <button
                  aria-label={`Go to slide ${dotIndex + 1}`}
                  className="flex h-11 items-center px-1"
                  key={slide.id}
                  onClick={() => go(dotIndex, dotIndex > safeIndex ? 1 : -1)}
                  type="button"
                >
                  <span
                    className={`block h-2 rounded-full shadow-sm transition-all duration-300 ${
                      dotIndex === safeIndex ? 'w-8 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'
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
