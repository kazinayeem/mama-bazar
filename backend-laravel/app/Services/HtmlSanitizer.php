<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * XSS-safe HTML sanitizer for product descriptions (and similar rich text).
 * Uses HTMLPurifier — not homemade regex.
 */
class HtmlSanitizer
{
    protected static ?HTMLPurifier $purifier = null;

    public static function purify(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $trimmed = trim($html);
        if ($trimmed === '') {
            return null;
        }

        // Plain text (no rich-text HTML tags): escape + wrap — never treat as HTML blindly.
        if (! self::looksLikeHtml($trimmed)) {
            $escaped = htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $withBreaks = nl2br($escaped, false);

            return '<p>'.$withBreaks.'</p>';
        }

        $clean = trim(self::purifier()->purify($trimmed));
        if ($clean === '') {
            return null;
        }

        // Purifier may leave bare text if all tags were stripped — wrap it.
        if (! self::looksLikeHtml($clean)) {
            return '<p>'.nl2br(htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false).'</p>';
        }

        return $clean;
    }

    /**
     * True when content already uses rich-text tags (not arbitrary angle brackets).
     */
    public static function looksLikeHtml(string $text): bool
    {
        return (bool) preg_match(
            '/<\s*(?:p|div|h[1-6]|ul|ol|li|table|thead|tbody|tfoot|tr|td|th|blockquote|br|hr|strong|em|b|i|u|s|strike|sub|sup|a|img|span|caption|colgroup|col)\b/i',
            $text
        );
    }

    /**
     * Sanitize for display only (does not wrap plain text — escapes it instead).
     * Prefer purify() on write; use this as a defense-in-depth on render.
     */
    public static function forDisplay(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $trimmed = trim($html);

        if (! self::looksLikeHtml($trimmed)) {
            return '<p>'.nl2br(htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false).'</p>';
        }

        $clean = trim(self::purifier()->purify($trimmed));

        return self::postProcess($clean);
    }

    protected static function purifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('Cache.SerializerPath', storage_path('app/htmlpurifier'));
        $config->set('Cache.SerializerPermissions', 0755);

        // Safe formatting tags only — no script/iframe/object/form/svg.
        $config->set('HTML.Allowed', implode(',', [
            'p[style|class]',
            'br',
            'hr',
            'span[style|class]',
            'div[style|class]',
            'h1[style|class]',
            'h2[style|class]',
            'h3[style|class]',
            'h4[style|class]',
            'h5[style|class]',
            'h6[style|class]',
            'strong',
            'b',
            'em',
            'i',
            'u',
            's',
            'strike',
            'sub',
            'sup',
            'blockquote[style|class]',
            'ul[style|class]',
            'ol[style|class]',
            'li[style|class]',
            'a[href|title|target|rel|class]',
            'img[src|alt|title|width|height|class|style]',
            'table[class|style|border|cellpadding|cellspacing|width]',
            'thead',
            'tbody',
            'tfoot',
            'tr[class|style]',
            'th[class|style|colspan|rowspan|scope]',
            'td[class|style|colspan|rowspan]',
            'colgroup',
            'col[span|width]',
            'caption',
        ]));

        $config->set('CSS.AllowedProperties', [
            'text-align',
            'text-decoration',
            'font-weight',
            'font-style',
            'color',
            'background-color',
            'padding-left',
            'margin-left',
            'width',
            'height',
            'max-width',
            'border',
            'border-collapse',
            'border-color',
            'border-style',
            'border-width',
        ]);

        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', false);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);
        // Reject javascript:, data:, vbscript: via AllowedSchemes + DisableExternalResources for data
        $config->set('URI.DisableExternalResources', false);
        $config->set('URI.DisableResources', false);

        // Images: relative /storage/... and same-origin http(s) only (no data: URIs).
        $config->set('URI.Base', config('app.url'));
        $config->set('URI.MakeAbsolute', false);

        // Auto-add rel on target=_blank links
        $config->set('HTML.Attr.Name.UseCDATA', true);

        if (! is_dir(storage_path('app/htmlpurifier'))) {
            @mkdir(storage_path('app/htmlpurifier'), 0755, true);
        }

        self::$purifier = new HTMLPurifier($config);

        return self::$purifier;
    }

    /**
     * Post-process purified HTML: force noopener noreferrer on links;
     * restrict img src to /storage/ paths (or same-host /storage/).
     */
    public static function postProcess(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // Add rel=noopener noreferrer to external-target links
        $html = preg_replace_callback(
            '/<a\s([^>]*?)>/i',
            function (array $m) {
                $attrs = $m[1];
                if (! preg_match('/\brel\s*=/i', $attrs)) {
                    $attrs .= ' rel="noopener noreferrer"';
                } else {
                    $attrs = preg_replace_callback(
                        '/\brel\s*=\s*(["\'])(.*?)\1/i',
                        function (array $rm) {
                            $parts = preg_split('/\s+/', trim($rm[2])) ?: [];
                            foreach (['noopener', 'noreferrer'] as $token) {
                                if (! in_array(strtolower($token), array_map('strtolower', $parts), true)) {
                                    $parts[] = $token;
                                }
                            }

                            return 'rel='.$rm[1].implode(' ', $parts).$rm[1];
                        },
                        $attrs
                    );
                }

                return '<a '.$attrs.'>';
            },
            $html
        ) ?? $html;

        // Strip images that are not local /storage/ (or legacy /uploads/)
        $html = preg_replace_callback(
            '/<img\s([^>]*?)>/i',
            function (array $m) {
                $attrs = $m[1];
                if (! preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/i', $attrs, $sm)) {
                    return '';
                }
                $src = trim($sm[2]);
                if (! self::isAllowedImageSrc($src)) {
                    return '';
                }
                // Ensure alt exists
                if (! preg_match('/\balt\s*=/i', $attrs)) {
                    $attrs .= ' alt=""';
                }

                return '<img '.$attrs.'>';
            },
            $html
        ) ?? $html;

        return $html;
    }

    public static function clean(?string $html): ?string
    {
        $purified = self::purify($html);
        if ($purified === null) {
            return null;
        }

        $processed = self::postProcess($purified);

        return $processed === '' ? null : $processed;
    }

    public static function isAllowedImageSrc(string $src): bool
    {
        if ($src === '' || preg_match('#^(javascript|data|vbscript):#i', $src)) {
            return false;
        }

        // Relative local storage
        if (preg_match('#^/(storage|uploads)/#i', $src)) {
            return true;
        }

        // Absolute URL pointing at this app's /storage/ or /uploads/
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($src, $appUrl.'/storage/')) {
            return true;
        }
        if ($appUrl !== '' && str_starts_with($src, $appUrl.'/uploads/')) {
            return true;
        }

        return false;
    }
}
