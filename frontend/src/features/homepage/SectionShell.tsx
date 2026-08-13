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
      return 'bg-brand-green-50'
    case 'dark':
      return 'bg-canvas-cream'
    default:
      return 'bg-white'
  }
}

const eyebrowColor = (background?: string) =>
  background === 'dark'
    ? 'bg-brand-green-100 text-brand-green-700'
    : 'bg-brand-orange-50 text-brand-orange-600 border border-brand-orange-200'

const titleColor = (_background?: string) => 'text-slate-900'

const subtitleColor = (_background?: string) => 'text-slate-500'

const SectionShell = ({ section, children, id }: SectionShellProps) => {
  const title = section.title?.trim()
  const subtitle = section.subtitle?.trim()
  const eyebrow = section.eyebrow?.trim()
  const showHeader = Boolean(title || subtitle || eyebrow)

  return (
    <section className={`py-6 lg:py-8 ${bgClass(section.background)}`} id={id}>
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {showHeader && (
          <div className="mb-4 flex items-end justify-between gap-4 lg:mb-6">
            <div className="min-w-0">
              {eyebrow && (
                <span className={`mb-2.5 inline-flex items-center rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] ${eyebrowColor(section.background)}`}>
                  {eyebrow}
                </span>
              )}
              {title && (
                <h2 className={`font-headline text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-[32px] lg:leading-tight ${titleColor(section.background)}`}>
                  {title}
                </h2>
              )}
              {subtitle && (
                <p className={`mt-1.5 max-w-2xl text-sm leading-6 sm:text-[15px] ${subtitleColor(section.background)}`}>
                  {subtitle}
                </p>
              )}
            </div>
            {section.ctaText?.trim() && section.ctaUrl?.trim() && (
              <Link
                className="group hidden shrink-0 items-center gap-1.5 rounded-full border border-brand-orange-300 bg-brand-orange-500 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-brand-orange-600 sm:inline-flex"
                to={section.ctaUrl}
              >
                {section.ctaText}
                <ArrowRight size={13} className="transition-transform group-hover:translate-x-0.5" />
              </Link>
            )}
          </div>
        )}
        {children}
        {section.ctaText?.trim() && section.ctaUrl?.trim() && (
          <div className="mt-6 text-center sm:hidden">
            <Link
              className="inline-flex items-center gap-1.5 rounded-full border border-brand-orange-300 bg-brand-orange-500 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-brand-orange-600"
              to={section.ctaUrl}
            >
              {section.ctaText}
              <ArrowRight size={13} />
            </Link>
          </div>
        )}
      </div>
    </section>
  )
}

export default SectionShell
