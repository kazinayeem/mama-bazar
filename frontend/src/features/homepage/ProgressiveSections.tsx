import { lazy, Suspense, useMemo } from 'react'
import SectionShell from './SectionShell'
import ProductCarousel from './ProductCarousel'
import FlashDeals from './FlashDeals'
import BrandRow from './BrandRow'
import CollectionTiles from './CollectionTiles'
import PromoBanner from './PromoBanner'
import ReviewsSection from './ReviewsSection'
import CategoryGrid from './CategoryGrid'
import {
  useGetProductsQuery,
  useGetCategoriesQuery,
  useGetBrandsQuery,
  useGetCollectionsQuery,
  useGetBannersQuery,
  useGetReviewsQuery,
} from '../../store/services/commerceApi'
import type {
  HomepageSectionConfig,
  HomepageFlashSaleWindow,
  HomepageContentItem,
  HomepageNewsletterSettings,
} from '../../types/homepage'
import type { Product } from '../../types'

// Lazy-load informational below-the-fold components
const WhyChooseUs = lazy(() => import('./WhyChooseUs'))
const NewsletterBlock = lazy(() => import('./NewsletterBlock'))

// ──────────────────────────────────────────────────────────────
// Reusable Compact Skeletons matching exact section dimensions (0 CLS)
// ──────────────────────────────────────────────────────────────

export const ProductRailSkeleton = () => (
  <div className="flex gap-3 overflow-hidden py-1">
    {Array.from({ length: 5 }).map((_, i) => (
      <div
        key={i}
        className="h-[310px] w-[210px] shrink-0 animate-pulse rounded-2xl border border-brand-green-100/60 bg-slate-50 p-3"
      >
        <div className="h-[180px] w-full rounded-xl bg-slate-100" />
        <div className="mt-3.5 space-y-2">
          <div className="h-3 w-3/4 rounded bg-slate-100" />
          <div className="h-3 w-1/2 rounded bg-slate-100" />
        </div>
      </div>
    ))}
  </div>
)

export const CategoryGridSkeleton = () => (
  <div className="flex gap-3 overflow-hidden pb-3 pt-1.5">
    {Array.from({ length: 6 }).map((_, i) => (
      <div
        key={i}
        className="flex w-[138px] shrink-0 flex-col items-center gap-2.5 rounded-2xl border border-brand-green-100/60 bg-slate-50 px-3 py-4 sm:w-[150px]"
      >
        <div className="h-16 w-16 animate-pulse rounded-2xl bg-slate-100 sm:h-[68px] sm:w-[68px]" />
        <div className="h-3 w-16 animate-pulse rounded bg-slate-100" />
      </div>
    ))}
  </div>
)

export const BrandRowSkeleton = () => (
  <div className="flex gap-3 overflow-hidden py-1">
    {Array.from({ length: 7 }).map((_, i) => (
      <div
        key={i}
        className="flex w-[130px] shrink-0 flex-col items-center justify-center gap-2.5 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-5"
      >
        <div className="h-12 w-12 animate-pulse rounded-xl bg-slate-100" />
        <div className="h-2.5 w-14 animate-pulse rounded bg-slate-100" />
      </div>
    ))}
  </div>
)

export const CollectionTilesSkeleton = () => (
  <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
    {Array.from({ length: 6 }).map((_, i) => (
      <div key={i} className="h-44 animate-pulse rounded-2xl border border-slate-100 bg-slate-100 sm:h-52" />
    ))}
  </div>
)

export const PromoBannerSkeleton = () => (
  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
    {Array.from({ length: 2 }).map((_, i) => (
      <div key={i} className="h-44 animate-pulse rounded-2xl border border-slate-100 bg-slate-100 sm:h-52 lg:h-60" />
    ))}
  </div>
)

export const ReviewsSkeleton = () => (
  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {Array.from({ length: 4 }).map((_, i) => (
      <div key={i} className="h-48 animate-pulse rounded-2xl border border-slate-100 bg-slate-50 p-5" />
    ))}
  </div>
)

// ──────────────────────────────────────────────────────────────
// Progressive Section Containers
// ──────────────────────────────────────────────────────────────

export const ProgressiveCategories = ({
  section,
  inView = true,
}: {
  section: HomepageSectionConfig
  inView?: boolean
}) => {
  const { data: categories = [], isLoading, isError } = useGetCategoriesQuery(undefined, { skip: !inView })

  if (!inView || isLoading) {
    return (
      <SectionShell section={section}>
        <CategoryGridSkeleton />
      </SectionShell>
    )
  }

  if (isError || categories.length === 0) return null

  return (
    <SectionShell section={section}>
      <CategoryGrid columns={section.columns} items={categories} />
    </SectionShell>
  )
}

export const ProgressiveProductRail = ({
  section,
  label,
  categorySlug,
  emiAvailable,
  onQuickView,
  inView = false,
}: {
  section: HomepageSectionConfig
  label?: string
  categorySlug?: string
  emiAvailable?: boolean
  onQuickView: (product: Product) => void
  inView?: boolean
}) => {
  const queryParams = useMemo(
    () => ({
      label: emiAvailable ? undefined : label || undefined,
      category: categorySlug || undefined,
      emiAvailable: emiAvailable || undefined,
      limit: section.limit || 12,
    }),
    [label, categorySlug, emiAvailable, section.limit],
  )

  const { data, isLoading, isError } = useGetProductsQuery(queryParams, { skip: !inView })

  if (!inView || isLoading) {
    return (
      <SectionShell section={section}>
        <ProductRailSkeleton />
      </SectionShell>
    )
  }

  if (isError) return null

  const products = data?.data || []
  if (products.length === 0) return null

  return (
    <SectionShell section={section}>
      <ProductCarousel maxItems={section.limit || 12} onQuickView={onQuickView} products={products} />
    </SectionShell>
  )
}

