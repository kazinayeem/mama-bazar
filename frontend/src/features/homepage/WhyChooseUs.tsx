import MotionReveal from '../../components/common/MotionReveal'
import type { HomepageContentItem } from '../../types/homepage'
import { iconByName } from './iconMap'

interface WhyChooseUsProps {
  items: HomepageContentItem[]
  dark?: boolean
}

const WhyChooseUs = ({ items, dark }: WhyChooseUsProps) => {
  if (items.length === 0) return null
  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {items.map((item, index) => {
        const Icon = iconByName(item.icon)
        return (
          <MotionReveal delay={index * 0.06} key={`${item.title}-${index}`}>
            <div
              className={`h-full rounded-[18px] p-6 transition hover:-translate-y-1 ${
                dark
                  ? 'border border-white/10 bg-white/5 hover:border-white/25 hover:bg-white/10'
                  : 'border border-slate-100 bg-slate-50/70 shadow-soft hover:shadow-card dark:border-slate-800 dark:bg-slate-800/40'
              }`}
            >
              <span
                className={`flex h-12 w-12 items-center justify-center rounded-2xl ${
                  dark ? 'bg-accent/20 text-accent' : 'bg-primary/10 text-primary-foreground'
                }`}
              >
                <Icon size={22} />
              </span>
              <p className={`mt-4 font-headline text-base font-extrabold ${dark ? 'text-white' : 'text-slate-900 dark:text-white'}`}>
                {item.title}
              </p>
              {item.text && (
                <p className={`mt-2 text-sm leading-6 ${dark ? 'text-slate-400' : 'text-slate-500 dark:text-slate-400'}`}>{item.text}</p>
              )}
            </div>
          </MotionReveal>
        )
      })}
    </div>
  )
}

export default WhyChooseUs
