<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class BusinessSettingService
{
    public const CACHE_KEY = 'mamabazar:business_info';

    public const CACHE_TTL = 86400; // 24 hours

    /**
     * Default canonical business settings.
     */
    public static function defaults(): array
    {
        return [
            // Basic Business Information
            'business_name' => 'Mama Bazar',
            'site_name' => 'Mama Bazar',
            'tagline' => 'Online Grocery & Lifestyle Essentials',
            'business_description' => 'Your trusted daily online grocery, lifestyle and essentials store. Quality products delivered across Bangladesh.',
            'logo_url' => '/brandlogo.png',
            'favicon_url' => '/brandlogo.png',

            // Contact Information
            'primary_phone' => '01700-000000',
            'secondary_phone' => '',
            'support_phone' => '01700-000000',
            'primary_email' => 'support@mamabazar.com',
            'support_email' => 'support@mamabazar.com',
            'sales_email' => '',
            'whatsapp_number' => '01700-000000',
            'support_url' => '/contact',

            // Business Address
            'address_line1' => 'House 12, Road 4',
            'address_line2' => 'Sector 3, Uttara',
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'postal_code' => '1230',
            'country' => 'Bangladesh',
            'contact_address' => 'House 12, Road 4, Sector 3, Uttara, Dhaka - 1230, Bangladesh',

            // Online Presence
            'website_url' => 'https://mamabazar.com',
            'facebook_url' => 'https://facebook.com/mamabazar.official',
            'instagram_url' => 'https://instagram.com/mamabazar.official',
            'youtube_url' => '',
            'linkedin_url' => '',
            'tiktok_url' => '',
            'twitter_url' => '',

            // Legal and Footer Information
            'copyright_text' => '© :year MamaBazar. All rights reserved.',
            'business_registration' => 'BIN-123456789-0101',
            'footer_description' => 'Your trusted daily online grocery, lifestyle and essentials store. Quality products delivered across Bangladesh.',
            'return_policy_url' => '/pages/return-refund',
            'privacy_policy_url' => '/pages/privacy-policy',
            'terms_url' => '/pages/terms',
            'return_policy_short' => 'Easy 7-day return for damaged or wrong items. Please keep the invoice.',
        ];
    }

    /**
     * Map aliases to canonical keys for backwards compatibility.
     */
    protected static array $aliases = [
        'store_name' => 'business_name',
        'site_name' => 'business_name',
        'store_tagline' => 'tagline',
        'store_address' => 'contact_address',
        'business_address' => 'contact_address',
        'store_phone' => 'primary_phone',
        'contact_number' => 'primary_phone',
        'helpline' => 'primary_phone',
        'store_email' => 'primary_email',
        'email' => 'primary_email',
        'website' => 'website_url',
        'store_website' => 'website_url',
        'trade_license' => 'business_registration',
        'tax_id' => 'business_registration',
    ];

    /**
     * Retrieve all business settings as a cached array with computed helpers.
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::loadAndFormat();
        });
    }

    /**
     * Get a specific business setting key with fallback default.
     */
    public static function get(string $key, $default = null)
    {
        $all = self::all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        $canonical = self::$aliases[$key] ?? $key;

        return $all[$canonical] ?? $default;
    }

    /**
     * Load settings from database and compute formatted values.
     */
    protected static function loadAndFormat(): array
    {
        $defaults = self::defaults();

        try {
            $dbRows = SiteSetting::all()->pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            $dbRows = [];
        }

        $settings = [];

        foreach ($defaults as $key => $defaultVal) {
            $val = $dbRows[$key] ?? null;

            // Check if an alias was stored instead
            if (($val === null || $val === '') && ! empty(self::$aliases)) {
                foreach (self::$aliases as $aliasKey => $targetKey) {
                    if ($targetKey === $key && ! empty($dbRows[$aliasKey])) {
                        $val = $dbRows[$aliasKey];
                        break;
                    }
                }
            }

            $settings[$key] = ($val !== null && $val !== '') ? $val : $defaultVal;
        }

        // Include any additional site_settings keys not in defaults
        foreach ($dbRows as $k => $v) {
            if (! isset($settings[$k])) {
                $settings[$k] = $v;
            }
        }

        // Computed Helpers
        // 1. Build formatted address.
        //    Priority:  (a) if contact_address / store_address / business_address was explicitly
        //                   saved in the database → use it directly as the single source of truth,
        //               (b) otherwise build from individual address_line1/2, city, district, etc.
        //    This prevents duplication when the admin saves a single-line address in "Business
        //    Information" while individual address_line fields are also populated.
        $explicitAddress = '';
        foreach (['contact_address', 'store_address', 'business_address'] as $addrKey) {
            $candidate = $dbRows[$addrKey] ?? '';
            if ($candidate !== null && $candidate !== '') {
                $explicitAddress = $candidate;
                break;
            }
        }

        if ($explicitAddress !== '') {
            // Admin saved a full address string — use it verbatim.
            $settings['formatted_address'] = $explicitAddress;
        } else {
            // No explicit full address stored; build from individual fields.
            $addrParts = array_filter([
                $settings['address_line1'] ?? '',
                $settings['address_line2'] ?? '',
                $settings['city'] ?? '',
                ! empty($settings['postal_code']) ? ($settings['district'] ?? '').' - '.$settings['postal_code'] : ($settings['district'] ?? ''),
                $settings['country'] ?? '',
            ]);

            $settings['formatted_address'] = ! empty($addrParts)
                ? implode(', ', $addrParts)
                : 'Dhaka, Bangladesh';
        }

        // Keep contact_address in sync with the resolved address.
        $settings['contact_address'] = $settings['formatted_address'];

        // 2. Raw phone for tel: links
        $phoneDigits = preg_replace('/[^\d+]/', '', $settings['primary_phone']);
        $settings['phone_raw'] = $phoneDigits ?: '01700000000';

        // 3. WhatsApp link
        $waDigits = preg_replace('/\D/', '', $settings['whatsapp_number'] ?: $settings['primary_phone']);
        if (str_starts_with($waDigits, '01')) {
            $waDigits = '88'.$waDigits;
        } elseif (! str_starts_with($waDigits, '880') && str_starts_with($waDigits, '1')) {
            $waDigits = '880'.$waDigits;
        }
        $settings['whatsapp_url'] = ! empty($waDigits) ? 'https://wa.me/'.$waDigits : 'https://wa.me/8801700000000';

        // 4. Rendered copyright
        $settings['copyright_rendered'] = str_replace(
            [':year', '{year}'],
            date('Y'),
            $settings['copyright_text'] ?? '© :year MamaBazar. All rights reserved.'
        );

        // 5. Stylized name parts (e.g. "Mama" and "Bazar")
        $nameParts = explode(' ', trim($settings['business_name']), 2);
        $settings['name_first_part'] = $nameParts[0] ?? 'Mama';
        $settings['name_second_part'] = $nameParts[1] ?? 'Bazar';

        // 6. Configured social links list
        $socialDefs = [
            'facebook_url' => ['label' => 'Facebook', 'icon' => 'facebook'],
            'instagram_url' => ['label' => 'Instagram', 'icon' => 'instagram'],
            'youtube_url' => ['label' => 'YouTube', 'icon' => 'youtube'],
            'tiktok_url' => ['label' => 'TikTok', 'icon' => 'tiktok'],
            'linkedin_url' => ['label' => 'LinkedIn', 'icon' => 'linkedin'],
            'twitter_url' => ['label' => 'Twitter / X', 'icon' => 'twitter'],
        ];

        $socials = [];
        foreach ($socialDefs as $key => $meta) {
            $url = trim((string) ($settings[$key] ?? ''));
            if ($url !== '' && preg_match('#^https?://#i', $url)) {
                $socials[] = [
                    'key' => $key,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'url' => $url,
                ];
            }
        }
        $settings['social_links'] = $socials;

        return $settings;
    }

    /**
     * Persist multiple business settings to DB and purge cache.
     */
    public static function setMany(array $data): void
    {
        $allowedKeys = array_keys(self::defaults());

        foreach ($data as $key => $val) {
            if (in_array($key, ['_token', '_method', 'logo_file', 'favicon_file'], true)) {
                continue;
            }

            if (! in_array($key, $allowedKeys, true) && ! isset(self::$aliases[$key])) {
                continue;
            }

            $cleanVal = is_string($val) ? trim($val) : $val;

            // Update primary key
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $cleanVal]
            );

            // Keep backwards-compatible alias keys updated
            foreach (self::$aliases as $alias => $target) {
                if ($target === $key) {
                    SiteSetting::updateOrCreate(
                        ['key' => $alias],
                        ['value' => $cleanVal]
                    );
                }
            }
        }

        self::clearCache();
    }

    /**
     * Invalidate the business settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Format store info specifically for invoices and PDF generation.
     */
    public static function forInvoice(): array
    {
        $b = self::all();

        return [
            'name' => $b['business_name'],
            'tagline' => $b['tagline'],
            'address' => $b['formatted_address'],
            'phone' => $b['primary_phone'],
            'email' => $b['support_email'] ?: $b['primary_email'],
            'website' => preg_replace('#^https?://#', '', rtrim($b['website_url'], '/')),
            'tax_id' => $b['business_registration'],
            'return_policy' => $b['return_policy_short'],
            'logo_url' => $b['logo_url'],
            'logo_base64' => self::logoBase64($b['logo_url']),
            'favicon_url' => $b['favicon_url'] ?? null,
        ];
    }

    /**
     * Resolve logo as a base64 image data URI for offline PDF rendering.
     */
    public static function logoBase64(?string $logoUrl = null): ?string
    {
        // Try local files first
        $candidates = [];
        if ($logoUrl && ! str_starts_with($logoUrl, 'http://') && ! str_starts_with($logoUrl, 'https://')) {
            $candidates[] = public_path(ltrim($logoUrl, '/'));
        }

        $candidates[] = public_path('brandlogo.png');
        $candidates[] = public_path('brand-logo.png');
        $candidates[] = public_path('logo.png');

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };

                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
            }
        }

        return null;
    }
}
