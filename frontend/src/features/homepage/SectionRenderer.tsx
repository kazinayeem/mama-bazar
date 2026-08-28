import { memo } from 'react'
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
  ProgressiveWhyChooseUs,
  ProgressiveNewsletter,
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
  type HomepageConfig,
  type HomepageSection,
} from '../../types/homepage'
import type { Product } from '../../types'

interface SectionRendererProps {
  section: HomepageSection
  config?: HomepageConfig
  promoIndex?: number
  onQuickView: (product: Product) => void
}

/**
 * SectionRenderer
 * Single source of truth for rendering any individual homepage section according
 * to its configured type, options, and responsive behavior.
 * Guarantees zero height/blank space if the section data is empty.
 */
export const SectionRenderer = memo(function SectionRenderer({
  section,
  config,
  promoIndex = 0,
  onQuickView,
}: SectionRendererProps) {
  if (section.enabled === false) return null

  switch (section.type) {
    case 'hero': {
      const heroSlides =
        asSlides(section).length > 0
          ? asSlides(section)
          : config?.heroSlides && config.heroSlides.length > 0
            ? config.heroSlides
            : []

      const popularSearches =
        config?.popularSearches?.slice(0, 8) || ['Smart Watch', 'Headphone', 'Speaker', 'Keyboard', 'Air Fryer']

      return (
        <div className="space-y-3">
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
    }

    case 'trust_strip': {
      const items = asContentItems(section).length > 0 ? asContentItems(section) : (config?.trustStrip as any)
      if (!items || items.length === 0) return null
      return <TrustStrip items={items} />
    }

    case 'categories': {
      return (
        <LazySection
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
    }

    case 'category_products': {
      const category = asCategory(section)
      return (
        <LazySection
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
      const emiAvailable = section.type === 'emi_available' ? true : undefined

      return (
        <LazySection
          rootMargin="450px"
          fallback={
            <SectionShell section={section}>
              <ProductRailSkeleton />
            </SectionShell>
          }
        >
          {({ inView }) => (
            <ProgressiveProductRail
              emiAvailable={emiAvailable}
              inView={inView}
              label={label}
              onQuickView={onQuickView}
              section={section}
            />
          )}
        </LazySection>
      )
    }

    case 'promo_banner': {
      return (
        <LazySection rootMargin="450px">
          {({ inView }) => (
            <ProgressivePromoBanner
              bannerOffset={promoIndex * 2}
              inView={inView}
            />
          )}
        </LazySection>
      )
    }

    case 'flash_deals': {
      return (
        <LazySection
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
              window={config?.flashSaleWindow}
            />
          )}
        </LazySection>
      )
    }

    case 'brands': {
      return (
        <LazySection
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
    }

    case 'collections': {
      return (
        <LazySection
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
    }

    case 'reviews': {
      return (
        <LazySection
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
    }

    case 'why_choose_us': {
      return (
        <LazySection
          rootMargin="450px"
          fallback={
            <SectionShell section={section}>
              <div className="h-44 w-full animate-pulse rounded-2xl bg-slate-100" />
            </SectionShell>
          }
        >
          {({ inView }) => (
            <ProgressiveWhyChooseUs
              inView={inView}
              items={asContentItems(section).length > 0 ? asContentItems(section) : (config?.whyChooseUs as any)}
              section={section}
            />
          )}
        </LazySection>
      )
    }

    case 'newsletter': {
      return (
        <LazySection
          rootMargin="450px"
          fallback={<div className="mx-auto my-6 h-40 max-w-7xl animate-pulse rounded-3xl bg-slate-100" />}
        >
          {({ inView }) => (
            <ProgressiveNewsletter
              inView={inView}
              settings={section.data?.settings || config?.newsletter}
            />
          )}
        </LazySection>
      )
    }

    default:
      return null
  }
})

export default SectionRenderer
