<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::whereNull('parent_id')->with('children')->get()->keyBy('slug');
        $brands = Brand::all()->keyBy('slug');
        $catalog = ProductCatalogData::get();

        $allCreatedProducts = [];
        $skuRegistry = [];

        DB::transaction(function () use ($categories, $brands, $catalog, &$allCreatedProducts, &$skuRegistry) {
            $globalProductIndex = 0;

            foreach ($catalog as $catData) {
                $catSlug = $catData['cat_slug'];
                $parentCat = $categories->get($catSlug);

                if (!$parentCat) {
                    continue;
                }

                $subCategories = $parentCat->children->values();
                $hasVariants = $catData['has_variants'] ?? false;
                $variantType = $catData['variant_type'] ?? null;

                foreach ($catData['items'] as $itemIdx => $item) {
                    $globalProductIndex++;

                    $brand = $brands->get($item['brand']) ?? $brands->first();
                    $subCat = null;
                    if (isset($item['sub']) && isset($subCategories[$item['sub']])) {
                        $subCat = $subCategories[$item['sub']];
                    } elseif ($subCategories->isNotEmpty()) {
                        $subCat = $subCategories[$itemIdx % $subCategories->count()];
                    }

                    $title = $item['title'];
                    $slug = Str::slug($title);
                    
                    // Generate clean, deterministic unique SKU
                    $brandPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $brand->name), 0, 3));
                    $titlePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 6));
                    $baseSku = sprintf('%s-%s-%03d', $brandPrefix, $titlePrefix, $globalProductIndex);

                    if (isset($skuRegistry[$baseSku])) {
                        $baseSku .= '-' . $globalProductIndex;
                    }
                    $skuRegistry[$baseSku] = true;

                    $price = (float) $item['price'];
                    $salePrice = isset($item['sale']) ? (float) $item['sale'] : null;
                    if ($salePrice !== null && $salePrice >= $price) {
                        $salePrice = null;
                    }
                    $discount = ($salePrice !== null) ? round($price - $salePrice, 2) : 0;
                    $costPrice = round($price * 0.82, 2);
                    $profitMargin = round((($price - $costPrice) / $costPrice) * 100, 2);

                    $stock = (int) ($item['stock'] ?? 20);
                    // Introduce realistic inventory variations (75% in stock, 15% low stock, 10% out of stock)
                    if ($globalProductIndex % 10 === 0) {
                        $stock = 0;
                        $stockStatus = 'out_of_stock';
                    } elseif ($globalProductIndex % 7 === 0) {
                        $stock = rand(1, 4);
                        $stockStatus = 'low_stock';
                    } else {
                        $stock = max(5, $stock);
                        $stockStatus = 'in_stock';
                    }

                    // Generate local image SVGs
                    $img1 = ProductImageSeeder::createProductSvg($title, $slug, 1, $brand->name, $parentCat->name);
                    $img2 = ProductImageSeeder::createProductSvg($title, $slug, 2, $brand->name, $parentCat->name);

                    // Badges
                    $isFeatured = ($itemIdx === 0 && $globalProductIndex <= 45); // ~40-45 featured products
                    $isBestSeller = ($itemIdx % 3 === 0);
                    $isTrending = ($itemIdx % 4 === 0);
                    $isNewArrival = ($itemIdx % 5 === 0);

                    $product = Product::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'title' => $title,
                            'description' => sprintf(
                                '<p>Discover the all-new <strong>%s</strong> by %s. Built with premium materials and engineered for superior durability, exceptional performance, and modern convenience.</p><p>Backed by official warranty and genuine manufacturer guarantee from Mama Bazar.</p>',
                                htmlspecialchars($title),
                                htmlspecialchars($brand->name)
                            ),
                            'short_description' => sprintf('%s genuine authentic stock from %s with fast delivery.', $title, $brand->name),
                            'price' => $price,
                            'sale_price' => $salePrice,
                            'discount' => $discount,
                            'cost_price' => $costPrice,
                            'profit_margin' => $profitMargin,
                            'category_id' => $parentCat->id,
                            'sub_category_id' => $subCat ? $subCat->id : null,
                            'brand_id' => $brand->id,
                            'brand' => $brand->name,
                            'country_of_origin' => $brand->country_of_origin ?? 'International',
                            'sku' => $baseSku,
                            'barcode' => sprintf('890%010d', $globalProductIndex),
                            'tags' => [strtolower($brand->name), strtolower($parentCat->name), 'genuine', 'bestseller'],
                            'warranty' => '1 Year Official Warranty',
                            'weight' => '500g',
                            'dimensions' => '20 x 15 x 5 cm',
                            'features' => ['100% Genuine product', 'Official manufacturer warranty', 'Fast secure door delivery', '7 days return policy'],
                            'return_policy' => '7 days easy return if product is damaged, defective or incorrect.',
                            'warehouse' => 'Dhaka Central Hub',
                            'is_featured' => $isFeatured,
                            'is_trending' => $isTrending,
                            'is_best_seller' => $isBestSeller,
                            'is_new_arrival' => $isNewArrival,
                            'stock' => $stock,
                            'low_stock_alert' => 5,
                            'stock_status' => $stockStatus,
                            'product_status' => 'published',
                            'images' => [$img1, $img2],
                            'status' => 'active',
                        ]
                    );

                    $allCreatedProducts[] = $product;

                    // Seed Product Specs
                    if (isset($item['specs']) && is_array($item['specs'])) {
                        ProductSpec::where('product_id', $product->id)->delete();
                        $sortSpec = 1;
                        foreach ($item['specs'] as $label => $val) {
                            ProductSpec::create([
                                'product_id' => $product->id,
                                'label' => $label,
                                'value' => $val,
                                'sort_order' => $sortSpec++,
                            ]);
                        }
                    }

                    // Seed Product Variants
                    if ($hasVariants && $variantType) {
                        $this->seedVariants($product, $variantType, $skuRegistry);
                    }
                }
            }

            // Seed Product Relationships (Accessories & Cross-sell)
            $this->seedRelationships($allCreatedProducts);
        });
    }

    private function seedVariants(Product $product, string $type, array &$skuRegistry): void
    {
        ProductVariant::where('product_id', $product->id)->delete();

        $variantList = [];

        if ($type === 'phone') {
            $colors = ['Space Black', 'Natural Titanium', 'Deep Blue'];
            $storages = ['128GB', '256GB', '512GB'];

            foreach ($storages as $sIdx => $storage) {
                foreach ($colors as $cIdx => $color) {
                    $vPrice = $product->price + ($sIdx * 8000);
                    $vSale = $product->sale_price ? ($product->sale_price + ($sIdx * 8000)) : null;
                    $colorCode = strtoupper(substr($color, 0, 3));
                    $vSku = sprintf('%s-%s-%s', $product->sku, str_replace('GB', '', $storage), $colorCode);

                    $variantList[] = [
                        'name' => "{$product->title} - {$storage} ({$color})",
                        'options' => ['Storage' => $storage, 'Color' => $color],
                        'price' => $vPrice,
                        'discount_price' => $vSale,
                        'sku' => $vSku,
                        'stock' => rand(4, 20),
                    ];
                }
            }
        } elseif ($type === 'laptop') {
            $configs = [
                ['ram' => '16GB', 'ssd' => '512GB SSD', 'add' => 0],
                ['ram' => '16GB', 'ssd' => '1TB SSD', 'add' => 12000],
                ['ram' => '32GB', 'ssd' => '1TB SSD', 'add' => 28000],
            ];

            foreach ($configs as $cfg) {
                $vPrice = $product->price + $cfg['add'];
                $vSale = $product->sale_price ? ($product->sale_price + $cfg['add']) : null;
                $vSku = sprintf('%s-%s-%s', $product->sku, str_replace('GB', '', $cfg['ram']), str_replace(['GB', 'TB', ' SSD', ' '], '', $cfg['ssd']));

                $variantList[] = [
                    'name' => "{$product->title} ({$cfg['ram']} RAM / {$cfg['ssd']})",
                    'options' => ['RAM' => $cfg['ram'], 'Storage' => $cfg['ssd']],
                    'price' => $vPrice,
                    'discount_price' => $vSale,
                    'sku' => $vSku,
                    'stock' => rand(3, 12),
                ];
            }
        } elseif ($type === 'clothing') {
            $sizes = ['S', 'M', 'L', 'XL'];
            $colors = ['Black', 'Navy', 'Olive'];

            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $vSku = sprintf('%s-%s-%s', $product->sku, strtoupper(substr($color, 0, 3)), $size);

                    $variantList[] = [
                        'name' => "{$product->title} - {$color} / {$size}",
                        'options' => ['Size' => $size, 'Color' => $color],
                        'price' => $product->price,
                        'discount_price' => $product->sale_price,
                        'sku' => $vSku,
                        'stock' => rand(5, 30),
                    ];
                }
            }
        } elseif ($type === 'shoes') {
            $sizes = ['40', '41', '42', '43', '44'];
            $colors = ['Black', 'Brown'];

            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $vSku = sprintf('%s-%s-%s', $product->sku, strtoupper(substr($color, 0, 3)), $size);

                    $variantList[] = [
                        'name' => "{$product->title} - {$color} (Size {$size})",
                        'options' => ['Size' => $size, 'Color' => $color],
                        'price' => $product->price,
                        'discount_price' => $product->sale_price,
                        'sku' => $vSku,
                        'stock' => rand(3, 20),
                    ];
                }
            }
        } elseif ($type === 'watch') {
            $straps = ['Stainless Steel Silver', 'Black Leather Strap', 'Sport Silicone'];

            foreach ($straps as $idx => $strap) {
                $vSku = sprintf('%s-ST%d', $product->sku, $idx + 1);
                $variantList[] = [
                    'name' => "{$product->title} - {$strap}",
                    'options' => ['Strap' => $strap],
                    'price' => $product->price,
                    'discount_price' => $product->sale_price,
                    'sku' => $vSku,
                    'stock' => rand(5, 25),
                ];
            }
        } elseif ($type === 'color_only') {
            $colors = ['Black', 'White', 'Silver'];

            foreach ($colors as $color) {
                $vSku = sprintf('%s-%s', $product->sku, strtoupper(substr($color, 0, 3)));
                $variantList[] = [
                    'name' => "{$product->title} ({$color})",
                    'options' => ['Color' => $color],
                    'price' => $product->price,
                    'discount_price' => $product->sale_price,
                    'sku' => $vSku,
                    'stock' => rand(5, 30),
                ];
            }
        } elseif ($type === 'tablet') {
            $storages = ['128GB', '256GB'];
            $colors = ['Space Grey', 'Silver'];

            foreach ($storages as $sIdx => $storage) {
                foreach ($colors as $color) {
                    $vPrice = $product->price + ($sIdx * 10000);
                    $vSale = $product->sale_price ? ($product->sale_price + ($sIdx * 10000)) : null;
                    $vSku = sprintf('%s-%s-%s', $product->sku, str_replace('GB', '', $storage), strtoupper(substr($color, 0, 3)));

                    $variantList[] = [
                        'name' => "{$product->title} - {$storage} ({$color})",
                        'options' => ['Storage' => $storage, 'Color' => $color],
                        'price' => $vPrice,
                        'discount_price' => $vSale,
                        'sku' => $vSku,
                        'stock' => rand(4, 15),
                    ];
                }
            }
        } elseif ($type === 'baby_diaper') {
            $sizes = ['Medium (7-12kg)', 'Large (9-14kg)', 'XL (12-17kg)'];

            foreach ($sizes as $idx => $size) {
                $vSku = sprintf('%s-SZ%d', $product->sku, $idx + 1);
                $variantList[] = [
                    'name' => "{$product->title} - Size {$size}",
                    'options' => ['Size' => $size],
                    'price' => $product->price,
                    'discount_price' => $product->sale_price,
                    'sku' => $vSku,
                    'stock' => rand(10, 40),
                ];
            }
        } elseif ($type === 'helmet') {
            $sizes = ['M (57-58cm)', 'L (59-60cm)', 'XL (61-62cm)'];
            $colors = ['Matte Black', 'Gloss White'];

            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $vSku = sprintf('%s-%s-%s', $product->sku, strtoupper(substr($color, 0, 3)), substr($size, 0, 1));
                    $variantList[] = [
                        'name' => "{$product->title} - {$color} / {$size}",
                        'options' => ['Size' => $size, 'Color' => $color],
                        'price' => $product->price,
                        'discount_price' => $product->sale_price,
                        'sku' => $vSku,
                        'stock' => rand(4, 18),
                    ];
                }
            }
        }

        foreach ($variantList as $vData) {
            $sku = $vData['sku'];
            if (isset($skuRegistry[$sku])) {
                $sku .= '-' . rand(10, 99);
            }
            $skuRegistry[$sku] = true;

            ProductVariant::create([
                'product_id' => $product->id,
                'name' => $vData['name'],
                'options' => $vData['options'],
                'price' => $vData['price'],
                'discount_price' => $vData['discount_price'],
                'sku' => $sku,
                'barcode' => sprintf('891%010d', rand(100000, 999999)),
                'stock' => $vData['stock'],
                'images' => $product->images,
                'thumbnail' => $product->images[0] ?? null,
                'status' => 'active',
                'availability' => ($vData['stock'] > 0),
            ]);
        }
    }

    private function seedRelationships(array $products): void
    {
        ProductRelation::truncate();

        $count = count($products);
        if ($count < 10) {
            return;
        }

        // Link neighboring products within same categories as frequently_bought_together or accessories
        for ($i = 0; $i < $count; $i++) {
            $current = $products[$i];

            // Related product in same category
            $nextIdx = ($i + 1) % $count;
            $altIdx = ($i + 2) % $count;

            ProductRelation::create([
                'product_id' => $current->id,
                'related_product_id' => $products[$nextIdx]->id,
                'type' => 'frequently_bought_together',
            ]);

            ProductRelation::create([
                'product_id' => $current->id,
                'related_product_id' => $products[$altIdx]->id,
                'type' => 'accessories',
            ]);
        }
    }
}
