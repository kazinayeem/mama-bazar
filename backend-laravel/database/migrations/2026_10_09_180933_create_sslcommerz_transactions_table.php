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
        Schema::create('sslcommerz_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('tran_id', 30)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('mode', 10)->default('sandbox');
            $table->string('status', 20)->default('initiated');
            $table->string('session_key', 100)->nullable();
            $table->string('val_id', 100)->nullable()->unique();
            $table->string('bank_tran_id', 100)->nullable();
            $table->string('card_type', 60)->nullable();
            $table->decimal('validated_amount', 10, 2)->nullable();
            $table->decimal('store_amount', 10, 2)->nullable();
            $table->unsignedTinyInteger('risk_level')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sslcommerz_transactions');
    }
};
