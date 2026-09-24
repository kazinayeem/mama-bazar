import DOMPurify from 'dompurify'

const ALLOWED_TAGS = [
  'p', 'br', 'hr', 'span', 'div',
  'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
  'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup',
  'blockquote', 'ul', 'ol', 'li',
  'a', 'img',
  'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'colgroup', 'col', 'caption',
]

const ALLOWED_ATTR = [
  'href', 'title', 'target', 'rel', 'class', 'style',
  'src', 'alt', 'width', 'height',
  'colspan', 'rowspan', 'scope', 'border', 'cellpadding', 'cellspacing',
]

function isAllowedUri(url: string): boolean {
  const trimmed = (url || '').trim()
  if (!trimmed) return false
  if (/^(javascript|data|vbscript):/i.test(trimmed)) return false
  return true
}

function isLocalImageSrc(src: string): boolean {
  if (!src) return false
  if (src.startsWith('/storage/') || src.startsWith('/uploads/')) return true
  try {
    const u = new URL(src, window.location.origin)
    return (
      u.origin === window.location.origin &&
      (u.pathname.startsWith('/storage/') || u.pathname.startsWith('/uploads/'))
    )
  } catch {
    return false
  }
}

/**
 * Client-side sanitize for product description HTML before dangerouslySetInnerHTML.
 * Server already sanitizes with HTMLPurifier; this is defense-in-depth for React storefront/admin.
 */
export function sanitizeProductHtml(html?: string | null): string {
  if (!html || !html.trim()) return ''

  // Plain text — escape and wrap (do not treat as HTML)
  if (!/<\s*(?:p|div|h[1-6]|ul|ol|li|table|thead|tbody|tfoot|tr|td|th|blockquote|br|hr|strong|em|b|i|u|s|a|img|span)\b/i.test(html.trim())) {
    const escaped = html
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
    return `<p>${escaped.replace(/\n/g, '<br>')}</p>`
  }

  const clean = DOMPurify.sanitize(html, {
    ALLOWED_TAGS,
    ALLOWED_ATTR,
    ALLOW_DATA_ATTR: false,
    FORBID_TAGS: ['script', 'iframe', 'object', 'embed', 'form', 'input', 'svg', 'math'],
    FORBID_ATTR: ['onerror', 'onclick', 'onload', 'onmouseover', 'onfocus', 'onblur'],
    ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto):|\/storage\/|\/uploads\/|#)/i,
  })

  // Ensure links get noopener noreferrer; drop non-local images
  if (typeof window === 'undefined') {
    return clean
  }

  const doc = new DOMParser().parseFromString(clean, 'text/html')
  doc.querySelectorAll('a[href]').forEach((a) => {
    const href = a.getAttribute('href') || ''
    if (!isAllowedUri(href)) {
      a.removeAttribute('href')
      return
    }
    a.setAttribute('rel', 'noopener noreferrer')
    if (a.getAttribute('target') === '_blank') {
      a.setAttribute('rel', 'noopener noreferrer')
    }
  })
  doc.querySelectorAll('img').forEach((img) => {
    const src = img.getAttribute('src') || ''
    if (!isLocalImageSrc(src)) {
      img.remove()
      return
    }
    if (!img.hasAttribute('alt')) {
      img.setAttribute('alt', '')
    }
  })

  return doc.body.innerHTML
}

export function stripHtmlToText(html?: string | null): string {
  if (!html) return ''
  if (typeof window === 'undefined') {
    return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
  }
  const doc = new DOMParser().parseFromString(html, 'text/html')
  return (doc.body.textContent || '').replace(/\s+/g, ' ').trim()
}