export const ProgressiveFlashDeals = ({
  section,
  window,
  onQuickView,
  inView = false,
}: {
  section: HomepageSectionConfig
  window?: HomepageFlashSaleWindow
  onQuickView: (product: Product) => void
  inView?: boolean
}) => {
  const { data, isLoading, isError } = useGetProductsQuery({ label: 'flash_sale', limit: section.limit || 12 }, { skip: !inView })

  if (!inView || isLoading) {
    return (
      <div className="bg-brand-green-50 py-6 lg:py-8">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="mb-4 h-24 w-full animate-pulse rounded-2xl bg-brand-green-100/60" />
          <ProductRailSkeleton />
        </div>
      </div>
    )
  }

  if (isError) return null

  const products = data?.data || []
  if (products.length === 0) return null

  return (
    <FlashDeals
      onQuickView={onQuickView}
      products={products}
      section={section}
      window={window}
    />
  )
}

export const ProgressivePromoBanner = ({
  bannerOffset = 0,
  inView = false,
}: {
  bannerOffset?: number
  inView?: boolean
}) => {
  const { data: banners = [], isLoading, isError } = useGetBannersQuery(undefined, { skip: !inView })

  // If banners are already loaded and there are none for this offset, collapse immediately
  if (banners.length > 0 && bannerOffset >= banners.length) return null

  if (!inView || isLoading) {
    return (
      <div className="py-4 lg:py-6">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <PromoBannerSkeleton />
        </div>
      </div>
    )
  }

  if (isError) return null

  const visibleBanners = banners.slice(bannerOffset, bannerOffset + 2)
  if (visibleBanners.length === 0) return null

  return (
    <div className="py-4 lg:py-6">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <PromoBanner items={visibleBanners} />
      </div>
    </div>
  )
}

export const ProgressiveBrands = ({
  section,
  inView = false,
}: {
  section: HomepageSectionConfig
  inView?: boolean
}) => {
  const { data: brands = [], isLoading, isError } = useGetBrandsQuery(undefined, { skip: !inView })

  if (!inView || isLoading) {
    return (
      <SectionShell section={section}>
        <BrandRowSkeleton />
      </SectionShell>
    )
  }

  if (isError || brands.length === 0) return null

  return (
    <SectionShell section={section}>
      <BrandRow items={brands} />
    </SectionShell>
  )
}

export const ProgressiveCollections = ({
  section,
  inView = false,
}: {
  section: HomepageSectionConfig
  inView?: boolean
}) => {
  const { data: collections = [], isLoading, isError } = useGetCollectionsQuery(undefined, { skip: !inView })

  if (!inView || isLoading) {
    return (
      <SectionShell section={section}>
        <CollectionTilesSkeleton />
      </SectionShell>
    )
  }

  if (isError || collections.length === 0) return null

  return (
    <SectionShell section={section}>
      <CollectionTiles items={collections} />
    </SectionShell>
  )
}

export const ProgressiveReviews = ({
  section,
  inView = false,
}: {
  section: HomepageSectionConfig
  inView?: boolean
}) => {
  const { data: reviews = [], isLoading, isError } = useGetReviewsQuery({ limit: section.limit || 8 }, { skip: !inView })

  if (!inView || isLoading) {
    return (
      <SectionShell section={section}>
        <ReviewsSkeleton />
      </SectionShell>
    )
  }

  if (isError || reviews.length === 0) return null

  return (
    <SectionShell section={section}>
      <ReviewsSection items={reviews} />
    </SectionShell>
  )
}

export const ProgressiveWhyChooseUs = ({
  section,
  items,
  inView = false,
}: {
  section: HomepageSectionConfig
  items?: HomepageContentItem[]
  inView?: boolean
}) => {
  if (!items || items.length === 0) return null

  if (!inView) {
    return (
      <SectionShell section={section}>
        <div className="h-44 w-full animate-pulse rounded-2xl bg-slate-100" />
      </SectionShell>
    )
  }

  return (
    <SectionShell section={section}>
      <Suspense fallback={<div className="h-44 w-full animate-pulse rounded-2xl bg-slate-100" />}>
        <WhyChooseUs items={items} />
      </Suspense>
    </SectionShell>
  )
}

export const ProgressiveNewsletter = ({
  settings,
  inView = false,
}: {
  settings?: HomepageNewsletterSettings
  inView?: boolean
}) => {
  if (!settings?.enabled) return null

  if (!inView) {
    return <div className="mx-auto my-6 h-40 max-w-7xl animate-pulse rounded-3xl bg-slate-100" />
  }

  return (
    <Suspense fallback={<div className="mx-auto my-6 h-40 max-w-7xl animate-pulse rounded-3xl bg-slate-100" />}>
      <NewsletterBlock settings={settings} />
    </Suspense>
  )
}
