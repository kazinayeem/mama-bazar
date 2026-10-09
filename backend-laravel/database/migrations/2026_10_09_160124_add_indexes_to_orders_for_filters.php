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
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['payment_status', 'created_at'], 'orders_payment_status_created_at_idx');
            $table->index('payment_method', 'orders_payment_method_idx');
            $table->index('total_price', 'orders_total_price_idx');
            $table->index('phone', 'orders_phone_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_payment_status_created_at_idx');
            $table->dropIndex('orders_payment_method_idx');
            $table->dropIndex('orders_total_price_idx');
            $table->dropIndex('orders_phone_idx');
        });
    }
};
