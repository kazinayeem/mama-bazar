<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifySeedData extends Command
{
    protected $signature = 'data:verify';
    protected $description = 'Verify the consistency, counts, and relational integrity of seeded e-commerce data';

    public function handle(): int
    {
        $this->info('========================================================');
        $this->info('           E-COMMERCE SEED DATA VERIFICATION           ');
        $this->info('========================================================');

        $hasErrors = false;

        // 1. Categories
        $parentCategoriesCount = Category::whereNull('parent_id')->count();
        $subCategoriesCount = Category::whereNotNull('parent_id')->count();

        $catStatus = ($parentCategoriesCount === 50) ? '✓' : '✗ (Expected exactly 50)';
        $subStatus = ($subCategoriesCount >= 150) ? '✓' : '✗ (Expected 150+)';

        $this->line(sprintf('Main Categories:        %d %s', $parentCategoriesCount, $catStatus));
        $this->line(sprintf('Subcategories:          %d %s', $subCategoriesCount, $subStatus));

        if ($parentCategoriesCount !== 50 || $subCategoriesCount < 150) {
            $hasErrors = true;
        }

        // 2. Brands
        $brandsCount = Brand::count();
        $brandStatus = ($brandsCount >= 50) ? '✓' : '✗ (Expected 50+)';
        $this->line(sprintf('Brands:                 %d %s', $brandsCount, $brandStatus));
        if ($brandsCount < 50) {
            $hasErrors = true;
        }

        // 3. Products
        $productsCount = Product::count();
        $prodStatus = ($productsCount === 500) ? '✓' : '✗ (Expected exactly 500)';
        $this->line(sprintf('Products:               %d %s', $productsCount, $prodStatus));
        if ($productsCount !== 500) {
            $hasErrors = true;
        }

        // 4. Product Variants
        $variantsCount = ProductVariant::count();
        $this->line(sprintf('Product Variants:       %d ✓', $variantsCount));

        // 5. Product Specs
        $specsCount = ProductSpec::count();
        $this->line(sprintf('Product Specs:          %d ✓', $specsCount));

        // 6. Product Relations
        $relationsCount = ProductRelation::count();
        $this->line(sprintf('Product Relations:      %d ✓', $relationsCount));

        // 7. Duplicate SKUs check
        $dupProductSkus = DB::table('products')
            ->select('sku', DB::raw('count(*) as c'))
            ->groupBy('sku')
            ->having('c', '>', 1)
            ->count();

        $dupVariantSkus = DB::table('product_variants')
            ->select('sku', DB::raw('count(*) as c'))
            ->groupBy('sku')
            ->having('c', '>', 1)
            ->count();

        $duplicateSkus = $dupProductSkus + $dupVariantSkus;
        $skuStatus = ($duplicateSkus === 0) ? '✓' : '✗ Duplicate SKUs found!';
        $this->line(sprintf('Duplicate SKUs:         %d %s', $duplicateSkus, $skuStatus));
        if ($duplicateSkus > 0) {
            $hasErrors = true;
        }

        // 8. Relational Integrity Checks
        $invalidCatRelations = Product::whereNull('category_id')
            ->orWhereNotIn('category_id', Category::pluck('id'))
            ->count();
        $catRelStatus = ($invalidCatRelations === 0) ? '✓' : '✗ Orphaned category ids!';
        $this->line(sprintf('Invalid Category Rel:   %d %s', $invalidCatRelations, $catRelStatus));

        $invalidBrandRelations = Product::whereNull('brand_id')
            ->orWhereNotIn('brand_id', Brand::pluck('id'))
            ->count();
        $brandRelStatus = ($invalidBrandRelations === 0) ? '✓' : '✗ Orphaned brand ids!';
        $this->line(sprintf('Invalid Brand Rel:      %d %s', $invalidBrandRelations, $brandRelStatus));

        $invalidSubcatRelations = Category::whereNotNull('parent_id')
            ->whereNotIn('parent_id', Category::whereNull('parent_id')->pluck('id'))
            ->count();
        $subRelStatus = ($invalidSubcatRelations === 0) ? '✓' : '✗ Broken subcategory parent_id!';
        $this->line(sprintf('Invalid Subcategory Rel:%d %s', $invalidSubcatRelations, $subRelStatus));

        $orphanedVariants = ProductVariant::whereNotIn('product_id', Product::pluck('id'))->count();
        $varRelStatus = ($orphanedVariants === 0) ? '✓' : '✗ Orphaned product variants!';
        $this->line(sprintf('Invalid Variants:       %d %s', $orphanedVariants, $varRelStatus));

        if ($invalidCatRelations > 0 || $invalidBrandRelations > 0 || $invalidSubcatRelations > 0 || $orphanedVariants > 0) {
            $hasErrors = true;
        }

        // 9. Pricing & Stock Checks
        $invalidPrices = Product::where('price', '<=', 0)
            ->orWhere(function ($q) {
                $q->whereNotNull('sale_price')->whereRaw('sale_price >= price');
            })
            ->count();
        $priceStatus = ($invalidPrices === 0) ? '✓' : '✗ Invalid product price or sale price!';
        $this->line(sprintf('Invalid Pricing:        %d %s', $invalidPrices, $priceStatus));

        $invalidStock = Product::where('stock', '<', 0)->count();
        $stockStatus = ($invalidStock === 0) ? '✓' : '✗ Negative stock!';
        $this->line(sprintf('Invalid Stock:          %d %s', $invalidStock, $stockStatus));

        if ($invalidPrices > 0 || $invalidStock > 0) {
            $hasErrors = true;
        }

        // 10. Image verification (physical files in storage)
        $brokenImages = 0;
        $productsWithImages = Product::all();
        foreach ($productsWithImages as $p) {
            if (empty($p->images) || !is_array($p->images)) {
                $brokenImages++;
                continue;
            }
            foreach ($p->images as $relPath) {
                $cleanPath = str_replace('/storage/', '', $relPath);
                $fullPath = storage_path('app/public/' . $cleanPath);
                if (!file_exists($fullPath)) {
                    $brokenImages++;
                }
            }
        }
        $imgStatus = ($brokenImages === 0) ? '✓' : "✗ ({$brokenImages} broken image references)";
        $this->line(sprintf('Broken Image Ref:       %d %s', $brokenImages, $imgStatus));
        if ($brokenImages > 0) {
            $hasErrors = true;
        }

        $this->info('========================================================');
        if ($hasErrors) {
            $this->error('VALIDATION FAILED: Some consistency checks did not pass.');
            return 1;
        }

        $this->info('ALL INTEGRITY & RELATIONAL CHECKS PASSED SUCCESSFULLY!');
        return 0;
    }
}
