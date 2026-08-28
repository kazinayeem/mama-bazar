/**
 * Centralized API configuration — the SINGLE source of truth for the backend
 * base URL and media/image URL resolution across the entire frontend.
 *
 * The API base is read from the environment so that development, staging and
 * production can each point at a different backend WITHOUT changing source code.
 *
 *   VITE_API_URL=http://localhost:5000        (dev backend)
 *   VITE_API_URL=https://api.mamabazar.com     (staging / production API origin)
 *
 * `VITE_API_BASE_URL` is supported as a legacy alias.
 *
 * When the variable is unset, the application assumes the API is served from the
 * SAME origin as the frontend (recommended production setup, e.g. Vercel routing
 * `/api/*` and `/uploads/*` to the backend). In that case the base is empty and
 * all request paths are relative — no hardcoded host is ever used.
 */

const envApiUrl = (
  import.meta.env.VITE_API_URL ||
  import.meta.env.VITE_API_BASE_URL ||
  ''
).trim()

// In production, default to deployed backend origin (https://mama-bazar.vercel.app)
const defaultProdApiUrl = 'https://mama-bazar.vercel.app'
const resolvedApiUrl = envApiUrl || (import.meta.env.PROD ? defaultProdApiUrl : '')

// Strip trailing slashes so `${API_BASE_URL}${path}` never double-slashes.
export const API_BASE_URL: string = resolvedApiUrl.replace(/\/+$/, '')

// True when the API is expected to live on the same origin as the frontend.
export const isApiSameOrigin = API_BASE_URL === ''

/**
 * Resolve a path to a URL usable in `fetch` and `<img src>`.
 *  - Absolute URLs (http/https/protocol-relative) are returned as-is.
 *  - Root-relative paths (`/uploads/x.png`, `/api/products`) are prefixed with
 *    API_BASE_URL, or left relative when the API is same-origin.
 *  - Relative paths are returned unchanged.
 */
export const resolveUrl = (path: string | undefined | null): string => {
  if (!path) return ''
  const value = String(path).trim()
  if (!value) return ''
  if (/^https?:\/\//i.test(value) || value.startsWith('//')) return value
  if (value.startsWith('/')) return API_BASE_URL ? `${API_BASE_URL}${value}` : value
  return value
}

/**
 * Resolve a path to an absolute URL — for SEO meta tags, canonical links,
 * clipboard copy, etc. Relative paths are prefixed with API_BASE_URL when the
 * API lives on a separate origin, otherwise with the browser's current origin.
 */
export const resolveAbsoluteUrl = (path: string | undefined | null): string => {
  const url = resolveUrl(path)
  if (!url) return ''
  if (/^https?:\/\//i.test(url) || url.startsWith('//')) return url
  if (url.startsWith('/')) {
    if (API_BASE_URL) return `${API_BASE_URL}${url}`
    return typeof window !== 'undefined' ? `${window.location.origin}${url}` : url
  }
  return url
}
