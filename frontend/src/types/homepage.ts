import type { Product, Category, ProductReview } from './index'
import type { Brand, Collection, Banner } from './admin'

export type HomepageSectionType =
  | 'hero'
  | 'trust_strip'
  | 'categories'
  | 'category_products'
  | 'promo_banner'
  | 'flash_deals'
  | 'featured'
  | 'best_sellers'
  | 'brands'
  | 'collections'
  | 'trending'
  | 'new_arrivals'
  | 'limited_edition'
  | 'official'
  | 'hot_deals'
  | 'emi_available'
  | 'recommendations'
  | 'why_choose_us'
  | 'reviews'
  | 'newsletter'

export interface HomepageHeroSlide {
  id: string
  desktopImage: string
  tabletImage?: string
  mobileImage?: string
  badge?: string
  title?: string
  subtitle?: string
  description?: string
  primaryButtonText?: string
  primaryButtonUrl?: string
  secondaryButtonText?: string
  secondaryButtonUrl?: string
  backgroundColor?: string
  textColor?: string
  overlay?: boolean
  overlayOpacity?: number
  alignment?: 'left' | 'center' | 'right'
  status: 'active' | 'inactive'
  priority: number
}

export interface HomepageSectionConfig {
  id: string
  type: HomepageSectionType
  enabled: boolean
  title?: string
  subtitle?: string
  eyebrow?: string
  ctaText?: string
  ctaUrl?: string
  limit?: number
  columns?: number
  background?: 'default' | 'muted' | 'dark'
  categoryId?: number | null
  categorySlug?: string | null
}

export interface HomepageContentItem {
  icon?: string
  title: string
  text?: string
}

export interface HomepageAnnouncement {
  enabled: boolean
  text: string
  backgroundColor?: string
  textColor?: string
}

export interface HomepageFlashSaleWindow {
  enabled: boolean
  start?: string | null
  end?: string | null
  /** Computed server-side: whether the sale window is currently live. */
  isActive?: boolean
  /** Server-computed end time (ISO) when a timed window is live. */
  endsAt?: string | null
}

export interface HomepageNewsletterSettings {
  enabled: boolean
  title?: string
  subtitle?: string
  buttonText?: string
}

export interface HomepageSectionData {
  slides?: HomepageHeroSlide[]
  items?: unknown[]
  settings?: HomepageNewsletterSettings
  category?: { id: number; name: string; slug: string } | null
}

export interface HomepageSection extends HomepageSectionConfig {
  data?: HomepageSectionData
}

export interface HomepageData {
  announcement: HomepageAnnouncement
  heroSlides: HomepageHeroSlide[]
  flashSaleWindow: HomepageFlashSaleWindow
  popularSearches: string[]
  sections: HomepageSection[]
}

export interface HomepageConfig {
  announcement: HomepageAnnouncement
  heroSlides: HomepageHeroSlide[]
  sections: HomepageSectionConfig[]
  trustStrip: HomepageContentItem[]
  whyChooseUs: HomepageContentItem[]
  newsletter: HomepageNewsletterSettings
  flashSaleWindow: HomepageFlashSaleWindow
  popularSearches: string[]
}

export interface NewsletterSubscriber {
  id: number
  email: string
  source?: string | null
  status: 'subscribed' | 'unsubscribed'
  subscribedAt: string
}

export const asProducts = (section?: HomepageSection): Product[] => (section?.data?.items as Product[]) || []
export const asCategories = (section?: HomepageSection): Category[] => (section?.data?.items as Category[]) || []
export const asCategory = (section?: HomepageSection): { id: number; name: string; slug: string } | null => section?.data?.category || null
export const asBrands = (section?: HomepageSection): Brand[] => (section?.data?.items as Brand[]) || []
export const asCollections = (section?: HomepageSection): Collection[] => (section?.data?.items as Collection[]) || []
export const asBanners = (section?: HomepageSection): Banner[] => (section?.data?.items as Banner[]) || []
export const asReviews = (section?: HomepageSection): ProductReview[] => (section?.data?.items as ProductReview[]) || []
export const asContentItems = (section?: HomepageSection): HomepageContentItem[] => (section?.data?.items as HomepageContentItem[]) || []
export const asSlides = (section?: HomepageSection): HomepageHeroSlide[] => (section?.data?.slides as HomepageHeroSlide[]) || []
