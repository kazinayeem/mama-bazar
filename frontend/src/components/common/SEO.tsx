import { Helmet } from 'react-helmet-async'
import { resolveAbsoluteUrl } from '../../lib/apiConfig'
import type { Product, Category } from '../../types'
import type { Brand } from '../../types/admin'

const SITE_NAME = 'Mama Bazar'
const DEFAULT_DESCRIPTION = 'Discover premium products at unbeatable prices. Official warranty, free delivery, and 24/7 support.'
const DEFAULT_IMAGE = '/brandlogo.png'

function getBaseUrl(): string {
  if (typeof window !== 'undefined') {
    return window.location.origin
  }
  return ''
}

function safeStr(value: unknown, fallback = ''): string {
  if (value == null) return fallback
  const s = String(value).trim()
  return s && s !== 'undefined' && s !== 'null' ? s : fallback
}

export interface SEOProps {
  title?: string
  description?: string
  image?: string
  url?: string
  type?: string
  noIndex?: boolean
}

export function SEO({ title, description, image, url, type = 'website', noIndex }: SEOProps) {
  const baseUrl = getBaseUrl()
  const pageTitle = title ? `${title} | ${SITE_NAME}` : `${SITE_NAME} - Premium Products & Gadgets`
  const pageDesc = safeStr(description, DEFAULT_DESCRIPTION)
  const pageImage = image ? resolveAbsoluteUrl(image) : resolveAbsoluteUrl(DEFAULT_IMAGE)
  const pageUrl = url ? (url.startsWith('http') ? url : `${baseUrl}${url}`) : (typeof window !== 'undefined' ? window.location.href : '')

  return (
    <Helmet>
      <title>{pageTitle}</title>
      <meta name="description" content={pageDesc} />
      {pageUrl && <link rel="canonical" href={pageUrl} />}
      {noIndex && <meta name="robots" content="noindex, nofollow" />}

      {/* Open Graph */}
      <meta property="og:type" content={type} />
      <meta property="og:title" content={pageTitle} />
      <meta property="og:description" content={pageDesc} />
      <meta property="og:image" content={pageImage} />
      {pageUrl && <meta property="og:url" content={pageUrl} />}
      <meta property="og:site_name" content={SITE_NAME} />

      {/* Twitter Card */}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content={pageTitle} />
      <meta name="twitter:description" content={pageDesc} />
      <meta name="twitter:image" content={pageImage} />
    </Helmet>
  )
}

/* ─── SEO helper functions ─── */

export function getProductSEO(product: Product): SEOProps {
  const name = safeStr(product.title, 'Product')
  const brand = safeStr(product.brandInfo?.name)
  const category = safeStr(product.category?.name)

  const title = product.seoTitle || (() => {
    const parts = [name]
    if (brand) parts.push(brand)
    if (category) parts.push(category)
    return parts.join(' - ')
  })()

  const description = product.seoDescription || (() => {
    const shortDesc = safeStr(product.shortDescription) || safeStr(product.description)
    return shortDesc
      ? `${shortDesc} - Buy from ${SITE_NAME}. Check price, availability, and delivery information.`
      : `Buy ${name} from ${SITE_NAME}. Check price, availability, variants, description and delivery information.`
  })()

  const image = product.ogImage || product.twitterImage || product.images?.[0] || undefined

  return {
    title,
    description,
    image,
    url: `/products/${safeStr(product.slug)}`,
    type: 'product',
  }
}

export function getCategorySEO(category: Category): SEOProps {
  const name = safeStr(category.name, 'Category')
  const desc = safeStr(category.description) || `${name} products available at ${SITE_NAME}. Browse our collection of ${name}.`

  return {
    title: `${name} Products`,
    description: desc,
    image: safeStr(category.image) || safeStr(category.banner) || undefined,
    url: `/shop?category=${safeStr(category.slug)}`,
  }
}

export function getBrandSEO(brand: Brand): SEOProps {
  const name = safeStr(brand.name, 'Brand')
  const desc = safeStr(brand.description) || `${name} products available at ${SITE_NAME}. Browse our collection of ${name}.`

  return {
    title: `${name} Products`,
    description: desc,
    image: safeStr(brand.logo) || safeStr(brand.bannerImage) || undefined,
    url: `/shop?brand=${safeStr(brand.slug)}`,
  }
}

export function getPolicyPageSEO(slug: string): SEOProps {
  const policyTitles: Record<string, string> = {
    'return-refund': 'Return & Refund Policy',
    shipping: 'Shipping Policy',
    privacy: 'Privacy Policy',
    terms: 'Terms & Conditions',
    cookie: 'Cookie Policy',
    payment: 'Payment Policy',
    cancellation: 'Cancellation Policy',
    warranty: 'Warranty Policy',
    faq: 'Frequently Asked Questions',
    contact: 'Contact Us',
  }

  const title = policyTitles[slug] || 'Policy'
  const pathMap: Record<string, string> = {
    'return-refund': '/refund-policy',
    shipping: '/shipping-policy',
    privacy: '/privacy-policy',
    terms: '/terms-and-conditions',
    cookie: '/cookie-policy',
    payment: '/payment-policy',
    cancellation: '/cancellation-policy',
    warranty: '/warranty-policy',
    faq: '/faq',
    contact: '/contact',
  }

  return {
    title,
    description: `${title} - Learn more about ${SITE_NAME}'s policies and guidelines.`,
    url: pathMap[slug] || `/${slug}`,
  }
}
