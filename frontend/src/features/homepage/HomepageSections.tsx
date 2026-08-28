import { lazy, Suspense } from 'react'
import SectionShell from './SectionShell'
import HeroCarousel from './HeroCarousel'
import TrustStrip from './TrustStrip'
import LazySection from '../../components/common/LazySection'
import {
  ProgressiveCategories,
  ProgressiveProductRail,
  ProgressiveFlashDeals,
  ProgressivePromoBanner,
  ProgressiveBrands,
  ProgressiveCollections,
  ProgressiveReviews,
  ProductRailSkeleton,
  CategoryGridSkeleton,
  BrandRowSkeleton,
  CollectionTilesSkeleton,
  ReviewsSkeleton,
} from './ProgressiveSections'
import { Link } from 'react-router-dom'
import {
  asCategory,
  asContentItems,
  asSlides,
  type HomepageData,
  type HomepageSection,
  type HomepageConfig,
} from '../../types/homepage'
import type { Product } from '../../types'
import { useGetBannersQuery } from '../../store/services/commerceApi'

// Code-split heavy below-the-fold informational components
const WhyChooseUs = lazy(() => import('./WhyChooseUs'))
const NewsletterBlock = lazy(() => import('./NewsletterBlock'))

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
  {
    id: 'trust_strip',
    type: 'trust_strip',
    enabled: true,
    title: 'Why shop with us',
  },
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
  {
    id: 'promo_banner',
    type: 'promo_banner',
    enabled: true,
  },
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
  {
    id: 'promo_banner_2',
    type: 'promo_banner',
    enabled: true,
  },
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
  {
    id: 'why_choose_us',
    type: 'why_choose_us',
    enabled: true,
  },
  {
    id: 'newsletter',
    type: 'newsletter',
    enabled: true,
  },
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
  // Query banners for promotional slots
  const { data: banners = [] } = useGetBannersQuery()

  if (hasError) return <HomepageErrorState onRetry={onRetry} />

  const sections: HomepageSection[] =
    (data?.sections && data.sections.length > 0)
      ? data.sections
      : (config?.sections && config.sections.length > 0)
        ? (config.sections as HomepageSection[])
        : DEFAULT_HOMEPAGE_SECTIONS

  // Convert banners to hero slides format if not provided by config or data
  const fallbackHeroSlides = banners.map((b, idx) => ({
    id: String(b.id || idx),
    desktopImage: b.image || '',
    tabletImage: b.imageTablet || b.image,
    mobileImage: b.imageMobile || b.image,
    title: b.title || '',
    subtitle: b.subtitle || '',
    primaryButtonText: b.buttonText || 'Shop Now',
    primaryButtonUrl: b.link || '/shop',
    status: 'active' as const,
    priority: idx,
  }))

  const heroSlides =
    (data && asSlides(data.sections.find((s) => s.type === 'hero'))) ||
    data?.heroSlides ||
    (config?.heroSlides && config.heroSlides.length > 0
      ? (config.heroSlides as any)
      : fallbackHeroSlides)

  const popularSearches =
    data?.popularSearches?.slice(0, 8) ||
    config?.popularSearches?.slice(0, 8) ||
    ['Smart Watch', 'Headphone', 'Speaker', 'Keyboard', 'Air Fryer']
  const flashSaleWindow = data?.flashSaleWindow || config?.flashSaleWindow

  let promoCounter = 0

  return (
    <>
      {sections.map((section) => {
        if (section.enabled === false) return null

        switch (section.type) {
          case 'hero':
            return (
              <div key={section.id} className="space-y-3">
                <HeroCarousel slides={heroSlides} />
                {popularSearches.length > 0 && (
                  <section className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col items-start gap-3 rounded-3xl border border-slate-100 bg-white/90 px-4 py-4 shadow-soft backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:px-5">
                      <div className="min-w-0">
                        <p className="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-900">Popular searches</p>
                        <p className="mt-1 text-sm text-slate-500">Jump into the products shoppers are looking for right now.</p>
                      </div>
                      <div className="flex flex-wrap gap-2">
                        {popularSearches.map((term) => (
                          <Link
                            className="rounded-full border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:border-primary hover:bg-primary hover:text-primary-foreground"
                            to={`/shop?search=${encodeURIComponent(term)}`}
                            key={term}
                          >
                            {term}
                          </Link>
                        ))}
                      </div>
                    </div>
                  </section>
                )}
              </div>
            )

          case 'trust_strip':
            return <TrustStrip key={section.id} items={asContentItems(section).length > 0 ? asContentItems(section) : (config?.trustStrip as any)} />

          case 'categories':
            return (
              <LazySection
                key={section.id}
                rootMargin="400px"
                fallback={
                  <SectionShell section={section}>
                    <CategoryGridSkeleton />
                  </SectionShell>
                }
              >
                {({ inView }) => <ProgressiveCategories inView={inView} section={section} />}
              </LazySection>
            )

          case 'new_arrivals':
          case 'featured':
          case 'best_sellers':
          case 'trending':
          case 'limited_edition':
          case 'official':
          case 'hot_deals':
          case 'emi_available':
          case 'recommendations': {
            const labelMap: Record<string, string> = {
              new_arrivals: 'new_arrival',
              featured: 'featured',
              best_sellers: 'best_seller',
              trending: 'trending',
              limited_edition: 'limited_edition',
              official: 'official',
              hot_deals: 'hot_deal',
              emi_available: 'featured',
              recommendations: 'trending',
            }
            const label = labelMap[section.type] || 'featured'

            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={
                  <SectionShell section={section}>
                    <ProductRailSkeleton />
                  </SectionShell>
                }
              >
                {({ inView }) => (
                  <ProgressiveProductRail
                    inView={inView}
                    label={label}
                    onQuickView={onQuickView}
                    section={section}
                  />
                )}
              </LazySection>
            )
          }

          case 'category_products': {
            const category = asCategory(section)
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={
                  <SectionShell section={section}>
                    <ProductRailSkeleton />
                  </SectionShell>
                }
              >
                {({ inView }) => (
                  <ProgressiveProductRail
                    categorySlug={category?.slug || section.categorySlug || undefined}
                    inView={inView}
                    onQuickView={onQuickView}
                    section={section}
                  />
                )}
              </LazySection>
            )
          }

          case 'promo_banner': {
            const currentOffset = promoCounter * 2
            promoCounter += 1
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
              >
                {({ inView }) => (
                  <ProgressivePromoBanner
                    bannerOffset={currentOffset}
                    inView={inView}
                  />
                )}
              </LazySection>
            )
          }

          case 'flash_deals':
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={
                  <div className="bg-brand-green-50 py-6 lg:py-8">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                      <div className="mb-4 h-24 w-full animate-pulse rounded-2xl bg-brand-green-100/60" />
                      <ProductRailSkeleton />
                    </div>
                  </div>
                }
              >
                {({ inView }) => (
                  <ProgressiveFlashDeals
                    inView={inView}
                    onQuickView={onQuickView}
                    section={section}
                    window={flashSaleWindow}
                  />
                )}
              </LazySection>
            )

          case 'brands':
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={
                  <SectionShell section={section}>
                    <BrandRowSkeleton />
                  </SectionShell>
                }
              >
                {({ inView }) => <ProgressiveBrands inView={inView} section={section} />}
              </LazySection>
            )

          case 'collections':
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={
                  <SectionShell section={section}>
                    <CollectionTilesSkeleton />
                  </SectionShell>
                }
              >
                {({ inView }) => <ProgressiveCollections inView={inView} section={section} />}
              </LazySection>
            )

          case 'reviews':
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={
                  <SectionShell section={section}>
                    <ReviewsSkeleton />
                  </SectionShell>
                }
              >
                {({ inView }) => <ProgressiveReviews inView={inView} section={section} />}
              </LazySection>
            )

          case 'why_choose_us':
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={
                  <SectionShell section={section}>
                    <div className="h-44 w-full animate-pulse rounded-2xl bg-slate-100" />
                  </SectionShell>
                }
              >
                {() => (
                  <Suspense fallback={<div className="h-44 w-full animate-pulse rounded-2xl bg-slate-100" />}>
                    <SectionShell section={section}>
                      <WhyChooseUs items={asContentItems(section).length > 0 ? asContentItems(section) : (config?.whyChooseUs as any)} />
                    </SectionShell>
                  </Suspense>
                )}
              </LazySection>
            )

          case 'newsletter':
            return (
              <LazySection
                key={section.id}
                rootMargin="450px"
                fallback={<div className="mx-auto my-6 h-40 max-w-7xl animate-pulse rounded-3xl bg-slate-100" />}
              >
                {() => (
                  <Suspense fallback={<div className="mx-auto my-6 h-40 max-w-7xl animate-pulse rounded-3xl bg-slate-100" />}>
                    <NewsletterBlock settings={section.data?.settings || config?.newsletter} />
                  </Suspense>
                )}
              </LazySection>
            )

          default:
            return null
        }
      })}
    </>
  )
}

export default HomepageSections
