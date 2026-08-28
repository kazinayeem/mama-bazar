import SectionRenderer from './SectionRenderer'
import type { HomepageData, HomepageConfig, HomepageSection } from '../../types/homepage'
import type { Product } from '../../types'

interface HomepageSectionsProps {
  data?: HomepageData
  config?: HomepageConfig
  isLoading?: boolean
  hasError?: boolean
  onRetry?: () => void
  onQuickView: (product: Product) => void
}

const DEFAULT_HOMEPAGE_SECTIONS: HomepageSection[] = [
  { id: 'hero', type: 'hero', enabled: true },
  { id: 'trust_strip', type: 'trust_strip', enabled: true, title: 'Why shop with us' },
  {
    id: 'categories',
    type: 'categories',
    enabled: true,
    title: 'Explore Categories',
    subtitle: 'Discover our wide range of products across all categories.',
    limit: 12,
  },
  {
    id: 'new_arrivals',
    type: 'new_arrivals',
    enabled: true,
    title: 'New arrivals',
    subtitle: 'Fresh products just added to the store.',
    limit: 12,
  },
  { id: 'promo_banner', type: 'promo_banner', enabled: true },
  {
    id: 'featured',
    type: 'featured',
    enabled: true,
    title: 'Featured products',
    subtitle: 'Handpicked favourites from our catalogue.',
    limit: 12,
  },
  {
    id: 'brands',
    type: 'brands',
    enabled: true,
    title: 'Trusted brands',
    subtitle: '100% authentic products from official distributors.',
    limit: 10,
  },
  { id: 'promo_banner_2', type: 'promo_banner', enabled: true },
  {
    id: 'collections',
    type: 'collections',
    enabled: true,
    title: 'Featured collections',
    subtitle: 'Complete setups built for every lifestyle.',
    limit: 6,
  },
  {
    id: 'flash_deals',
    type: 'flash_deals',
    enabled: true,
    title: 'Flash Deals',
    subtitle: "Limited-time prices. When they're gone, they're gone.",
    limit: 12,
    background: 'muted',
  },
  {
    id: 'best_sellers',
    type: 'best_sellers',
    enabled: true,
    title: 'Best sellers',
    subtitle: 'The most-ordered products right now.',
    limit: 12,
  },
  {
    id: 'trending',
    type: 'trending',
    enabled: true,
    title: 'Trending right now',
    subtitle: 'The products everyone is talking about.',
    limit: 10,
    background: 'muted',
  },
  {
    id: 'reviews',
    type: 'reviews',
    enabled: true,
    title: 'Customer Reviews',
    subtitle: 'Real feedback from verified shoppers.',
    limit: 8,
  },
  { id: 'why_choose_us', type: 'why_choose_us', enabled: true },
  { id: 'newsletter', type: 'newsletter', enabled: true },
]

const HomepageErrorState = ({ onRetry }: { onRetry?: () => void }) => (
  <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div className="flex flex-col items-center gap-4 rounded-3xl border border-slate-100 bg-white px-6 py-14 text-center shadow-soft">
      <span className="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-2xl">
        <svg fill="none" height="26" viewBox="0 0 24 24" width="26" stroke="currentColor" strokeWidth="2" className="text-red-500">
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        </svg>
      </span>
      <div>
        <h2 className="font-headline text-lg font-extrabold text-slate-900">Unable to load the homepage</h2>
        <p className="mt-1 text-sm text-slate-500">
          Something went wrong while fetching products and offers. Please try again.
        </p>
      </div>
      {onRetry && (
        <button
          className="mt-2 rounded-full bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground transition hover:bg-primary-700 active:scale-95"
          onClick={onRetry}
          type="button"
        >
          Try Again
        </button>
      )}
    </div>
  </div>
)

const HomepageSections = ({ data, config, hasError, onRetry, onQuickView }: HomepageSectionsProps) => {
  if (hasError) return <HomepageErrorState onRetry={onRetry} />

  const sections: HomepageSection[] =
    (data?.sections && data.sections.length > 0)
      ? data.sections
      : (config?.sections && config.sections.length > 0)
        ? (config.sections as HomepageSection[])
        : DEFAULT_HOMEPAGE_SECTIONS

  let promoCounter = 0

  return (
    <>
      {sections.map((section) => {
        if (section.enabled === false) return null

        const currentPromoIndex = section.type === 'promo_banner' ? promoCounter++ : 0

        return (
          <SectionRenderer
            config={config}
            key={section.id}
            onQuickView={onQuickView}
            promoIndex={currentPromoIndex}
            section={section}
          />
        )
      })}
    </>
  )
}

export default HomepageSections
