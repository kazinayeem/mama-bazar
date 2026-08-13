import type { TrackingConfig } from '@/types/admin'

type PixelFn = ((...args: unknown[]) => void) & {
  queue?: unknown[][]
  callMethod?: (...args: unknown[]) => unknown
  loaded?: boolean
  version?: string
}

declare global {
  interface Window {
    fbq?: PixelFn
  }
}

const CURRENCY = 'BDT'
const PIXEL_SCRIPT_SRC = 'https://connect.facebook.net/en_US/fbevents.js'

let configuredPixelId: string | null = null
let initializedPixelId: string | null = null
let lastTrackedPath: string | null = null
let pendingInitialPath: string | null = null
let configResolved = false
let bufferedEvents: Array<{ event: string; params?: Record<string, unknown> }> = []
const MAX_BUFFER = 50

export const getPixelCurrency = () => CURRENCY

export const isPixelInitialized = () => Boolean(initializedPixelId && window.fbq)

export function initPixel(config?: TrackingConfig | null) {
  if (config == null) return
  const pixelId = config.facebookPixelId || null
  configResolved = true
  configuredPixelId = pixelId
  if (!pixelId) {
    bufferedEvents = []
    pendingInitialPath = null
    return
  }
  if (initializedPixelId === pixelId) return
  initializedPixelId = pixelId

  if (!window.fbq) {
    const fn = ((...args: unknown[]) => {
      // At call time window.fbq is either this shim or the real SDK.
      if (window.fbq?.callMethod) window.fbq.callMethod(...args)
      else window.fbq?.queue?.push(args)
    }) as PixelFn
    fn.queue = []
    fn.loaded = true
    fn.version = '2.0'
    window.fbq = fn
  }

  if (!window.fbq) return

  if (!document.querySelector(`script[src="${PIXEL_SCRIPT_SRC}"]`)) {
    const script = document.createElement('script')
    script.async = true
    script.src = PIXEL_SCRIPT_SRC
    const first = document.getElementsByTagName('script')[0]
    first?.parentNode?.insertBefore(script, first)
  }

  window.fbq('init', pixelId, false, { autoConfig: false })

  if (pendingInitialPath && pendingInitialPath === lastTrackedPath) {
    pendingInitialPath = null
    firePixel('PageView')
  }
  const buffered = bufferedEvents
  bufferedEvents = []
  for (const entry of buffered) firePixel(entry.event, entry.params)
}

function firePixel(event: string, params?: Record<string, unknown>) {
  if (!configuredPixelId || !window.fbq) return
  try {
    window.fbq('track', event, params)
  } catch {
    // Pixel failures must never break the storefront
  }
}

export function trackPixel(event: string, params?: Record<string, unknown>) {
  if (!configResolved) {
    if (bufferedEvents.length < MAX_BUFFER) bufferedEvents.push({ event, params })
    return
  }
  firePixel(event, params)
}

export function trackPageView(path: string) {
  if (lastTrackedPath === path) return
  lastTrackedPath = path
  if (!configResolved) {
    pendingInitialPath = path
    return
  }
  firePixel('PageView')
}

export function trackViewContent(params: { title: string; id: string | number; value: number; category?: string }) {
  trackPixel('ViewContent', {
    content_name: params.title,
    content_category: params.category || undefined,
    content_ids: [String(params.id)],
    content_type: 'product',
    value: params.value,
    currency: CURRENCY,
    contents: [{ id: String(params.id), quantity: 1 }],
  })
}

export function trackSearch(term: string) {
  trackPixel('Search', { search_string: term })
}

export function trackAddToCart(params: { title: string; id: string | number; value: number; quantity: number }) {
  trackPixel('AddToCart', {
    content_name: params.title,
    content_ids: [String(params.id)],
    content_type: 'product',
    value: params.value,
    currency: CURRENCY,
    contents: [{ id: String(params.id), quantity: params.quantity }],
  })
}

export function trackInitiateCheckout(params: { value: number; numItems: number; contents?: Array<{ id: string; quantity: number }> }) {
  trackPixel('InitiateCheckout', {
    content_type: 'product',
    value: params.value,
    currency: CURRENCY,
    num_items: params.numItems,
    contents: params.contents || [],
  })
}

export function trackPurchase(params: { value: number; numItems: number; contentIds: Array<string | number>; contents?: Array<{ id: string; quantity: number }> }) {
  trackPixel('Purchase', {
    value: params.value,
    currency: CURRENCY,
    content_type: 'product',
    num_items: params.numItems,
    content_ids: params.contentIds.map(String),
    contents: params.contents || [],
  })
}