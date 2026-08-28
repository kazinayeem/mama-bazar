import MotionReveal from '../../components/common/MotionReveal'
import type { HomepageContentItem } from '../../types/homepage'
import { iconByName } from './iconMap'

interface WhyChooseUsProps {
  items: HomepageContentItem[]
  dark?: boolean
}

const WhyChooseUs = ({ items = [], dark }: WhyChooseUsProps) => {
  const safeItems = Array.isArray(items) ? items : []
  if (safeItems.length === 0) return null
  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {safeItems.map((item, index) => {
        const Icon = iconByName(item.icon)
        return (
          <MotionReveal delay={index * 0.06} key={`${item.title}-${index}`}>
            <div
              className={`h-full rounded-[18px] p-6 transition hover:-translate-y-1 ${
                dark
                  ? 'border border-slate-100 bg-white/70 hover:border-slate-200 hover:bg-white'
                  : 'border border-slate-100 bg-slate-50/70 shadow-soft hover:shadow-card'
              }`}
            >
              <span
                className={`flex h-12 w-12 items-center justify-center rounded-2xl ${
                  dark ? 'bg-emerald-50 text-emerald-700' : 'bg-emerald-50 text-emerald-700'
                }`}
              >
                <Icon size={22} />
              </span>
              <p className={`mt-4 font-headline text-base font-extrabold ${dark ? 'text-slate-900' : 'text-slate-900'}`}>
                {item.title}
              </p>
              {item.text && (
                <p className={`mt-2 text-sm leading-6 ${dark ? 'text-slate-600' : 'text-slate-500'}`}>{item.text}</p>
              )}
            </div>
          </MotionReveal>
        )
      })}
    </div>
  )
}

export default WhyChooseUs
