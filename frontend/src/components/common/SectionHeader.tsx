import { ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import MotionReveal from './MotionReveal'

interface SectionHeaderProps {
  eyebrow: string
  title: string
  subtitle?: string
  linkText?: string
  linkTo?: string
}

const SectionHeader = ({ eyebrow, title, subtitle, linkText, linkTo }: SectionHeaderProps) => {
  return (
    <MotionReveal>
      <div className="mb-10 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
        <div className="max-w-2xl">
          <span className="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-primary-foreground">
            <span className="h-1.5 w-1.5 rounded-full bg-primary" />
            {eyebrow}
          </span>
          <h2 className="mt-4 font-headline text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{title}</h2>
          {subtitle && <p className="mt-3 text-[15px] leading-7 text-slate-500 dark:text-slate-400">{subtitle}</p>}
        </div>
        {linkText && linkTo && (
          <Link
            className="group inline-flex shrink-0 items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-primary hover:text-primary-foreground dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            to={linkTo}
          >
            {linkText}
            <ArrowRight size={16} className="transition-transform group-hover:translate-x-1" />
          </Link>
        )}
      </div>
    </MotionReveal>
  )
}

export default SectionHeader
