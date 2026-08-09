import { ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { Banner } from '../../types/admin'

interface PromoBannerProps {
  items: Banner[]
}

const isExternal = (url: string) => /^https?:\/\//.test(url)

const PromoBanner = ({ items }: PromoBannerProps) => {
  const banner = items[0]
  if (!banner) return null
  const target = banner.link || '/shop'

  const body = (
    <>
      <picture>
        {banner.imageMobile && <source media="(max-width: 639px)" srcSet={banner.imageMobile} />}
        {banner.imageTablet && <source media="(max-width: 1023px)" srcSet={banner.imageTablet} />}
        <img
          alt={banner.title || 'Promotional banner'}
          className="absolute inset-0 h-full w-full object-cover"
          loading="lazy"
          src={banner.image}
        />
      </picture>
      <div className="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/40 to-transparent" />
      <div className="relative z-10 max-w-sm px-6 py-8 sm:px-8 sm:py-10">
        {banner.title && (
          <h2 className="font-headline text-xl font-black tracking-tight text-white sm:text-2xl">
            {banner.title}
          </h2>
        )}
        {banner.subtitle && (
          <p className="mt-2 text-xs leading-6 text-slate-200 sm:text-sm">{banner.subtitle}</p>
        )}
        {banner.buttonText && (
          <span className="group mt-5 inline-flex items-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-bold text-white transition hover:bg-accent-600 active:scale-95">
            {banner.buttonText}
            <ArrowRight size={14} className="transition-transform group-hover:translate-x-0.5" />
          </span>
        )}
      </div>
    </>
  )

  return (
    <div className="relative h-36 overflow-hidden rounded-xl sm:h-44 lg:h-52">
      {isExternal(target) ? (
        <a className="block h-full" href={target} rel="noopener noreferrer" target="_blank">
          {body}
        </a>
      ) : (
        <Link className="block h-full" to={target}>
          {body}
        </Link>
      )}
    </div>
  )
}

export default PromoBanner
