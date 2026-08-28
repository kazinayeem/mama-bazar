/**
 * Centralized Cloudinary URL handling.
 *
 * Image URLs are stored as the Cloudinary `secure_url` returned at upload time
 * (e.g. `https://res.cloudinary.com/<cloud>/image/upload/v<ts>/<public_id>`).
 * That URL is already a complete, valid delivery URL and loads without any
 * transformation — so callers should use it as-is.
 *
 * Optimizations (`f_auto,q_auto`, resizing, …) are only applied on top of a
 * genuine Cloudinary upload URL, and are inserted directly after the
 * `/image/upload/` delivery segment so the result is always a valid
 * Cloudinary transformation path — for BOTH versioned URLs
 * (`.../upload/v<ts>/<public_id>`) and unversioned URLs
 * (`.../upload/<public_id>`). If the URL isn't a Cloudinary upload, or already
 * carries the requested transform, the original URL is returned untouched.
 * This helper can never emit a malformed path that Cloudinary would reject
 * with a 400.
 */

import { resolveUrl } from './apiConfig'

const CLOUDINARY_HOST_RE = /^https?:\/\/res\.cloudinary\.com\//i

const CLOUDINARY_UPLOAD_RE = /^(https?:\/\/res\.cloudinary\.com\/[^/]+\/image\/upload\/)/i

/** True when `url` points at Cloudinary's CDN (any resource type). */
export const isCloudinaryUrl = (url?: string | null): boolean => {
  const absolute = resolveUrl(url)
  return !!absolute && CLOUDINARY_HOST_RE.test(absolute)
}

/**
 * Resolve any stored image URL for use in `<img src>` / CSS.
 *
 * Cloudinary `secure_url`s are returned exactly as stored — they are already
 * valid and do not need a transformation to load.
 */
export const resolveImageUrl = (url?: string | null): string => resolveUrl(url)

/**
 * Apply a Cloudinary transformation to an upload URL.
 *
 * `transforms` (default `f_auto,q_auto`) is inserted immediately after the
 * `/image/upload/` segment, producing:
 *
 *   https://res.cloudinary.com/<cloud>/image/upload/<transforms>/v<ts>/<public_id>
 *   https://res.cloudinary.com/<cloud>/image/upload/<transforms>/<public_id>
 *
 * Returns the original URL unchanged when:
 *  - the URL isn't a Cloudinary image upload, or
 *  - the URL already contains the requested transform (no duplication), or
 *  - the URL cannot be parsed safely.
 *
 * Never returns a malformed transformation path.
 */
export const optimizeCloudinaryUrl = (
  url?: string | null,
  transforms = 'f_auto,q_auto',
): string => {
  const absolute = resolveUrl(url)
  if (!absolute) return ''

  const match = CLOUDINARY_UPLOAD_RE.exec(absolute)
  if (!match) return absolute

  if (absolute.slice(match[0].length).includes(transforms)) return absolute

  const base = match[0]
  const rest = absolute.slice(base.length)
  if (!rest) return absolute

  return `${base}${transforms}/${rest}`
}

/**
 * Optimized URL for homepage product cards (240×240 display).
 * Applies format auto, quality auto, and width constraint.
 * Falls back to the original URL for non-Cloudinary images.
 */
export const getCloudinaryCardUrl = (url?: string | null): string =>
  optimizeCloudinaryUrl(url, 'w_240,h_240,c_pad,f_auto,q_auto')

/**
 * Optimized URL for category chip images (120×120 display).
 */
export const getCloudinaryCategoryUrl = (url?: string | null): string =>
  optimizeCloudinaryUrl(url, 'w_120,h_120,c_fill,f_auto,q_auto')

/**
 * Optimized URL for brand logos (96×96 display).
 */
export const getCloudinaryBrandUrl = (url?: string | null): string =>
  optimizeCloudinaryUrl(url, 'w_96,h_96,c_pad,f_auto,q_auto')

/**
 * Optimized URL for hero/banner images — format+quality only, no resize
 * (hero fills the viewport and needs its natural dimensions).
 */
export const getCloudinaryBannerUrl = (url?: string | null): string =>
  optimizeCloudinaryUrl(url, 'f_auto,q_auto')

/**
 * Responsive hero URL for a specific render width.
 *
 * Uses `c_limit` so the image is only DOWNSCALED when wider than `width`
 * (never upscaled, never cropped, never padded) — the natural aspect ratio is
 * preserved and the hero's `object-contain` display is visually identical to
 * the source, just a far smaller file. This is what the homepage LCP element
 * loads; right-sizing it from a multi-megabyte original to the viewport width
 * is the single biggest image-transfer saving on the page.
 *
 *   desktop (≥1024px): w_1280   tablet (≥640px): w_900   mobile: w_640
 */
export const getCloudinaryHeroUrl = (url?: string | null, width = 1280): string =>
  optimizeCloudinaryUrl(url, `w_${width},c_limit,f_auto,q_auto`)

/**
 * Optimized URL for collection tile images (object-cover, ~4/3 tiles, up to
 * 4 per row on desktop). `c_limit` downscales to at most 500px wide so CSS
 * `object-cover` still crops to the tile exactly as before, but the transfer
 * is a fraction of the original-resolution image.
 */
export const getCloudinaryCollectionUrl = (url?: string | null): string =>
  optimizeCloudinaryUrl(url, 'w_500,c_limit,f_auto,q_auto')
