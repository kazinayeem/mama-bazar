<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_id', 64)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedInteger('order_id')->nullable()->index();
            $table->string('status', 20)->default('active')->index(); // active, incomplete, converted, expired
            $table->unsignedTinyInteger('progress_percent')->default(0)->index();
            $table->string('current_step', 50)->default('opened');
            $table->text('completed_fields')->nullable();
            $table->text('milestones')->nullable();
            $table->unsignedInteger('cart_item_count')->default(0);
            $table->decimal('cart_subtotal', 12, 2)->default(0.00);
            $table->string('shipping_method_name', 100)->nullable();
            $table->string('payment_method_code', 50)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('device_category', 20)->default('desktop');
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->timestamp('first_active_at')->nullable()->index();
            $table->timestamp('last_active_at')->nullable()->index();
            $table->timestamp('converted_at')->nullable()->index();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_active_at'], 'idx_chk_status_last_active');
            $table->index(['status', 'created_at'], 'idx_chk_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};
