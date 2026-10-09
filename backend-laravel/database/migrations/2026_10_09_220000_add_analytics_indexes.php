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
            if (! $this->hasIndex('orders', 'orders_created_at_index')) {
                $table->index('created_at', 'orders_created_at_index');
            }
            if (! $this->hasIndex('orders', 'orders_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'orders_status_created_at_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! $this->hasIndex('products', 'products_stock_index')) {
                $table->index('stock', 'products_stock_index');
            }
            if (! $this->hasIndex('products', 'products_status_stock_index')) {
                $table->index(['status', 'stock'], 'products_status_stock_index');
            }
            if (! $this->hasIndex('products', 'products_cost_price_index')) {
                $table->index('cost_price', 'products_cost_price_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if ($this->hasIndex('orders', 'orders_created_at_index')) {
                $table->dropIndex('orders_created_at_index');
            }
            if ($this->hasIndex('orders', 'orders_status_created_at_index')) {
                $table->dropIndex('orders_status_created_at_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if ($this->hasIndex('products', 'products_stock_index')) {
                $table->dropIndex('products_stock_index');
            }
            if ($this->hasIndex('products', 'products_status_stock_index')) {
                $table->dropIndex('products_status_stock_index');
            }
            if ($this->hasIndex('products', 'products_cost_price_index')) {
                $table->dropIndex('products_cost_price_index');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if (($index['name'] ?? '') === $indexName) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            // fallback
        }

        return false;
    }
};
