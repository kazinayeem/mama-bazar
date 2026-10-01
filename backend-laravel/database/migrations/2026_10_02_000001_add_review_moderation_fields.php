<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_verified_purchase')->default(false)->after('status');
            $table->boolean('is_featured')->default(false)->after('is_verified_purchase');
            $table->text('admin_note')->nullable()->after('is_featured');
            $table->timestamp('approved_at')->nullable()->after('admin_note');
            $table->unsignedInteger('approved_by')->nullable()->after('approved_at');
            $table->timestamp('updated_at')->nullable()->after('created_at');

            // One review per customer per product (backend-level duplicate guard).
            // MySQL treats NULLs as distinct, so guest reviews (user_id NULL) stay allowed.
            $table->unique(['product_id', 'user_id'], 'reviews_product_user_unique');

            $table->index(['product_id', 'status'], 'reviews_product_status_index');
            $table->index(['status', 'created_at'], 'reviews_status_created_index');
        });

        // Backfill verified-purchase flags from existing order history.
        // Only counts orders that were actually placed (excludes cancelled).
        try {
            DB::statement("
                UPDATE reviews r
                INNER JOIN (
                    SELECT DISTINCT oi.product_id, o.user_id
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    WHERE o.user_id IS NOT NULL
                      AND o.status <> 'cancelled'
                ) bought ON bought.product_id = r.product_id AND bought.user_id = r.user_id
                SET r.is_verified_purchase = 1
                WHERE r.user_id IS NOT NULL
            ");
        } catch (\Throwable $e) {
            // Backfill is best-effort; the flag is recomputed on future submissions.
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_product_user_unique');
            $table->dropIndex('reviews_product_status_index');
            $table->dropIndex('reviews_status_created_index');
            $table->dropColumn([
                'is_verified_purchase',
                'is_featured',
                'admin_note',
                'approved_at',
                'approved_by',
                'updated_at',
            ]);
        });
    }
};
