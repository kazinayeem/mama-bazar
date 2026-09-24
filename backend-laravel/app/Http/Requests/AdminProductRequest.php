<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
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
            'category_id' => 'nullable|integer',
            'sub_category_id' => 'nullable|integer',
            'child_category_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'collection_id' => 'nullable|integer',
            'vendor_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'stock' => 'nullable|integer|min:0',
            'low_stock_alert' => 'nullable|integer|min:0',
            'min_order' => 'nullable|integer|min:0',
            'max_order' => 'nullable|integer|min:0',
            'stock_status' => 'nullable|string|in:in_stock,low_stock,out_of_stock,on_backorder',
            'product_status' => 'nullable|string|in:draft,published,hidden,archived',
            'status' => 'nullable|string|in:active,inactive',
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
            'seo_description' => 'nullable|string',
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
            'tags' => 'nullable|array',
            'features' => 'nullable|array',
            'size_options' => 'nullable|array',
            'color_options' => 'nullable|array',
        ];
    }
}
