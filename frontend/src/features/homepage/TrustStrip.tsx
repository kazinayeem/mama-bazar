import type { HomepageContentItem } from '../../types/homepage'
import { iconByName } from './iconMap'

interface TrustStripProps {
  items: HomepageContentItem[]
}

const TrustStrip = ({ items }: TrustStripProps) => {
  if (items.length === 0) return null
  return (
    <section className="border-y border-slate-100 bg-white dark:border-slate-800 dark:bg-slate-900">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="no-scrollbar flex overflow-x-auto py-4 sm:grid sm:grid-cols-2 sm:gap-4 sm:overflow-visible lg:grid-cols-4">
          {items.map((item, index) => {
            const Icon = iconByName(item.icon)
            return (
              <div
                key={`${item.title}-${index}`}
                className="flex shrink-0 items-center gap-3 px-4 py-2 sm:px-0"
                style={{ minWidth: '180px' }}
              >
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                  <Icon size={18} />
                </span>
                <div className="min-w-0">
                  <p className="text-xs font-bold text-slate-900 dark:text-white">{item.title}</p>
                  {item.text && <p className="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{item.text}</p>}
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
