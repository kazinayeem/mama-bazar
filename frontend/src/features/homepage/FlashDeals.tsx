import { motion } from 'framer-motion'
import { Flame, Timer } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import type { HomepageFlashSaleWindow } from '../../types/homepage'
import type { Product } from '../../types'
import SectionShell from './SectionShell'
import type { HomepageSectionConfig } from '../../types/homepage'
import ProductCarousel from './ProductCarousel'

interface FlashDealsProps {
  section: HomepageSectionConfig
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
  <div className="flex flex-col items-center">
    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/20 font-headline text-base font-black tabular-nums text-white">
      {value}
    </span>
    <span className="mt-1 text-[9px] font-bold uppercase tracking-wider text-white/70">{label}</span>
  </div>
)

const FlashDeals = ({ section, products, window, onQuickView }: FlashDealsProps) => {
  const target = useMemo(() => computeTarget(window), [window])
  const time = useCountdown(target)

  if (products.length === 0) return null

  return (
    <SectionShell section={section}>
      {/* Compact Flash Deals header */}
      <div className="relative mb-5 overflow-hidden rounded-xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-5 py-4 sm:py-5">
        <div className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-accent/20 blur-2xl" />
        <div className="relative flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <motion.span
              animate={{ scale: [1, 1.15, 1] }}
              className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent text-white"
              transition={{ duration: 1.4, repeat: Infinity }}
            >
              <Flame size={18} className="fill-white" />
            </motion.span>
            <div>
              <p className="font-headline text-lg font-black text-white">Flash Deals</p>
              <p className="text-xs text-accent">Limited stock — while supplies last</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <span className="inline-flex items-center gap-1 rounded-full bg-accent/20 px-2.5 py-1 text-[11px] font-bold text-accent">
              <Timer size={11} /> Ends in
            </span>
            <div className="flex items-start gap-1.5">
              {time.days > 0 && <TimeUnit label="D" value={pad(time.days)} />}
              <TimeUnit label="H" value={pad(time.hours)} />
              <TimeUnit label="M" value={pad(time.minutes)} />
              <TimeUnit label="S" value={pad(time.seconds)} />
            </div>
          </div>
        </div>
      </div>

      <ProductCarousel onQuickView={onQuickView} products={products.slice(0, 10)} />
    </SectionShell>
  )
}

export default FlashDeals
