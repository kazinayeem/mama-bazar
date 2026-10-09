<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class IpLocationService
{
    /**
     * Resolve approximate country, region, city, ISP, timezone, and lookup status from an IP address.
     *
     * @return array{
     *     ip: string|null,
     *     ip_version: string|null,
     *     country: string|null,
     *     country_code: string|null,
     *     region: string|null,
     *     city: string|null,
     *     isp: string|null,
     *     timezone: string|null,
     *     status: string,
     *     status_label: string,
     *     is_private: bool,
     *     formatted: string
     * }
     */
    public static function lookup(?string $ip): array
    {
        $ip = trim((string) $ip);

        if ($ip === '' || $ip === '0.0.0.0') {
            return self::notRecorded();
        }

        $version = self::detectIpVersion($ip);

        if (self::isPrivateOrReserved($ip)) {
            return [
                'ip' => $ip,
                'ip_version' => $version,
                'country' => null,
                'country_code' => null,
                'region' => null,
                'city' => null,
                'isp' => 'Local / Private Network',
                'timezone' => null,
                'status' => 'private',
                'status_label' => 'Private / Internal Network',
                'is_private' => true,
                'formatted' => 'Local Network',
            ];
        }

        // Check if IP is syntactically invalid
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return self::unavailable($ip, $version, 'Invalid IP address');
        }

        $cacheTtl = (int) config('services.geolocation.cache_ttl', 86400);

        return Cache::remember("geoip:v2:{$ip}", $cacheTtl, function () use ($ip, $version) {
            try {
                if (! config('services.geolocation.enabled', true)) {
                    return self::unavailable($ip, $version, 'Lookup disabled');
                }

                $timeout = (int) config('services.geolocation.timeout', 3);
                $driver = config('services.geolocation.driver', 'ip-api');
                $apiKey = config('services.geolocation.api_key');
                $customEndpoint = config('services.geolocation.endpoint');

                if ($customEndpoint) {
                    $url = str_replace('{ip}', urlencode($ip), $customEndpoint);
                } elseif ($driver === 'ipinfo') {
                    $url = "https://ipinfo.io/{$ip}/json".($apiKey ? "?token={$apiKey}" : '');
                } else {
                    // ip-api.com
                    if ($apiKey) {
                        $url = "https://pro.ip-api.com/json/{$ip}?key={$apiKey}&fields=status,message,country,countryCode,regionName,city,isp,org,timezone,query";
                    } else {
                        $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,regionName,city,isp,org,timezone,query";
                    }
                }

                $response = Http::timeout($timeout)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    if ($driver === 'ipinfo') {
                        $country = $data['country'] ?? null;
                        $countryCode = $data['country'] ?? null;
                        $region = $data['region'] ?? null;
                        $city = $data['city'] ?? null;
                        $isp = $data['org'] ?? null;
                        $timezone = $data['timezone'] ?? null;
                        $isSuccess = ! empty($country) || ! empty($city);
                    } else {
                        $isSuccess = ($data['status'] ?? '') === 'success';
                        $country = $data['country'] ?? null;
                        $countryCode = $data['countryCode'] ?? null;
                        $region = $data['regionName'] ?? null;
                        $city = $data['city'] ?? null;
                        $isp = $data['isp'] ?? ($data['org'] ?? null);
                        $timezone = $data['timezone'] ?? null;
                    }

                    if ($isSuccess) {
                        $isIncomplete = empty($country) || (empty($region) && empty($city));

                        return [
                            'ip' => $ip,
                            'ip_version' => $version,
                            'country' => $country,
                            'country_code' => $countryCode,
                            'region' => $region,
                            'city' => $city,
                            'isp' => $isp,
                            'timezone' => $timezone,
                            'status' => $isIncomplete ? 'incomplete' : 'success',
                            'status_label' => $isIncomplete ? 'Incomplete location' : 'Approximate location',
                            'is_private' => false,
                            'formatted' => self::formatLocation($country, $region, $city),
                        ];
                    }
                }
            } catch (Throwable) {
                // Silently fallback without breaking requests
            }

            return self::unavailable($ip, $version);
        });
    }

    /**
     * Compare customer IP-derived approximate location with the shipping address.
     *
     * @return array{
     *     status: 'likely_match'|'possible_mismatch'|'insufficient_data',
     *     label: string,
     *     headline: string,
     *     description: string,
     *     badge_class: string,
     *     badge_color: string
     * }
     */
    public static function compareLocation(array $geo, ?Order $order): array
    {
        if (! $order) {
            return [
                'status' => 'insufficient_data',
                'label' => 'Insufficient Data',
                'headline' => 'No order data available',
                'description' => 'Cannot compare location without order information.',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'badge_color' => 'slate',
            ];
        }

        // Check if IP is recorded
        if (($geo['status'] ?? '') === 'not_recorded' || empty($geo['ip'])) {
            return [
                'status' => 'insufficient_data',
                'label' => 'Insufficient Data',
                'headline' => 'IP address not recorded',
                'description' => 'Customer IP was not recorded for this order; location comparison is unavailable.',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'badge_color' => 'slate',
            ];
        }

        // Private / local network
        if (! empty($geo['is_private'])) {
            return [
                'status' => 'insufficient_data',
                'label' => 'Insufficient Data',
                'headline' => 'Private or local network IP',
                'description' => 'The order was placed from a local or private network address ('.($geo['ip'] ?? 'Local').'). Geolocation is not applicable for private IPs.',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'badge_color' => 'slate',
            ];
        }

        // Geolocation unavailable or incomplete
        if (($geo['status'] ?? '') === 'unavailable' || empty($geo['country'])) {
            return [
                'status' => 'insufficient_data',
                'label' => 'Insufficient Data',
                'headline' => 'Geolocation lookup unavailable',
                'description' => 'Approximate location data could not be retrieved from the geolocation provider.',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'badge_color' => 'slate',
            ];
        }

        // Collect order shipping location fields
        $shippingCountry = trim((string) ($order->country ?: 'Bangladesh'));
        $shippingDivision = trim((string) ($order->division ?? ''));
        $shippingDistrict = trim((string) ($order->district ?? ''));
        $shippingUpazila = trim((string) ($order->upazila ?? ''));
        $shippingAddress = trim((string) ($order->address ?? ''));

        if ($shippingCountry === '' && $shippingDivision === '' && $shippingDistrict === '' && $shippingAddress === '') {
            return [
                'status' => 'insufficient_data',
                'label' => 'Insufficient Data',
                'headline' => 'Shipping address is incomplete',
                'description' => 'The shipping address lacks geographical details needed for verification.',
                'badge_class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                'badge_color' => 'slate',
            ];
        }

        $geoCountry = trim((string) ($geo['country'] ?? ''));
        $geoCountryCode = strtoupper(trim((string) ($geo['country_code'] ?? '')));
        $geoRegion = trim((string) ($geo['region'] ?? ''));
        $geoCity = trim((string) ($geo['city'] ?? ''));

        $isBangladeshShipping = self::isBangladesh($shippingCountry);
        $isBangladeshIp = self::isBangladesh($geoCountry) || $geoCountryCode === 'BD';

        // Check for cross-border mismatch
        if ($isBangladeshShipping && ! $isBangladeshIp) {
            return [
                'status' => 'possible_mismatch',
                'label' => 'Possible Mismatch',
                'headline' => 'Possible mismatch — manual review recommended',
                'description' => sprintf(
                    'IP geolocation indicates %s%s, which differs from the shipping destination (%s). Note: VPN, roaming, corporate network routing, or an expat ordering for family can cause this difference.',
                    $geoCity ? "{$geoCity}, " : '',
                    $geoCountry,
                    $shippingCountry ?: 'Bangladesh'
                ),
                'badge_class' => 'bg-amber-100 text-amber-800 border border-amber-300',
                'badge_color' => 'amber',
            ];
        }

        if (! $isBangladeshShipping && ! empty($shippingCountry)) {
            $normShipping = strtolower($shippingCountry);
            $normGeo = strtolower($geoCountry);
            if ($normShipping !== $normGeo && $geoCountryCode !== strtoupper($shippingCountry)) {
                return [
                    'status' => 'possible_mismatch',
                    'label' => 'Possible Mismatch',
                    'headline' => 'Possible mismatch — manual review recommended',
                    'description' => sprintf(
                        'IP geolocation indicates %s, differing from shipping country %s. Geolocation is approximate; manual review recommended.',
                        $geoCountry,
                        $shippingCountry
                    ),
                    'badge_class' => 'bg-amber-100 text-amber-800 border border-amber-300',
                    'badge_color' => 'amber',
                ];
            }
        }

        // Region / City comparison within the same country
        $allShippingText = strtolower(implode(' ', array_filter([
            $shippingDivision,
            $shippingDistrict,
            $shippingUpazila,
            $shippingAddress,
        ])));

        $regionMatch = false;
        $geoTokens = array_merge(self::tokenizeRegion($geoRegion), self::tokenizeRegion($geoCity));

        foreach ($geoTokens as $token) {
            if (strlen($token) >= 3 && str_contains($allShippingText, $token)) {
                $regionMatch = true;
                break;
            }
        }

        $shippingDisplay = array_filter([$shippingDistrict ?: $shippingDivision, $shippingCountry]);
        $shippingText = implode(', ', $shippingDisplay) ?: 'Bangladesh';
        $ipDisplay = array_filter([$geoCity ?: $geoRegion, $geoCountry]);
        $ipText = implode(', ', $ipDisplay);

        if ($regionMatch) {
            return [
                'status' => 'likely_match',
                'label' => 'Likely Match',
                'headline' => 'Broadly consistent',
                'description' => sprintf(
                    'IP-derived approximate location (%s) is broadly consistent with the shipping destination (%s). (Note: IP geolocation is approximate and does not pinpoint physical addresses).',
                    $ipText,
                    $shippingText
                ),
                'badge_class' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                'badge_color' => 'emerald',
            ];
        }

        return [
            'status' => 'likely_match',
            'label' => 'Likely Match',
            'headline' => 'Broadly consistent (Country match)',
            'description' => sprintf(
                'Country matches (%s). IP gateway is routed via %s while shipping address is in %s. Regional variances are common on cellular data (e.g. Grameenphone, Robi) and ISP gateways.',
                $geoCountry ?: 'Bangladesh',
                $ipText,
                $shippingText
            ),
            'badge_class' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
            'badge_color' => 'emerald',
        ];
    }

    public static function detectIpVersion(?string $ip): ?string
    {
        if (empty($ip)) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'IPv4';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'IPv6';
        }

        return null;
    }

    public static function isPrivateOrReserved(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return true;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    public static function formatLocation(?string $country, ?string $region, ?string $city): string
    {
        $parts = array_filter([$city, $region, $country]);

        return $parts ? implode(', ', array_unique($parts)) : 'Location unavailable';
    }

    public static function unavailable(?string $ip = null, ?string $version = null, string $label = 'Location lookup unavailable'): array
    {
        return [
            'ip' => $ip,
            'ip_version' => $version ?? ($ip ? self::detectIpVersion($ip) : null),
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
            'isp' => null,
            'timezone' => null,
            'status' => 'unavailable',
            'status_label' => $label,
            'is_private' => false,
            'formatted' => 'Location unavailable',
        ];
    }

    public static function notRecorded(): array
    {
        return [
            'ip' => null,
            'ip_version' => null,
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
            'isp' => null,
            'timezone' => null,
            'status' => 'not_recorded',
            'status_label' => 'Not recorded',
            'is_private' => false,
            'formatted' => 'Not recorded',
        ];
    }

    protected static function isBangladesh(string $country): bool
    {
        $normalized = strtolower(trim($country));

        return in_array($normalized, ['bd', 'bangladesh', 'bangladesh (bd)'], true);
    }

    /**
     * @return array<int, string>
     */
    protected static function tokenizeRegion(string $region): array
    {
        $normalized = strtolower(trim($region));
        $cleaned = str_replace(['division', 'district', 'city', 'state', 'province', '-'], ' ', $normalized);
        $parts = array_filter(array_map('trim', explode(' ', $cleaned)));

        $expanded = [];
        foreach ($parts as $p) {
            $expanded[] = $p;
            if ($p === 'chittagong') {
                $expanded[] = 'chattogram';
            }
            if ($p === 'chattogram') {
                $expanded[] = 'chittagong';
            }
            if ($p === 'barisal') {
                $expanded[] = 'barishal';
            }
            if ($p === 'barishal') {
                $expanded[] = 'barisal';
            }
            if ($p === 'comilla') {
                $expanded[] = 'cumilla';
            }
            if ($p === 'cumilla') {
                $expanded[] = 'comilla';
            }
            if ($p === 'bogra') {
                $expanded[] = 'bogura';
            }
            if ($p === 'bogura') {
                $expanded[] = 'bogra';
            }
            if ($p === 'jessore') {
                $expanded[] = 'jashore';
            }
            if ($p === 'jashore') {
                $expanded[] = 'jessore';
            }
        }

        return array_unique($expanded);
    }
}
