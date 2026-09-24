<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->unique();
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->timestamp('expiry_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->decimal('charge', 10, 2);
            $table->string('estimated_delivery', 100)->nullable();
            $table->text('description')->nullable();
            $table->integer('priority')->default(0);
            $table->decimal('free_shipping_min_amount', 10, 2)->nullable();
            $table->boolean('cod_available')->default(true);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->enum('type', ['cod', 'mobile_banking', 'bank', 'online']);
            $table->boolean('enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('maintenance_mode')->default(false);
            $table->json('config')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('checkout_notices', function (Blueprint $table) {
            $table->increments('id');
            $table->text('text');
            $table->integer('priority')->default(0);
            $table->string('background_color', 50)->default('#FFF7ED');
            $table->string('text_color', 50)->default('#9A3412');
            $table->string('icon', 50)->default('alert');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_notices');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('coupons');
    }
};
