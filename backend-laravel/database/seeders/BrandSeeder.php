<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brandsData = BrandData::get();

        DB::transaction(function () use ($brandsData) {
            $sort = 1;
            foreach ($brandsData as $index => $b) {
                $logo = ProductImageSeeder::createBrandSvg($b['name'], $b['slug']);

                Brand::updateOrCreate(
                    ['slug' => $b['slug']],
                    [
                        'name' => $b['name'],
                        'logo' => $logo,
                        'banner_image' => $logo,
                        'description' => $b['desc'],
                        'website' => $b['website'],
                        'country_of_origin' => $b['country'],
                        'featured' => $index < 16,
                        'homepage_visibility' => true,
                        'sort_order' => $sort++,
                        'seo_title' => "{$b['name']} Official Store - Mama Bazar",
                        'seo_description' => $b['desc'],
                        'seo_keywords' => strtolower($b['name']) . ", {$b['name']} original, official {$b['name']}",
                        'status' => 'active',
                    ]
                );
            }
        });
    }
}
