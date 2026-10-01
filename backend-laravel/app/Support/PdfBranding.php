<?php

namespace App\Support;

/**
 * Centralized software-attribution branding for generated PDF documents.
 *
 * Single source of truth for the Bornosoft footer used by every PDF
 * (invoices, packing slips, receipts). No external assets or network
 * requests — text + hyperlink only, so dompdf renders it offline.
 */
class PdfBranding
{
    public const MAKER = 'Bornosoft';

    public const TAGLINE = 'Software crafted by Bornosoft';

    public const SUBLINE = 'Custom eCommerce solutions and digital experiences.';

    public const DOMAIN = 'bornosoft.bd';

    public const URL = 'https://bornosoft.bd/';

    public static function attribution(): array
    {
        return [
            'maker' => self::MAKER,
            'tagline' => self::TAGLINE,
            'subline' => self::SUBLINE,
            'domain' => self::DOMAIN,
            'url' => self::URL,
        ];
    }
}
