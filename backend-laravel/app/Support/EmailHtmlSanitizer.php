<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist sanitizer for admin-authored email HTML.
 *
 * Keeps the table/inline-style markup email clients need, removes scripts,
 * embeds, forms, event handlers and dangerous URL schemes. Placeholders such
 * as {{reset_url}} survive untouched (including inside href attributes).
 */
class EmailHtmlSanitizer
{
    protected const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'caption', 'center', 'code', 'col', 'colgroup', 'div', 'em', 'font',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'li', 'ol', 'p', 'pre', 's', 'small',
        'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul',
    ];

    /** Elements removed together with their content. */
    protected const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea',
        'link', 'meta', 'base', 'svg', 'math', 'frame', 'frameset', 'applet', 'noscript', 'template', 'head', 'title',
    ];

    protected const ALLOWED_ATTRIBUTES = [
        'align', 'alt', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'class', 'color', 'colspan', 'dir',
        'face', 'height', 'href', 'lang', 'rel', 'role', 'rowspan', 'size', 'src', 'style', 'target', 'title',
        'valign', 'width',
    ];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        // libxml URI-escapes href/src on output; shield placeholders with URL-safe sentinels.
        $html = preg_replace('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', '__MBPH_$1_HPBM__', $html) ?? $html;

        $previous = libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__email_root__">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__email_root__');
        if (! $root) {
            return '';
        }

        self::cleanChildren($root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $doc->saveHTML($child);
        }

        $output = preg_replace('/__MBPH_([a-z0-9_]+?)_HPBM__/i', '{{$1}}', $output) ?? $output;

        return trim($output);
    }

    protected static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::cleanChildren($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child);
            self::cleanChildren($child);
        }
    }

    protected static function cleanAttributes(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = (string) $attribute->value;

            if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($value, $name === 'src')) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'style') {
                $clean = self::cleanStyle($value);
                $clean === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $clean);
            }

            if ($name === 'target') {
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    public static function isSafeUrl(string $url, bool $isImage = false): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', $url) ?? '');

        if ($normalized === '' || str_starts_with($normalized, '{{') || str_starts_with($normalized, '__mbph_') || str_starts_with($normalized, '#') || str_starts_with($normalized, '/')) {
            return true;
        }

        if ($isImage && preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#', $normalized)) {
            return true;
        }

        if (! preg_match('#^([a-z][a-z0-9+.\-]*):#', $normalized, $match)) {
            return true;
        }

        return in_array($match[1], ['http', 'https', 'mailto', 'tel'], true);
    }

    protected static function cleanStyle(string $style): string
    {
        $style = html_entity_decode($style, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lower = strtolower($style);

        foreach (['expression', 'javascript:', 'vbscript:', 'behavior', '-moz-binding', '@import', '</'] as $needle) {
            if (str_contains($lower, $needle)) {
                return '';
            }
        }

        $style = preg_replace('/url\s*\(\s*([\'"]?)(?!https?:)[^)]*\1\s*\)/i', 'none', $style) ?? '';

        return trim($style);
    }
}
