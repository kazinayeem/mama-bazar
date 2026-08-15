import SectionShell from './SectionShell'
import HeroCarousel from './HeroCarousel'
import TrustStrip from './TrustStrip'
import CategoryGrid from './CategoryGrid'
import ProductCarousel from './ProductCarousel'
import FlashDeals from './FlashDeals'
import BrandRow from './BrandRow'
import CollectionTiles from './CollectionTiles'
import PromoBanner from './PromoBanner'
import ReviewsSection from './ReviewsSection'
import NewsletterBlock from './NewsletterBlock'
import WhyChooseUs from './WhyChooseUs'
import { Link } from 'react-router-dom'
import {
  asBanners,
  asBrands,
  asCategories,
  asCategory,
  asCollections,
  asContentItems,
  asProducts,
  asReviews,
  asSlides,
  type HomepageData,
  type HomepageSectionConfig,
} from '../../types/homepage'
import type { Product } from '../../types'

interface HomepageSectionsProps {
  data?: HomepageData
  loading?: boolean
  hasError?: boolean
  onRetry?: () => void
  onQuickView: (product: Product) => void
}

// Skeleton row for loading state — mimics a product rail
const ProductRowSkeleton = () => (
  <div className="flex gap-3 overflow-hidden py-1">
    {Array.from({ length: 5 }).map((_, i) => (
      <div key={i} className="h-72 w-48 shrink-0 animate-pulse rounded-2xl border border-slate-100 bg-slate-100/80" />
    ))}
  </div>
)

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

const HomepageSections = ({ data, loading, hasError, onRetry, onQuickView }: HomepageSectionsProps) => {
  if (!data) {
    if (hasError) return <HomepageErrorState onRetry={onRetry} />
    if (!loading) return null
    return (
      <div className="space-y-4 bg-white py-3">
        {/* Hero skeleton */}
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="h-[300px] w-full animate-pulse rounded-3xl bg-slate-100 sm:h-[400px] lg:h-[480px]" />
        </div>

        {/* Trust strip skeleton */}
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="flex items-center gap-3 rounded-2xl bg-slate-50 p-4">
                <div className="h-10 w-10 animate-pulse rounded-xl bg-slate-100" />
                <div className="space-y-1.5">
                  <div className="h-2.5 w-24 animate-pulse rounded bg-slate-100" />
                  <div className="h-2 w-28 animate-pulse rounded bg-slate-100" />
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Category carousel skeleton */}
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-4 h-6 w-48 animate-pulse rounded bg-slate-100" />
          <div className="flex gap-3 overflow-hidden">
            {Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="flex w-[138px] shrink-0 flex-col items-center gap-2 rounded-2xl bg-slate-50 p-4">
                <div className="h-16 w-16 animate-pulse rounded-2xl bg-slate-100" />
                <div className="h-2.5 w-20 animate-pulse rounded bg-slate-100" />
              </div>
            ))}
          </div>
        </div>

        {/* Product rail skeleton x2 */}
        {[1, 2].map((k) => (
          <div key={k} className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="mb-4 h-6 w-48 animate-pulse rounded bg-slate-100" />
            <ProductRowSkeleton />
          </div>
        ))}
      </div>
    )
  }

  const heroSlides = asSlides(data.sections.find((s) => s.type === 'hero')) || data.heroSlides
  const popularSearches = data.popularSearches?.slice(0, 8) || []

  return (
    <>
      {data.sections.map((section) => {
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
            return <TrustStrip key={section.id} items={asContentItems(section)} />

          case 'categories': {
            const items = asCategories(section)
            if (items.length === 0) return null
            return (
              <SectionShell key={section.id} section={section}>
                <CategoryGrid columns={section.columns} items={items} />
              </SectionShell>
            )
          }

          case 'category_products': {
            const products = asProducts(section)
            const category = asCategory(section)
            if (products.length === 0 || !category) return null
            const resolved: HomepageSectionConfig = {
              ...section,
              title: section.title?.trim() || category.name,
              ctaText: section.ctaText?.trim() || 'View More',
              ctaUrl: section.ctaUrl?.trim() || `/shop?category=${category.slug}`,
            }
            return (
              <SectionShell key={section.id} section={resolved}>
                <ProductCarousel onQuickView={onQuickView} products={products} />
              </SectionShell>
            )
          }

          case 'promo_banner': {
            const items = asBanners(section)
            if (items.length === 0) return null
            return (
              <SectionShell key={section.id} section={section}>
                <PromoBanner items={items} />
              </SectionShell>
            )
          }

          case 'flash_deals': {
            const products = asProducts(section)
            if (products.length === 0) return null
            return (
              <FlashDeals
                key={section.id}
                onQuickView={onQuickView}
                products={products}
                section={section}
                window={data.flashSaleWindow}
              />
            )
          }

          case 'brands': {
            const items = asBrands(section)
            if (items.length === 0) return null
            return (
              <SectionShell key={section.id} section={section}>
                <BrandRow items={items} />
              </SectionShell>
            )
          }

          case 'collections': {
            const items = asCollections(section)
            if (items.length === 0) return null
            return (
              <SectionShell key={section.id} section={section}>
                <CollectionTiles items={items} />
              </SectionShell>
            )
          }

          case 'reviews': {
            const items = asReviews(section)
            if (items.length === 0) return null
            return (
              <SectionShell key={section.id} section={section}>
                <ReviewsSection items={items} />
              </SectionShell>
            )
          }

          case 'why_choose_us': {
            const items = asContentItems(section)
            if (items.length === 0) return null
            return (
              <SectionShell key={section.id} section={section}>
                <WhyChooseUs items={items} />
              </SectionShell>
            )
          }

          case 'newsletter':
            return <NewsletterBlock key={section.id} settings={section.data?.settings} />

          case 'featured':
          case 'best_sellers':
          case 'trending':
          case 'new_arrivals':
          case 'limited_edition':
          case 'official':
          case 'hot_deals':
          case 'emi_available':
          case 'recommendations': {
            const products = asProducts(section)
            if (products.length === 0) return null
            return (
              <SectionShell key={section.id} section={section}>
                <ProductCarousel onQuickView={onQuickView} products={products} />
              </SectionShell>
            )
          }

          default:
            return null
        }
      })}
    </>
  )
}

export default HomepageSections
