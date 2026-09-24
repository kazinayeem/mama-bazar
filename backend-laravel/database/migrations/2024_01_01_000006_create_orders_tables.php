<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_id', 20)->unique();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('customer_name', 255);
            $table->string('phone', 20);
            $table->string('alternative_phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('upazila', 100)->nullable();
            $table->string('area', 150)->nullable();
            $table->text('address');
            $table->string('apartment', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->unsignedInteger('shipping_method_id')->nullable();
            $table->string('shipping_method_name', 255)->nullable();
            $table->decimal('shipping_cost', 10, 2);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->text('order_note')->nullable();
            $table->text('checkout_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->decimal('total_price', 10, 2);
            $table->string('payment_method', 50)->default('cod');
            $table->string('transaction_id', 100)->nullable();
            $table->string('sender_number', 30)->nullable();
            $table->string('payment_screenshot', 500)->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->decimal('amount_sent', 10, 2)->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('courier_tracking_number', 120)->nullable();
            $table->enum('payment_status', [
                'pending',
                'payment_pending',
                'payment_verification',
                'verified',
                'success',
                'failed',
                'rejected',
                'refunded',
            ])->default('pending');
            $table->enum('status', [
                'pending',
                'payment_pending',
                'payment_verification',
                'confirmed',
                'processing',
                'packed',
                'shipped',
                'out_for_delivery',
                'delivered',
                'returned',
                'cancelled',
                'refunded',
            ])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->enum('status', [
                'pending',
                'payment_pending',
                'payment_verification',
                'confirmed',
                'processing',
                'packed',
                'shipped',
                'out_for_delivery',
                'delivered',
                'returned',
                'cancelled',
                'refunded',
            ]);
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by_user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variant_id')->nullable();
            $table->string('size', 30)->nullable();
            $table->string('color', 50)->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('orders');
    }
};
