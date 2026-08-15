import { Link } from 'react-router-dom'
import { ChevronRight, Home, MessageCircle, Phone } from 'lucide-react'
import { SEO } from '../common/SEO'
import { cn } from '@/lib/utils'

export interface StaticTocItem {
  id: string
  label: string
}

interface StaticInfoLayoutProps {
  kicker: string
  title: string
  seoTitle: string
  seoDescription: string
  url: string
  children: React.ReactNode
  toc?: StaticTocItem[]
  relatedLinks?: { title: string; to: string }[]
  showSupport?: boolean
}

const StaticInfoLayout = ({
  kicker,
  title,
  seoTitle,
  seoDescription,
  url,
  children,
  toc = [],
  relatedLinks = [],
  showSupport = true,
}: StaticInfoLayoutProps) => {
  return (
    <div className="min-h-[60vh] bg-[#f5f7f5] pb-16">
      <div className="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
        <SEO title={seoTitle} description={seoDescription} url={url} />

        <nav className="flex items-center gap-1.5 text-xs text-slate-500">
          <Link to="/" className="flex items-center gap-1 transition hover:text-brand-green-700">
            <Home className="h-3.5 w-3.5" /> Home
          </Link>
          <ChevronRight className="h-3.5 w-3.5" />
          <span className="font-medium text-brand-green-700">{title}</span>
        </nav>

        <div className={cn('mt-6 grid gap-8', toc.length > 0 && 'lg:grid-cols-[260px_minmax(0,1fr)]')}>
          {toc.length > 0 && (
            <aside className="order-2 lg:order-1">
              <div className="hidden rounded-md border border-slate-200 bg-white p-4 shadow-sm lg:sticky lg:top-24 lg:block">
                <p className="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">On this page</p>
                <ul className="mt-3 space-y-1">
                  {toc.map((item) => (
                    <li key={item.id}>
                      <a
                        href={`#${item.id}`}
                        className="block rounded-md px-2 py-1.5 text-[13px] leading-5 text-slate-600 transition hover:bg-brand-green-50 hover:text-brand-green-700"
                      >
                        {item.label}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            </aside>
          )}

          <article className="order-1 min-w-0 lg:order-2">
            <div className="rounded-md border border-slate-200 bg-white px-5 py-8 shadow-sm sm:px-10">
              <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-brand-orange-500">{kicker}</p>
              <h1 className="mt-2 text-2xl font-extrabold leading-snug text-brand-green-700 sm:text-3xl">{title}</h1>
              <div className="mt-8 space-y-10">{children}</div>
            </div>

            {toc.length > 0 && (
              <div className="mt-8 rounded-md border border-slate-200 bg-slate-50 p-4 lg:hidden">
                <p className="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">On this page</p>
                <ul className="mt-2 space-y-1">
                  {toc.map((item) => (
                    <li key={item.id}>
                      <a href={`#${item.id}`} className="block text-[13px] text-slate-600 hover:text-brand-green-700">
                        {item.label}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {showSupport && (
              <section className="mt-6 rounded-md border border-brand-green-700/20 bg-brand-green-700 px-5 py-6 text-white shadow-sm sm:px-8">
                <h2 className="flex items-center gap-2 text-lg font-bold">
                  <MessageCircle className="h-5 w-5 text-brand-orange-400" />
                  Need More Help?
                </h2>
                <p className="mt-2 text-sm leading-6 text-brand-green-50/90">
                  Our support team is ready to help you with any questions about orders, delivery, returns, and more.
                </p>
                <div className="mt-4 flex flex-wrap gap-3">
                  <Link
                    to="/contact"
                    className="inline-flex items-center gap-2 rounded-md bg-brand-orange-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-orange-600"
                  >
                    <Phone className="h-4 w-4" /> Contact Us
                  </Link>
                  <Link
                    to="/about"
                    className="inline-flex items-center gap-2 rounded-md border border-brand-green-50/30 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-green-600"
                  >
                    About MamaBazar
                  </Link>
                </div>
              </section>
            )}

            {relatedLinks.length > 0 && (
              <section className="mt-6">
                <p className="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">Related Pages</p>
                <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                  {relatedLinks.map((link) => (
                    <Link
                      key={link.to}
                      to={link.to}
                      className="rounded-md border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-brand-green-700 shadow-sm transition hover:border-brand-orange-300 hover:text-brand-orange-500"
                    >
                      {link.title}
                    </Link>
                  ))}
                </div>
              </section>
            )}
          </article>
        </div>
      </div>
    </div>
  )
}

export default StaticInfoLayout