import { AnimatePresence, motion, useReducedMotion } from 'framer-motion'
import { ArrowRight, ChevronLeft, ChevronRight, ImageOff } from 'lucide-react'
import { useCallback, useEffect, useMemo, useState, type CSSProperties } from 'react'
import { Link } from 'react-router-dom'
import { resolveImageUrl, getCloudinaryHeroUrl } from '@/lib/cloudinary'
import type { HomepageHeroSlide } from '../../types/homepage'

interface HeroCarouselProps {
  slides: HomepageHeroSlide[]
  loading?: boolean
}

const AUTOPLAY_MS = 4500

/**
 * Hero slide images are stored as Cloudinary `secure_url`s.
 * We apply per-breakpoint width constraints with `c_limit` (downscale-only, no
 * crop/pad) so the browser never downloads a multi-megabyte original for a
 * small viewport. Desktop serves up to ~1280px, tablet ~900px, mobile ~640px —
 * matching the `<picture>` sources below. The hero's `object-contain` display
 * is visually identical to the source, just a fraction of the bytes. This is
 * the LCP element, so the smaller + preconnected transfer directly lowers LCP.
 * For non-Cloudinary URLs the helper returns the original URL unchanged.
 */
const resolveHeroUrl = (url: string | undefined, width: number) =>
  getCloudinaryHeroUrl(resolveImageUrl(url), width)

const pickImage = (slide: HomepageHeroSlide) => {
  const desktop = resolveHeroUrl(slide.desktopImage, 1280)
  const tablet = resolveHeroUrl(slide.tabletImage, 900) || desktop
  const mobile = resolveHeroUrl(slide.mobileImage, 640) || tablet
  return { desktop, tablet, mobile }
}

