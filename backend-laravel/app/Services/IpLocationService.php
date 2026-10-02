<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class IpLocationService
{
    /**
     * Resolve approximate country, region, and city from an IP address.
     *
     * @return array{country: string|null, region: string|null, city: string|null, formatted: string}
     */
    public static function lookup(?string $ip): array
    {
        $ip = trim((string) $ip);

        if ($ip === '' || $ip === '0.0.0.0') {
            return self::unavailable();
        }

        if (self::isPrivateOrReserved($ip)) {
            return [
                'country' => 'Local Network',
                'region' => 'Private IP',
                'city' => 'Localhost',
                'formatted' => 'Local Network',
            ];
        }

        return Cache::remember("geoip:v1:{$ip}", now()->addDays(30), function () use ($ip) {
            try {
                // ip-api.com returns JSON with 2-second timeout
                $response = Http::timeout(2)
                    ->get("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");

                if ($response->successful() && ($response->json('status') === 'success')) {
                    $country = $response->json('country');
                    $region = $response->json('regionName');
                    $city = $response->json('city');

                    return [
                        'country' => $country,
                        'region' => $region,
                        'city' => $city,
                        'formatted' => self::formatLocation($country, $region, $city),
                    ];
                }
            } catch (Throwable $e) {
                // Silently fallback without breaking login
            }

            return self::unavailable();
        });
    }

    public static function isPrivateOrReserved(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return true;
        }

        // Check if filter_var considers it private or reserved
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    public static function formatLocation(?string $country, ?string $region, ?string $city): string
    {
        $parts = array_filter([$city, $region, $country]);

        return $parts ? implode(', ', array_unique($parts)) : 'Location unavailable';
    }

    public static function unavailable(): array
    {
        return [
            'country' => null,
            'region' => null,
            'city' => null,
            'formatted' => 'Location unavailable',
        ];
    }
}
