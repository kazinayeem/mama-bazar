import type { HomepageContentItem } from '../../types/homepage'
import { iconByName } from './iconMap'

interface TrustStripProps {
  items: HomepageContentItem[]
}

const TrustStrip = ({ items }: TrustStripProps) => {
  if (items.length === 0) return null
  return (
    <section className="border-y border-slate-100 bg-white dark:border-slate-800 dark:bg-slate-950">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 gap-3 py-5 sm:gap-4 lg:grid-cols-4">
          {items.map((item, index) => {
            const Icon = iconByName(item.icon)
            return (
              <div
                key={`${item.title}-${index}`}
                className="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3.5 transition hover:border-primary/20 hover:bg-primary/5 dark:border-slate-800 dark:bg-slate-900/60 dark:hover:bg-slate-800"
              >
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-primary-700 text-white shadow-sm">
                  <Icon size={18} />
                </span>
                <div className="min-w-0">
                  <p className="truncate text-xs font-bold text-slate-900 sm:text-[13px] dark:text-white">{item.title}</p>
                  {item.text && <p className="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">{item.text}</p>}
                </div>
              </div>
            )
          })}
        </div>
      </div>
    </section>
  )
}

export default TrustStrip
