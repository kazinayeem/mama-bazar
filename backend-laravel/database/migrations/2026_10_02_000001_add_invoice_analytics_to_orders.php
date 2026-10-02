<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Invoice numbering (stable, unique, nullable for backfill)
            $table->string('invoice_number', 30)->nullable()->unique()->after('order_id');
            // Guest tracking token + idempotency (safe duplicate-submit protection)
            $table->string('access_token', 64)->nullable()->after('invoice_number');
            $table->string('idempotency_key', 64)->nullable()->unique()->after('access_token');
            // Privacy-conscious analytics: hashed + truncated IP only, no raw IP
            $table->string('ip_hash', 64)->nullable()->after('admin_notes');
            $table->string('ip_truncated', 45)->nullable()->after('ip_hash');
            $table->text('user_agent')->nullable()->after('ip_truncated');
            $table->string('browser', 50)->nullable()->after('user_agent');
            $table->string('os_platform', 50)->nullable()->after('browser');
            $table->string('device_type', 20)->nullable()->after('os_platform');
            // Attribution (session-persisted, no cross-site tracking)
            $table->string('referrer', 500)->nullable()->after('device_type');
            $table->string('landing_page', 500)->nullable()->after('referrer');
            $table->string('utm_source', 100)->nullable()->after('landing_page');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 150)->nullable()->after('utm_medium');
            $table->string('utm_content', 150)->nullable()->after('utm_campaign');
            $table->string('utm_term', 150)->nullable()->after('utm_content');
            $table->string('order_source', 50)->nullable()->after('utm_term');
            $table->boolean('marketing_consent')->default(false)->after('order_source');
            // Purchase tracking dedup
            $table->string('fb_event_id', 64)->nullable()->after('marketing_consent');
            $table->timestamp('purchase_tracked_at')->nullable()->after('fb_event_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Snapshots so invoices stay accurate when products change later
            $table->string('product_title', 255)->nullable()->after('product_id');
            $table->string('product_sku', 100)->nullable()->after('product_title');
            $table->string('variant_name', 150)->nullable()->after('variant_id');
        });

        // Backfill existing orders: invoice numbers + access tokens
        $orders = DB::table('orders')->select('id', 'order_id')->get();
        foreach ($orders as $o) {
            DB::table('orders')->where('id', $o->id)->update([
                'invoice_number' => 'INV-'.date('Y').'-'.str_pad((string) $o->id, 6, '0', STR_PAD_LEFT),
                'access_token' => bin2hex(random_bytes(16)),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['product_title', 'product_sku', 'variant_name']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number', 'access_token', 'idempotency_key',
                'ip_hash', 'ip_truncated', 'user_agent', 'browser',
                'os_platform', 'device_type', 'referrer', 'landing_page',
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content',
                'utm_term', 'order_source', 'marketing_consent',
                'fb_event_id', 'purchase_tracked_at',
            ]);
        });
    }
};
