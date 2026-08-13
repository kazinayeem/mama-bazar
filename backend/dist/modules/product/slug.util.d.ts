/**
 * Product slug utilities.
 *
 * Product slugs are strictly ASCII: English letters (a-z), digits (0-9) and
 * hyphens only. Bengali (Bangla) text is transliterated to English where
 * possible, and any remaining Unicode characters are stripped.
 */
/** Regex matching a complete ASCII slug: English letters, numbers, hyphens. */
export declare const SLUG_PATTERN: RegExp;
/** Transliterate Bangla text to English (loanwords first, then characters). */
export declare const transliterateBangla: (input: string) => string;
/** Convert any text to an ASCII slug: lowercase, spaces/symbols become hyphens. */
export declare const toAsciiSlug: (value: string) => string;
/** True when the input still contains characters that cannot be transliterated. */
export declare const hasUnsupportedSlugChars: (value: string) => boolean;
//# sourceMappingURL=slug.util.d.ts.map