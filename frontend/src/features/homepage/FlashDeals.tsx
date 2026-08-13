import { motion, useReducedMotion } from 'framer-motion'
import { ArrowRight, Flame, Timer } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import type { HomepageFlashSaleWindow } from '../../types/homepage'
import type { Product } from '../../types'
import ProductCarousel from './ProductCarousel'

interface FlashDealsProps {
  section: { title?: string; subtitle?: string; ctaText?: string; ctaUrl?: string }
  products: Product[]
  window?: HomepageFlashSaleWindow
  onQuickView: (product: Product) => void
}

interface TimeLeft {
  days: number
  hours: number
  minutes: number
  seconds: number
}

const pad = (n: number) => String(Math.max(0, n)).padStart(2, '0')

const computeTarget = (window?: HomepageFlashSaleWindow): number => {
  const now = Date.now()
  const endDate = window?.endsAt || window?.end
  if (endDate) {
    const end = new Date(endDate).getTime()
    if (end > now) return end
  }
  if (window?.start) {
    const start = new Date(window.start).getTime()
    if (start > now) return start
    const end = start + 24 * 60 * 60 * 1000
    if (end > now) return end
  }
  const midnight = new Date()
  midnight.setHours(23, 59, 59, 999)
  return midnight.getTime()
}

const useCountdown = (target: number) => {
  const [now, setNow] = useState(() => Date.now())
  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 1000)
    return () => clearInterval(timer)
  }, [])
  const diff = Math.max(0, target - now)
  return useMemo<TimeLeft>(
    () => ({
      days: Math.floor(diff / 86_400_000),
      hours: Math.floor((diff % 86_400_000) / 3_600_000),
      minutes: Math.floor((diff % 3_600_000) / 60_000),
      seconds: Math.floor((diff % 60_000) / 1000),
    }),
    [diff],
  )
}

const TimeUnit = ({ value, label }: { value: string; label: string }) => (
  <div className="flex flex-col items-center gap-0.5">
    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white font-headline text-base font-black tabular-nums text-brand-green-700 shadow-md sm:h-11 sm:w-11">
      {value}
    </span>
    <span className="text-[9px] font-bold uppercase tracking-wider text-white/70">{label}</span>
  </div>
)

const FlashDeals = ({ section, products, window, onQuickView }: FlashDealsProps) => {
  const reduceMotion = useReducedMotion()
  const target = useMemo(() => computeTarget(window), [window])
  const time = useCountdown(target)

  if (products.length === 0) return null

  return (
    <section className="bg-brand-green-50 py-6 lg:py-8">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {/* Flash Deals header panel */}
        <div className="relative mb-4 overflow-hidden rounded-2xl bg-gradient-to-r from-brand-green-dark via-brand-green-600 to-brand-green-dark px-5 py-5 sm:rounded-3xl sm:px-7 sm:py-6 lg:mb-6">
          <div className="pointer-events-none absolute -right-10 -top-10 hidden h-48 w-48 rounded-full bg-brand-orange-500/10 blur-3xl md:block" />
          <div className="pointer-events-none absolute -bottom-16 left-1/3 hidden h-40 w-40 rounded-full bg-brand-orange-400/15 blur-3xl md:block" />
          <div className="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-4">
              <motion.span
                animate={reduceMotion ? undefined : { scale: [1, 1.12, 1] }}
                className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-brand-orange-300/40 bg-brand-orange-500/20 text-brand-orange-300 shadow-lg shadow-black/30"
                transition={reduceMotion ? undefined : { duration: 1.4, repeat: Infinity }}
              >
                <Flame size={22} className="fill-brand-orange-300" />
              </motion.span>
              <div>
                <p className="font-headline text-xl font-light text-white tracking-tight sm:text-2xl">
                  {section.title || 'Flash Deals'}
                </p>
                <p className="mt-0.5 text-xs font-medium text-white/75 sm:text-sm">
                  {section.subtitle || 'Limited-time prices. When they are gone, they are gone.'}
                </p>
              </div>
            </div>

            <div className="flex flex-wrap items-center gap-4">
              <span className="inline-flex items-center gap-1.5 rounded-full border border-brand-orange-300/40 bg-brand-orange-500/20 px-3 py-1.5 text-[11px] font-bold text-brand-orange-200">
                <Timer size={12} /> Ends in
              </span>
              <div className="flex items-start gap-2">
                {time.days > 0 && <TimeUnit label="Days" value={pad(time.days)} />}
                <TimeUnit label="Hrs" value={pad(time.hours)} />
                <TimeUnit label="Min" value={pad(time.minutes)} />
                <TimeUnit label="Sec" value={pad(time.seconds)} />
              </div>
              <Link
                className="group hidden items-center gap-1.5 rounded-full border border-brand-orange-400/50 bg-brand-orange-500 px-4 py-2 text-xs font-bold text-white transition hover:bg-brand-orange-600 lg:inline-flex"
                to="/shop?sale=true"
              >
                View All
                <ArrowRight size={13} className="transition-transform group-hover:translate-x-0.5" />
              </Link>
            </div>
          </div>
        </div>

        <ProductCarousel onQuickView={onQuickView} products={products.slice(0, 10)} />
      </div>
    </section>
  )
}

export default FlashDeals
