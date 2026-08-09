import { ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { HomepageSectionConfig } from '../../types/homepage'

interface SectionShellProps {
  section: HomepageSectionConfig
  children: React.ReactNode
  id?: string
}

const bgClass = (background?: string) => {
  switch (background) {
    case 'muted':
      return 'bg-slate-50 dark:bg-slate-900/60'
    case 'dark':
      return 'bg-slate-950 dark:bg-slate-950'
    default:
      return 'bg-white dark:bg-slate-900'
  }
}

const eyebrowColor = (background?: string) =>
  background === 'dark' ? 'text-accent' : 'text-primary'

const titleColor = (background?: string) =>
  background === 'dark' ? 'text-white' : 'text-slate-900 dark:text-white'

const subtitleColor = (background?: string) =>
  background === 'dark' ? 'text-slate-400' : 'text-slate-500 dark:text-slate-400'

const SectionShell = ({ section, children, id }: SectionShellProps) => {
  const title = section.title?.trim()
  return (
    <section className={`py-6 lg:py-8 ${bgClass(section.background)}`} id={id}>
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {(title || section.eyebrow?.trim()) && (
          <div className="mb-4 flex items-center justify-between gap-4 lg:mb-5">
            <div className="min-w-0">
              {section.eyebrow?.trim() && (
                <p className={`mb-1 text-[10px] font-bold uppercase tracking-[0.18em] ${eyebrowColor(section.background)}`}>
                  {section.eyebrow}
                </p>
              )}
              {title && (
                <h2 className={`font-headline text-xl font-bold sm:text-2xl ${titleColor(section.background)}`}>
                  {title}
                </h2>
              )}
              {section.subtitle?.trim() && (
                <p className={`mt-1 text-sm ${subtitleColor(section.background)}`}>{section.subtitle}</p>
              )}
            </div>
            {section.ctaText?.trim() && section.ctaUrl?.trim() && (
              <Link
                className={`group inline-flex shrink-0 items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-semibold transition ${
                  section.background === 'dark'
                    ? 'border-white/20 text-white hover:border-white/50'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-primary hover:text-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200'
                }`}
                to={section.ctaUrl}
              >
                {section.ctaText}
                <ArrowRight size={13} className="transition-transform group-hover:translate-x-0.5" />
              </Link>
            )}
          </div>
        )}
        {children}
      </div>
    </section>
  )
}

export default SectionShell