const HeroSlide = ({ slide, priority }: { slide: HomepageHeroSlide; priority?: boolean }) => {
  const images = pickImage(slide)
  const [imageFailed, setImageFailed] = useState(false)
  const [imageLoaded, setImageLoaded] = useState(false)
  const isExternal = (url?: string) => !!url && /^https?:\/\//.test(url)

  // Cached images can already be complete when the ref attaches — surface that
  // state so the skeleton fades out immediately instead of waiting for onLoad.
  const setImgRef = useCallback((node: HTMLImageElement | null) => {
    if (node?.complete) setImageLoaded(true)
  }, [])

  // A slide with the overlay switched off is a light track: keep the image at
  // full brightness and default to dark text so it stays readable.
  const isLightTrack = slide.overlay === false
  const textColor = slide.textColor || (isLightTrack ? '#0f172a' : '#ffffff')
  const hasImage = !!(images.desktop || images.tablet || images.mobile)
  const showImage = hasImage && !imageFailed

  const renderButton = (text: string, url: string, primary: boolean) => {
    /* Cinematic track CTAs: white-stroked pill on dark. Light track uses the
       ink pill so contrast holds on bright imagery. */
    const pill = 'inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold transition active:scale-95'
    const className = isLightTrack
      ? primary
        ? `${pill} bg-slate-900 text-white shadow-lg shadow-slate-900/25 hover:bg-slate-800`
        : `${pill} border border-slate-900/25 bg-slate-900/5 text-slate-900 backdrop-blur hover:bg-slate-900/10`
      : primary
        ? `${pill} border-2 border-white bg-transparent text-white hover:bg-white/10`
        : `${pill} border border-white/40 bg-white/15 text-white backdrop-blur hover:bg-white/25`

    const inner = (
      <>
        <span>{text}</span>
        {primary && <ArrowRight size={15} />}
      </>
    )
    if (isExternal(url)) {
      return (
        <a className={className} href={url} rel="noopener noreferrer" target="_blank">
          {inner}
        </a>
      )
    }
    return (
      <Link className={className} to={url}>
        {inner}
      </Link>
    )
  }

  return (
    <div
      className="relative mt-2 h-full w-full overflow-hidden"
      style={{ backgroundColor: slide.backgroundColor || '#0b1220' }}
    >
      {/* The slide background is always painted underneath so the banner area
          never flashes empty/white while an image loads (or when it is missing). */}
      {!showImage && (
        <div className="absolute right-4 top-4 z-20 flex items-center gap-2 rounded-full border border-white/20 bg-black/30 px-3 py-1.5 text-xs font-medium text-white/80 backdrop-blur">
          <ImageOff size={12} />
          Image unavailable
        </div>
      )}

      {/* Lightweight skeleton while the image loads — fades out on first paint. */}
      {showImage && !imageLoaded && (
        <div aria-hidden="true" className="absolute inset-0 animate-pulse bg-slate-200/60" />
      )}

      {showImage && (
        <picture>
          {images.desktop && <source media="(min-width: 1024px)" srcSet={images.desktop} />}
          {images.tablet && <source media="(min-width: 640px)" srcSet={images.tablet} />}
          <img
            ref={setImgRef}
            alt={slide.title || slide.badge || 'Promotional banner'}
            className={`absolute inset-0 h-full w-full object-contain object-center ${
              priority ? '' : 'transition-opacity duration-300'
            }`}
            draggable={false}
            fetchPriority={priority ? 'high' : 'auto'}
            loading={priority ? 'eager' : 'lazy'}
            decoding={priority ? 'sync' : 'async'}
            width="1200"
            height="480"
            onError={() => setImageFailed(true)}
            onLoad={() => setImageLoaded(true)}
            src={images.mobile}
            style={priority ? undefined : { opacity: imageLoaded ? 1 : 0 }}
          />
        </picture>
      )}

      {/* Subtle overlay — left-to-right gradient, not a full dark box. A minimum
          scrim is enforced on dark tracks so light text never sits on a bright
          image without contrast. */}
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
            opacity: Math.max(slide.overlayOpacity ?? 0.65, 0.35),
          } as CSSProperties}
        />
      )}

      {/* Content */}
      <div
        className={`relative z-10 flex h-full flex-col justify-center px-14 py-8 sm:px-16 lg:px-20 ${
          slide.alignment === 'center'
            ? 'items-center text-center'
            : slide.alignment === 'right'
              ? 'items-end text-right'
              : 'items-start text-left'
        }`}
      >
        <div className="max-w-md sm:max-w-lg">
          {slide.badge && (
            <span
              className={`mb-3 inline-flex items-center rounded-full border px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider backdrop-blur ${
                isLightTrack
                  ? 'border-slate-900/15 bg-slate-900/10 text-slate-900'
                  : 'border-white/25 bg-white/10 text-white'
              }`}
            >
              {slide.badge}
            </span>
          )}

          {slide.title && (
            <h2
              className="font-headline text-3xl font-light leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl"
              style={{
                color: textColor,
                textShadow: isLightTrack ? 'none' : '0 2px 12px rgba(2,6,23,0.45)',
              }}
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

  // Auto-play. Keyed on `safeIndex` so every change — manual or automatic —
  // tears down the previous timer and starts a fresh countdown (reset after
  // user interaction). The cleanup guarantees a single live timer at a time.
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
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="h-[300px] w-full animate-pulse rounded-3xl bg-slate-100 sm:h-[400px] lg:h-[480px]" />
      </div>
    )
  }

  if (count === 0) return null

  // Current slide's mobile image — used only as a height spacer on mobile.
  // Right-sized to 640px (mobile render width) so the spacer fetch is tiny.
  const currentSlide = slides[safeIndex]
  const currentMobileImage =
    resolveHeroUrl(currentSlide.mobileImage, 640) ||
    resolveHeroUrl(currentSlide.tabletImage, 900) ||
    resolveHeroUrl(currentSlide.desktopImage, 1280) ||
    ''

  return (
    <section aria-label="Promotions" className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div
        aria-roledescription="carousel"
        className="group relative select-none overflow-hidden rounded-2xl shadow-lift sm:rounded-3xl"
        onBlur={() => setPaused(false)}
        onFocus={() => setPaused(true)}
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
      >
        {/* Mobile height spacer — image establishes natural height, no black bars.
             fetchPriority=high makes the first slide's LCP image discoverable immediately. */}
        {currentMobileImage && (
          <div className="sm:hidden" aria-hidden="true">
            <img
              alt=""
              className="block w-full"
              src={currentMobileImage}
              width="640"
              height="360"
              fetchPriority="high"
              loading="eager"
            />
          </div>
        )}

        {/* Slides — absolute on mobile (fills spacer height), relative on sm+ with fixed height */}
        <div className="absolute inset-0 sm:relative sm:h-[400px] lg:h-[480px] sm:overflow-hidden">
          <AnimatePresence custom={direction} initial={false}>
            <motion.div
              key={slides[safeIndex].id}
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
        </div>

        {/* Arrows */}
        {count > 1 && (
          <>
            <motion.button
              aria-label="Previous slide"
              className="absolute bottom-0 left-3 top-0 z-20 my-auto flex h-11 w-11 items-center justify-center rounded-full border border-white/40 bg-white/90 text-slate-800 shadow-lg shadow-slate-900/10 backdrop-blur hover:bg-white sm:left-5"
              onClick={prev}
              type="button"
              transition={{ duration: 0.2 }}
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
            >
              <ChevronLeft size={20} />
            </motion.button>
            <motion.button
              aria-label="Next slide"
              className="absolute bottom-0 right-3 top-0 z-20 my-auto flex h-11 w-11 items-center justify-center rounded-full border border-white/40 bg-white/90 text-slate-800 shadow-lg shadow-slate-900/10 backdrop-blur hover:bg-white sm:right-5"
              onClick={next}
              type="button"
              transition={{ duration: 0.2 }}
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
            >
              <ChevronRight size={20} />
            </motion.button>
          </>
        )}

        {/* Dots */}
        {count > 1 && (
          <div className="absolute inset-x-0 bottom-4 z-20 flex items-center justify-center gap-2">
            {slides.map((slide, dotIndex) => (
              <button
                aria-current={dotIndex === safeIndex ? 'true' : undefined}
                aria-label={`Go to slide ${dotIndex + 1}`}
                className="flex h-11 items-center px-1"
                key={slide.id}
                onClick={() => go(dotIndex, dotIndex > safeIndex ? 1 : -1)}
                type="button"
              >
                <motion.span
                  animate={
                    dotIndex === safeIndex
                      ? { width: 32, opacity: 1, scale: 1 }
                      : { width: 8, opacity: 0.5, scale: 1 }
                  }
                  className="block h-2 rounded-full bg-white shadow-sm"
                  initial={false}
                  transition={{ duration: 0.35, ease: 'easeOut' }}
                />
              </button>
            ))}
          </div>
        )}
      </div>
    </section>
  )
}

export default HeroCarousel
