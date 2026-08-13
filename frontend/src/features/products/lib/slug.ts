/**
 * Product slug helpers — frontend mirror of `backend/src/modules/product/slug.util.ts`.
 *
 * Slugs are strictly ASCII: English letters (a-z), digits (0-9) and hyphens.
 * Bangla text is transliterated to English where possible; anything else that
 * cannot be transliterated is flagged as unsupported.
 */

/** Common Bangla → English loanwords (whole-word lookup, applied first). */
const BANGLA_DICTIONARY: Record<string, string> = {
  'টিভি': 'tv',
  'টেলিভিশন': 'television',
  'মোবাইল': 'mobile',
  'ফোন': 'phone',
  'ল্যাপটপ': 'laptop',
  'কম্পিউটার': 'computer',
  'ফ্রিজ': 'fridge',
  'রেফ্রিজারেটর': 'refrigerator',
  'ওয়াশিং': 'washing',
  'মেশিন': 'machine',
  'টেবিল': 'table',
  'চেয়ার': 'chair',
  'ক্যামেরা': 'camera',
  'হেডফোন': 'headphone',
  'স্পিকার': 'speaker',
  'ইয়ারফোন': 'earphone',
  'ফ্যান': 'fan',
  'লাইট': 'light',
  'বাল্ব': 'bulb',
  'চার্জার': 'charger',
  'ব্যাটারি': 'battery',
  'কীবোর্ড': 'keyboard',
  'মাউস': 'mouse',
  'ডিসপ্লে': 'display',
  'স্ক্রিন': 'screen',
  'বাটন': 'button',
  'পাওয়ার': 'power',
  'স্যামসাং': 'samsung',
  'স্মার্ট': 'smart',
  'গ্যাজেট': 'gadget',
  'ওয়াইফাই': 'wifi',
  'ব্লুটুথ': 'bluetooth',
  'মাইক্রোওয়েভ': 'microwave',
  'ওভেন': 'oven',
  'ইলেকট্রিক': 'electric',
  'ট্রান্সফরমার': 'transformer',
  'গ্যাস': 'gas',
  'প্রসেসর': 'processor',
  'মেমোরি': 'memory',
  'সুইচ': 'switch',
  'কেবল': 'cable',
  'টি-শার্ট': 't-shirt',
  'টি শার্ট': 't-shirt',
  'শার্ট': 'shirt',
  'প্যান্ট': 'pant',
  'জুতা': 'shoes',
  'স্যান্ডেল': 'sandals',
  'ঘড়ি': 'watch',
  'গ্লাস': 'glass',
  'মগ': 'mug',
  'বালিশ': 'pillow',
  'কম্বল': 'blanket',
  'তালা': 'lock',
  'ছাতা': 'umbrella',
}

/** Single-character Bangla → English transliteration table. */
const BANGLA_CHARS: Record<string, string> = {
  // Vowels (independent)
  'অ': 'a', 'আ': 'a', 'ই': 'i', 'ঈ': 'i', 'উ': 'u', 'ঊ': 'u',
  'ঋ': 'ri', 'এ': 'e', 'ঐ': 'oi', 'ও': 'o', 'ঔ': 'ou',
  // Vowel signs (matras)
  'া': 'a', 'ি': 'i', 'ী': 'i', 'ু': 'u', 'ূ': 'u', 'ৃ': 'ri',
  'ে': 'e', 'ৈ': 'oi', 'ো': 'o', 'ৌ': 'ou', 'ৗ': 'o',
  // Consonants
  'ক': 'k', 'খ': 'kh', 'গ': 'g', 'ঘ': 'gh', 'ঙ': 'ng',
  'চ': 'ch', 'ছ': 'ch', 'জ': 'j', 'ঝ': 'jh', 'ঞ': 'n',
  'ট': 't', 'ঠ': 'th', 'ড': 'd', 'ঢ': 'dh', 'ণ': 'n',
  'ত': 't', 'থ': 'th', 'দ': 'd', 'ধ': 'dh', 'ন': 'n',
  'প': 'p', 'ফ': 'f', 'ব': 'b', 'ভ': 'bh', 'ম': 'm',
  'য': 'j', 'র': 'r', 'ল': 'l', 'শ': 'sh', 'ষ': 'sh',
  'স': 's', 'হ': 'h', 'ৎ': 't', 'ড়': 'r', 'ঢ়': 'rh', 'য়': 'y',
  'ং': 'ng', 'ঃ': 'h', 'ঁ': 'n',
  // Digits
  '০': '0', '১': '1', '২': '2', '৩': '3', '৪': '4',
  '৫': '5', '৬': '6', '৭': '7', '৮': '8', '৯': '9',
}

const BANGLA_RANGE = /[\u0980-\u09FF]/

/** Regex matching a complete ASCII slug: English letters, numbers, hyphens. */
export const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

/** Transliterate Bangla text to English (loanwords first, then characters). */
export const transliterateBangla = (input: string): string => {
  const out = input
    .split(/\s+/)
    .map((word) => BANGLA_DICTIONARY[word] ?? word)
    .join(' ')
  return out
    .split('')
    .map((ch) => BANGLA_CHARS[ch] ?? ch)
    .join('')
}

/** True when the string contains any character outside the ASCII range. */
const containsNonAscii = (value: string): boolean => {
  for (let i = 0; i < value.length; i++) {
    if (value.charCodeAt(i) > 127) return true
  }
  return false
}

/** Convert any text to an ASCII slug: lowercase, spaces/symbols become hyphens. */
export const slugifyAscii = (value: string): string =>
  transliterateBangla(value)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')

/** True when the input still contains characters that cannot be transliterated. */
export const hasUnsupportedSlugChars = (value: string): boolean =>
  BANGLA_RANGE.test(value)
    ? containsNonAscii(transliterateBangla(value))
    : containsNonAscii(value)

/** Normalize manual input while typing: lowercase, symbols become hyphens. */
export const sanitizeSlugInput = (value: string): string =>
  value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')

/** Human-readable validation message for the slug field, or null when valid. */
export const slugValidationError = (value: string): string | null => {
  const trimmed = value.trim()
  if (!trimmed) return null
  if (hasUnsupportedSlugChars(trimmed)) {
    return 'Bangla and other unsupported characters are not allowed. Only English letters, numbers and hyphens (e.g. samsung-tv-55).'
  }
  if (!SLUG_PATTERN.test(trimmed)) {
    return 'Slug may only contain English letters, numbers and hyphens (e.g. samsung-tv-55).'
  }
  return null
}
