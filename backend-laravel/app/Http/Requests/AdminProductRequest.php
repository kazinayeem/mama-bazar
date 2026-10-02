<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // 1. Map camelCase fields to snake_case equivalents if not already present
        $mappings = [
            'shortDescription' => 'short_description',
            'returnPolicy' => 'return_policy',
            'categoryId' => 'category_id',
            'subCategoryId' => 'sub_category_id',
            'childCategoryId' => 'child_category_id',
            'brandId' => 'brand_id',
            'collectionId' => 'collection_id',
            'vendorId' => 'vendor_id',
            'supplierId' => 'supplier_id',
            'countryOfOrigin' => 'country_of_origin',
            'videoUrl' => 'video_url',
            'paymentPhoneNumber' => 'payment_phone_number',
            'salePrice' => 'sale_price',
            'costPrice' => 'cost_price',
            'profitMargin' => 'profit_margin',
            'shippingCharge' => 'shipping_charge',
            'codFee' => 'cod_fee',
            'flashSalePrice' => 'flash_sale_price',
            'wholesalePrice' => 'wholesale_price',
            'dealerPrice' => 'dealer_price',
            'lowStockAlert' => 'low_stock_alert',
            'minOrder' => 'min_order',
            'maxOrder' => 'max_order',
            'stockStatus' => 'stock_status',
            'unlimitedStock' => 'unlimited_stock',
            'allowBackorder' => 'backorder',
            'allow_backorder' => 'backorder',
            'trackInventory' => 'track_inventory',
            'productStatus' => 'product_status',
            'emiAvailable' => 'emi_available',
            'isFeatured' => 'is_featured',
            'isTrending' => 'is_trending',
            'isFlashSale' => 'is_flash_sale',
            'isNewArrival' => 'is_new_arrival',
            'isBestSeller' => 'is_best_seller',
            'isLimitedEdition' => 'is_limited_edition',
            'isOfficial' => 'is_official',
            'isHotDeal' => 'is_hot_deal',
            'seoTitle' => 'seo_title',
            'seoDescription' => 'seo_description',
            'seoKeywords' => 'seo_keywords',
            'canonicalUrl' => 'canonical_url',
            'ogImage' => 'og_image',
            'twitterImage' => 'twitter_image',
            'structuredData' => 'structured_data',
            'sizeOptions' => 'size_options',
            'colorOptions' => 'color_options',
            'hasVariants' => 'has_variants',
        ];

        foreach ($mappings as $camel => $snake) {
            if (! $this->has($snake) && $this->has($camel)) {
                $this->merge([$snake => $this->input($camel)]);
            }
        }

        // 2. Safely normalize boolean fields without blind casting
        $isWebForm = $this->has('save_mode') || $this->has('saveMode') || $this->has('_token');

        $booleanFields = [
            'track_inventory', 'unlimited_stock', 'backorder', 'has_variants',
            'emi_available', 'is_featured', 'is_trending', 'is_flash_sale',
            'is_new_arrival', 'is_best_seller', 'is_limited_edition',
            'is_official', 'is_hot_deal',
        ];

        foreach ($booleanFields as $field) {
            if (! $this->has($field)) {
                if ($isWebForm) {
                    $this->merge([$field => 0]);
                }

                continue;
            }

            $val = $this->input($field);

            if ($val === null || $val === '' || $val === 'null' || $val === 'undefined') {
                if ($isWebForm) {
                    $this->merge([$field => 0]);
                } else {
                    $this->merge([$field => null]);
                }

                continue;
            }

            if (is_bool($val)) {
                $this->merge([$field => $val ? 1 : 0]);

                continue;
            }

            if (is_numeric($val)) {
                $intVal = (int) $val;
                if ($intVal === 0 || $intVal === 1) {
                    $this->merge([$field => $intVal]);
                }

                // Invalid numbers like 2, 99, or -1 remain as-is so Laravel boolean validator catches them
                continue;
            }

            if (is_string($val)) {
                $lower = strtolower(trim($val));
                if (in_array($lower, ['1', 'true', 'on', 'yes'], true)) {
                    $this->merge([$field => 1]);
                } elseif (in_array($lower, ['0', 'false', 'off', 'no'], true)) {
                    $this->merge([$field => 0]);
                }
                // Non-boolean strings (e.g. 'invalid', 'abc') remain as-is so Laravel boolean validator catches them
            }
        }

        // 3. Decode JSON-encoded fields from Blade forms
        foreach (['variants', 'specs', 'relations', 'images', 'tags', 'features', 'size_options', 'color_options'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $raw = trim($this->input($field));
                if ($raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->merge([$field => $decoded]);
                    }
                } else {
                    $this->merge([$field => []]);
                }
            }
        }

        // 4. Sanitize images array
        if ($this->has('images') && is_array($this->input('images'))) {
            $cleanedImages = array_values(array_filter($this->input('images'), function ($img) {
                return is_string($img) && trim($img) !== '' && ! str_starts_with($img, 'blob:') && ! str_starts_with($img, 'data:');
            }));
            $this->merge(['images' => $cleanedImages]);
        }

        // 5. Handle empty strings for nullable foreign keys and numbers
        foreach ([
            'category_id', 'sub_category_id', 'child_category_id', 'brand_id',
            'collection_id', 'vendor_id', 'supplier_id',
            'max_order', 'sale_price', 'flash_sale_price',
            'wholesale_price', 'dealer_price',
        ] as $nullableField) {
            if ($this->has($nullableField) && ($this->input($nullableField) === '' || $this->input($nullableField) === null || $this->input($nullableField) === 'null')) {
                $this->merge([$nullableField => null]);
            }
        }

        // NOT NULL decimal columns must never become null
        foreach ([
            'discount', 'cost_price', 'profit_margin', 'tax', 'vat',
            'shipping_charge', 'cod_fee',
        ] as $zeroField) {
            if ($this->has($zeroField) && ($this->input($zeroField) === '' || $this->input($zeroField) === null)) {
                $this->merge([$zeroField => 0]);
            }
        }

        if ($this->has('price') && ($this->input('price') === '' || $this->input('price') === null)) {
            if ($this->input('has_variants')) {
                $this->merge(['price' => 0]);
            }
        }
        if ($this->has('stock') && ($this->input('stock') === '' || $this->input('stock') === null)) {
            $this->merge(['stock' => 0]);
        }
        if ($this->has('low_stock_alert') && ($this->input('low_stock_alert') === '' || $this->input('low_stock_alert') === null)) {
            $this->merge(['low_stock_alert' => 10]);
        }
        if ($this->has('min_order') && ($this->input('min_order') === '' || $this->input('min_order') === null)) {
            $this->merge(['min_order' => 1]);
        }
    }

    public function rules(): array
    {
        $productId = $this->route('id') ?? $this->route('product');
        $hasVariants = (bool) $this->input('has_variants');

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string',
            'price' => $hasVariants ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'cost_price' => 'nullable|numeric|min:0',
            'profit_margin' => 'nullable|numeric',
            'tax' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'shipping_charge' => 'nullable|numeric|min:0',
            'cod_fee' => 'nullable|numeric|min:0',
            'flash_sale_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sub_category_id' => 'nullable|integer|exists:categories,id',
            'child_category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'collection_id' => 'nullable|integer|exists:collections,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'stock' => 'nullable|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'min_order' => 'nullable|integer|min:0',
            'max_order' => 'nullable|integer|min:0',
            'stock_status' => 'nullable|string|in:in_stock,low_stock,out_of_stock,on_backorder',
            'product_status' => 'nullable|string|in:draft,published,hidden,archived',
            'status' => 'nullable|string|in:active,inactive',
            'has_variants' => 'nullable|boolean',
            'unlimited_stock' => 'nullable|boolean',
            'backorder' => 'nullable|boolean',
            'track_inventory' => 'nullable|boolean',
            'emi_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_trending' => 'nullable|boolean',
            'is_flash_sale' => 'nullable|boolean',
            'is_new_arrival' => 'nullable|boolean',
            'is_best_seller' => 'nullable|boolean',
            'is_limited_edition' => 'nullable|boolean',
            'is_official' => 'nullable|boolean',
            'is_hot_deal' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string',
            'canonical_url' => 'nullable|string|max:255',
            'og_image' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|string|max:500',
            'structured_data' => ['nullable', function ($attribute, $value, $fail) {
                if (is_string($value) && trim($value) !== '') {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('Structured Data (JSON-LD) must be valid JSON.');
                    }
                }
            }],
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'nullable|string|max:255',
            'variants.*.options' => 'nullable',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.salePrice' => 'nullable|numeric|min:0',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.stock' => 'nullable|integer|min:0',
            'variants.*.thumbnail' => 'nullable|string|max:500',
            'variants.*.availability' => 'nullable',
            'variants.*.remove_image' => 'nullable',
            'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'specs' => 'nullable|array',
            'relations' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => ['required', 'string', 'max:500', function ($attribute, $value, $fail) {
                if (str_starts_with($value, 'blob:') || str_starts_with($value, 'data:')) {
                    $fail('Product images must be uploaded to server storage before saving.');
                }
            }],
            'tags' => 'nullable|array',
            'features' => 'nullable|array',
            'size_options' => 'nullable|array',
            'color_options' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Product title is required.',
            'title.max' => 'Product title may not be greater than 255 characters.',
            'slug.regex' => 'The product URL format is invalid. Use lowercase letters, numbers, and hyphens only.',
            'slug.unique' => 'This product URL is already in use. Please choose another one.',
            'category_id.exists' => 'Please select a valid category.',
            'sub_category_id.exists' => 'Please select a valid subcategory.',
            'child_category_id.exists' => 'Please select a valid child category.',
            'brand_id.exists' => 'Please select a valid brand.',
            'collection_id.exists' => 'Please select a valid collection.',
            'vendor_id.exists' => 'Please select a valid vendor.',
            'supplier_id.exists' => 'Please select a valid supplier.',
            'price.required' => 'Please enter the product price.',
            'price.numeric' => 'Enter a valid price using numbers only.',
            'price.min' => 'Price cannot be negative.',
            'sale_price.numeric' => 'Enter a valid sale price using numbers only.',
            'sale_price.min' => 'Sale price cannot be negative.',
            'discount.numeric' => 'Enter a valid discount percentage.',
            'discount.min' => 'Discount percentage cannot be negative.',
            'discount.max' => 'Discount percentage cannot exceed 100%.',
            'cost_price.numeric' => 'Cost price must be a valid number.',
            'cost_price.min' => 'Cost price cannot be negative.',
            'profit_margin.numeric' => 'Profit margin must be a valid number.',
            'tax.numeric' => 'Tax must be a valid number.',
            'tax.min' => 'Tax cannot be negative.',
            'vat.numeric' => 'VAT must be a valid number.',
            'vat.min' => 'VAT cannot be negative.',
            'shipping_charge.numeric' => 'Shipping charge must be a valid number.',
            'shipping_charge.min' => 'Shipping charge cannot be negative.',
            'cod_fee.numeric' => 'Cash on delivery fee must be a valid number.',
            'cod_fee.min' => 'Cash on delivery fee cannot be negative.',
            'flash_sale_price.numeric' => 'Flash sale price must be a valid number.',
            'flash_sale_price.min' => 'Flash sale price cannot be negative.',
            'wholesale_price.numeric' => 'Wholesale price must be a valid number.',
            'wholesale_price.min' => 'Wholesale price cannot be negative.',
            'dealer_price.numeric' => 'Dealer price must be a valid number.',
            'dealer_price.min' => 'Dealer price cannot be negative.',
            'stock.integer' => 'Stock quantity must be a whole number.',
            'stock.min' => 'Stock quantity cannot be negative.',
            'low_stock_alert.integer' => 'Low stock alert threshold must be a whole number.',
            'low_stock_alert.min' => 'Low stock alert threshold cannot be negative.',
            'min_order.integer' => 'Minimum order quantity must be a whole number.',
            'min_order.min' => 'Minimum order quantity cannot be negative.',
            'max_order.integer' => 'Maximum order quantity must be a whole number.',
            'max_order.min' => 'Maximum order quantity cannot be negative.',
            'track_inventory.boolean' => 'Please select whether inventory tracking is enabled or disabled.',
            'unlimited_stock.boolean' => 'Please select whether unlimited stock is enabled or disabled.',
            'backorder.boolean' => 'Please select whether backorders are allowed or disallowed.',
            'has_variants.boolean' => 'Please select whether product variants are enabled or disabled.',
            'emi_available.boolean' => 'Please select whether EMI is available or unavailable.',
            'is_featured.boolean' => 'Please specify a valid option for featured status.',
            'is_trending.boolean' => 'Please specify a valid option for trending status.',
            'is_flash_sale.boolean' => 'Please specify a valid option for flash sale status.',
            'is_new_arrival.boolean' => 'Please specify a valid option for new arrival status.',
            'is_best_seller.boolean' => 'Please specify a valid option for best seller status.',
            'is_limited_edition.boolean' => 'Please specify a valid option for limited edition status.',
            'is_official.boolean' => 'Please specify a valid option for official status.',
            'is_hot_deal.boolean' => 'Please specify a valid option for hot deal status.',
            'seo_description.max' => 'Your SEO description is too long. Please shorten it.',
            'variants.*.image.image' => 'Please upload a valid product image.',
            'variants.*.image.mimes' => 'Please upload a valid product image (JPG, PNG, or WebP).',
            'variants.*.image.max' => 'The product image must not be larger than 5MB.',
            'variants.*.price.numeric' => 'Variant price must be a valid number.',
            'variants.*.price.min' => 'Variant price cannot be negative.',
            'variants.*.stock.integer' => 'Variant stock must be a whole number.',
            'variants.*.stock.min' => 'Variant stock cannot be negative.',
        ];
    }
}
