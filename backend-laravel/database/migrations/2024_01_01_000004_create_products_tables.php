<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('profit_margin', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('shipping_charge', 10, 2)->default(0);
            $table->decimal('cod_fee', 10, 2)->default(0);
            $table->decimal('flash_sale_price', 10, 2)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->decimal('dealer_price', 10, 2)->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('sub_category_id')->nullable();
            $table->unsignedInteger('child_category_id')->nullable();
            $table->unsignedInteger('collection_id')->nullable();
            $table->unsignedInteger('brand_id')->nullable();
            $table->string('brand', 100)->nullable();
            $table->unsignedInteger('vendor_id')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('supplier', 255)->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->json('tags')->nullable();
            $table->string('warranty', 100)->nullable();
            $table->string('weight', 50)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->json('features')->nullable();
            $table->text('return_policy')->nullable();
            $table->string('warehouse', 255)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('twitter_image', 500)->nullable();
            $table->json('structured_data')->nullable();
            $table->json('draft')->nullable();
            $table->boolean('emi_available')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_flash_sale')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_limited_edition')->default(false);
            $table->boolean('is_official')->default(false);
            $table->boolean('is_hot_deal')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->json('meta')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('low_stock_alert')->default(10);
            $table->integer('min_order')->default(1);
            $table->integer('max_order')->nullable();
            $table->boolean('unlimited_stock')->default(false);
            $table->boolean('backorder')->default(false);
            $table->boolean('track_inventory')->default(true);
            $table->string('stock_status', 20)->default('in_stock');
            $table->string('product_status', 30)->default('published');
            $table->json('images')->nullable();
            $table->json('size_options')->nullable();
            $table->json('color_options')->nullable();
            $table->json('payment_methods')->nullable();
            $table->string('payment_phone_number', 20)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('name', 500);
            $table->json('options');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->integer('stock')->default(0);
            $table->string('weight', 50)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->json('images')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->string('warranty', 100)->nullable();
            $table->boolean('availability')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('product_specs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('label', 255);
            $table->text('value');
            $table->integer('sort_order')->default(0);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('product_relations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('related_product_id');
            $table->enum('type', [
                'frequently_bought_together',
                'cross_sell',
                'up_sell',
                'accessories',
                'similar',
            ]);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('related_product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_relations');
        Schema::dropIfExists('product_specs');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
