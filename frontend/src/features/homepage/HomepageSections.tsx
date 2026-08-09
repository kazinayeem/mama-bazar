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
import {
  asBanners,
  asBrands,
  asCategories,
  asCollections,
  asContentItems,
  asProducts,
  asReviews,
  asSlides,
  type HomepageData,
} from '../../types/homepage'
import type { Product } from '../../types'

interface HomepageSectionsProps {
  data?: HomepageData
  loading?: boolean
  onQuickView: (product: Product) => void
}

// Skeleton row for loading state — mimics a product rail
const ProductRowSkeleton = () => (
  <div className="flex gap-3 overflow-hidden py-1">
    {Array.from({ length: 5 }).map((_, i) => (
      <div key={i} className="h-56 w-44 shrink-0 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
    ))}
  </div>
)

const HomepageSections = ({ data, loading, onQuickView }: HomepageSectionsProps) => {
  if (!data) {
    if (!loading) return null
    return (
      <div className="space-y-6 bg-white py-4 dark:bg-slate-950">
        {/* Hero skeleton */}
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="h-[260px] w-full animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800 sm:h-[320px] lg:h-[400px]" />
        </div>

        {/* Trust strip skeleton */}
        <div className="mx-auto max-w-7xl px-4">
          <div className="flex gap-6 py-3">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="flex shrink-0 items-center gap-3">
                <div className="h-9 w-9 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
                <div className="space-y-1">
                  <div className="h-2.5 w-20 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
                  <div className="h-2 w-28 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Category carousel skeleton */}
        <div className="mx-auto max-w-7xl px-4">
          <div className="mb-3 h-5 w-40 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
          <div className="flex gap-3 overflow-hidden">
            {Array.from({ length: 8 }).map((_, i) => (
              <div key={i} className="flex shrink-0 flex-col items-center gap-2">
                <div className="h-14 w-14 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800" />
                <div className="h-2.5 w-14 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
              </div>
            ))}
          </div>
        </div>

        {/* Product rail skeleton x2 */}
        {[1, 2].map((k) => (
          <div key={k} className="mx-auto max-w-7xl px-4">
            <div className="mb-3 h-5 w-40 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
            <ProductRowSkeleton />
          </div>
        ))}
      </div>
    )
  }

  const heroSlides = asSlides(data.sections.find((s) => s.type === 'hero')) || data.heroSlides

  return (
    <>
      {data.sections.map((section) => {
        switch (section.type) {
          case 'hero':
            return <HeroCarousel key={section.id} slides={heroSlides} />

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

          case 'newsletter':
            return <NewsletterBlock key={section.id} settings={section.data?.settings} />

          case 'featured':
          case 'best_sellers':
          case 'trending':
          case 'new_arrivals':
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
