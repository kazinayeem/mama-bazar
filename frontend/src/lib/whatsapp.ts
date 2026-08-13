export const WHATSAPP_URL = 'https://wa.me/8801790612788'

let currentProductName: string | null = null

export function setWhatsAppProduct(name: string | null | undefined) {
  currentProductName = name ?? null
}

export function buildWhatsAppUrl(): string {
  const pageUrl = typeof window !== 'undefined' ? window.location.href : ''
  const message = currentProductName
    ? `Hi, I'm interested in this product: ${currentProductName}\nPage: ${pageUrl}`
    : `Hi, I'd like to know more about your products.\nPage: ${pageUrl}`
  return `${WHATSAPP_URL}?text=${encodeURIComponent(message)}`
}