<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categoriesData = CategoryData::get();

        DB::transaction(function () use ($categoriesData) {
            $sortOrder = 1;
            foreach ($categoriesData as $index => $cat) {
                $imagePath = ProductImageSeeder::createCategorySvg($cat['name'], $cat['slug']);

                $parent = Category::updateOrCreate(
                    ['slug' => $cat['slug']],
                    [
                        'name' => $cat['name'],
                        'parent_id' => null,
                        'image' => $imagePath,
                        'icon' => $imagePath,
                        'banner' => $imagePath,
                        'thumbnail' => $imagePath,
                        'description' => $cat['description'],
                        'featured' => $index < 12,
                        'sort_order' => $sortOrder++,
                        'homepage_visibility' => true,
                        'seo_title' => "{$cat['name']} - Shop Online at Mama Bazar",
                        'seo_description' => $cat['description'],
                        'seo_keywords' => strtolower(str_replace(' ', ', ', $cat['name'])),
                        'status' => 'active',
                    ]
                );

                $subSort = 1;
                foreach ($cat['subcategories'] as $sub) {
                    $subImage = ProductImageSeeder::createCategorySvg($sub['name'], $sub['slug']);
                    Category::updateOrCreate(
                        ['slug' => $sub['slug']],
                        [
                            'name' => $sub['name'],
                            'parent_id' => $parent->id,
                            'image' => $subImage,
                            'icon' => $subImage,
                            'banner' => $subImage,
                            'thumbnail' => $subImage,
                            'description' => $sub['description'],
                            'featured' => false,
                            'sort_order' => $subSort++,
                            'homepage_visibility' => true,
                            'seo_title' => "{$sub['name']} - Best Prices & Offers",
                            'seo_description' => $sub['description'],
                            'seo_keywords' => strtolower(str_replace(' ', ', ', $sub['name'])),
                            'status' => 'active',
                        ]
                    );
                }
            }
        });
    }
}
