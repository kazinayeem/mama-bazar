import { ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import { resolveUrl } from '@/lib/apiConfig'
import { getCloudinaryHeroUrl } from '@/lib/cloudinary'
import type { Banner } from '../../types/admin'

interface PromoBannerProps {
  items: Banner[]
}

const isExternal = (url: string) => /^https?:\/\//.test(url)

const PromoCard = ({ banner }: { banner: Banner }) => {
  const target = banner.link || '/shop'
  // Right-sized per breakpoint with c_limit (downscale-only). Each card renders
  // at most ~600px wide on desktop (2-col grid) / ~400px on mobile; serving
  // 900/700/500px sources avoids the full-resolution banner transfer entirely.
  const image = getCloudinaryHeroUrl(resolveUrl(banner.image), 900)
  const imageTablet = getCloudinaryHeroUrl(resolveUrl(banner.imageTablet), 700) || image
  const imageMobile = getCloudinaryHeroUrl(resolveUrl(banner.imageMobile), 500) || imageTablet

  const body = (
    <>
      <picture>
        <source media="(min-width: 1024px)" srcSet={image} />
        <source media="(min-width: 640px)" srcSet={imageTablet} />
        <img
          alt={banner.title || 'Promotional banner'}
          className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
          decoding="async"
          height="300"
          loading="lazy"
          src={imageMobile}
          width="800"
        />
      </picture>
      <div className="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/45 to-transparent" />
      <div className="relative z-10 flex h-full max-w-sm flex-col justify-center px-6 py-8 sm:px-8 sm:py-10">
        {banner.title && (
          <h2 className="font-headline text-xl font-black tracking-tight text-white sm:text-2xl">
            {banner.title}
          </h2>
        )}
        {banner.subtitle && (
          <p className="mt-2 text-xs leading-6 text-slate-200 sm:text-sm">{banner.subtitle}</p>
        )}
        {banner.buttonText && (
          <span className="mt-5 inline-flex w-fit items-center gap-2 rounded-full bg-accent px-5 py-2.5 text-sm font-bold text-accent-foreground shadow-lg shadow-accent/25 transition hover:bg-accent-600 active:scale-95">
            {banner.buttonText}
            <ArrowRight size={14} className="transition-transform group-hover:translate-x-0.5" />
          </span>
        )}
      </div>
    </>
  )

  return (
    <div className="group relative h-44 overflow-hidden rounded-2xl shadow-soft transition-shadow hover:shadow-card sm:h-52 lg:h-60">
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

const PromoBanner = ({ items = [] }: PromoBannerProps) => {
  const safeItems = Array.isArray(items) ? items : []
  if (safeItems.length === 0) return null
  const visible = safeItems.slice(0, 2)
  return (
    <div className={`grid gap-4 ${visible.length > 1 ? 'sm:grid-cols-2' : 'grid-cols-1'}`}>
      {visible.map((banner) => (
        <PromoCard banner={banner} key={banner.id} />
      ))}
    </div>
  )
}

export default PromoBanner

