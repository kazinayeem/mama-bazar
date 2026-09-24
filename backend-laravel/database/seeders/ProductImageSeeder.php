<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $catDir = storage_path('app/public/categories');
        $prodDir = storage_path('app/public/products');

        File::ensureDirectoryExists($catDir);
        File::ensureDirectoryExists($prodDir);
        File::ensureDirectoryExists(storage_path('app/public/brands'));
    }

    public static function createBrandSvg(string $name, string $slug): string
    {
        $dir = storage_path('app/public/brands');
        File::ensureDirectoryExists($dir);
        $path = "{$dir}/{$slug}.svg";
        if (file_exists($path)) {
            return "/storage/brands/{$slug}.svg";
        }

        $hue = abs(crc32($slug)) % 360;
        $cleanName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200" width="100%" height="100%">
  <rect width="400" height="200" rx="16" fill="#ffffff" stroke="#e2e8f0" stroke-width="2"/>
  <circle cx="90" cy="100" r="45" fill="hsl({$hue}, 70%, 50%)" fill-opacity="0.12"/>
  <circle cx="90" cy="100" r="28" fill="hsl({$hue}, 70%, 45%)"/>
  <text x="160" y="108" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="28" font-weight="800" fill="#0f172a">
    {$cleanName}
  </text>
  <text x="162" y="132" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="12" font-weight="600" fill="#64748b" letter-spacing="1">
    OFFICIAL BRAND
  </text>
</svg>
SVG;

        file_put_contents($path, $svg);
        return "/storage/brands/{$slug}.svg";
    }

    public static function createCategorySvg(string $name, string $slug): string
    {
        $path = storage_path("app/public/categories/{$slug}.svg");
        if (file_exists($path)) {
            return "/storage/categories/{$slug}.svg";
        }

        $hue = abs(crc32($slug)) % 360;
        $hue2 = ($hue + 45) % 360;
        $cleanName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" width="100%" height="100%">
  <defs>
    <linearGradient id="grad-{$slug}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="hsl({$hue}, 70%, 45%)" />
      <stop offset="100%" stop-color="hsl({$hue2}, 85%, 25%)" />
    </linearGradient>
    <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="8" stdDeviation="12" flood-opacity="0.3"/>
    </filter>
  </defs>
  <rect width="600" height="600" rx="36" fill="url(#grad-{$slug})"/>
  <circle cx="300" cy="240" r="110" fill="#ffffff" fill-opacity="0.15" filter="url(#shadow)"/>
  <rect x="250" y="190" width="100" height="100" rx="20" fill="#ffffff" fill-opacity="0.9"/>
  <circle cx="300" cy="240" r="28" fill="hsl({$hue}, 75%, 45%)"/>
  <text x="300" y="420" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="28" font-weight="700" fill="#ffffff" text-anchor="middle">
    {$cleanName}
  </text>
  <text x="300" y="460" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="16" font-weight="500" fill="#ffffff" fill-opacity="0.75" text-anchor="middle">
    mama-bazar verified catalog
  </text>
</svg>
SVG;

        file_put_contents($path, $svg);
        return "/storage/categories/{$slug}.svg";
    }

    public static function createProductSvg(string $title, string $slug, int $index, string $brand, string $categoryName): string
    {
        $filename = "{$slug}-{$index}.svg";
        $path = storage_path("app/public/products/{$filename}");
        if (file_exists($path)) {
            return "/storage/products/{$filename}";
        }

        $hue = abs(crc32($categoryName)) % 360;
        $cleanTitle = htmlspecialchars(mb_strimwidth($title, 0, 36, '...'), ENT_QUOTES, 'UTF-8');
        $cleanBrand = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
        $viewLabel = $index === 1 ? 'Primary View' : ($index === 2 ? 'Angle View' : 'Detail View');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="100%" height="100%">
  <defs>
    <linearGradient id="bg-{$slug}-{$index}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f8fafc" />
      <stop offset="100%" stop-color="#e2e8f0" />
    </linearGradient>
    <filter id="card-shadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="16" stdDeviation="24" flood-color="#0f172a" flood-opacity="0.12"/>
    </filter>
  </defs>
  <rect width="800" height="800" rx="32" fill="url(#bg-{$slug}-{$index})"/>
  <rect x="80" y="80" width="640" height="640" rx="24" fill="#ffffff" filter="url(#card-shadow)"/>
  
  <rect x="120" y="120" width="120" height="32" rx="16" fill="hsl({$hue}, 80%, 45%)" fill-opacity="0.15"/>
  <text x="180" y="142" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="700" fill="hsl({$hue}, 80%, 35%)" text-anchor="middle">
    {$cleanBrand}
  </text>
  
  <circle cx="400" cy="380" r="170" fill="hsl({$hue}, 60%, 96%)" stroke="hsl({$hue}, 60%, 85%)" stroke-width="2"/>
  
  <!-- Stylized product representation box -->
  <rect x="310" y="290" width="180" height="180" rx="28" fill="hsl({$hue}, 65%, 45%)"/>
  <circle cx="400" cy="380" r="45" fill="#ffffff" fill-opacity="0.25"/>
  <path d="M 375 380 L 425 380 M 400 355 L 400 405" stroke="#ffffff" stroke-width="6" stroke-linecap="round"/>

  <rect x="600" y="120" width="80" height="28" rx="8" fill="#f1f5f9"/>
  <text x="640" y="139" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="12" font-weight="600" fill="#64748b" text-anchor="middle">
    {$viewLabel}
  </text>

  <text x="400" y="610" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="24" font-weight="800" fill="#1e293b" text-anchor="middle">
    {$cleanTitle}
  </text>
  <text x="400" y="645" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="16" font-weight="500" fill="#64748b" text-anchor="middle">
    Official Genuine Stock • Fast Delivery
  </text>
</svg>
SVG;

        file_put_contents($path, $svg);
        return "/storage/products/{$filename}";
    }
}
