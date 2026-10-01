<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive hardening of the email system tables.
 *
 * Existing customer accounts are grandfathered: they keep logging in as
 * before (email_verification_required = false). Marketing opt-in is reset
 * to "not consented" for every account without a recorded consent
 * timestamp, because the previous migration defaulted the flag to true
 * without the customer ever choosing it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_verification_required')) {
                $table->boolean('email_verification_required')->default(false);
            }
            if (! Schema::hasColumn('users', 'marketing_opt_in_at')) {
                $table->timestamp('marketing_opt_in_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'marketing_consent_source')) {
                $table->string('marketing_consent_source', 50)->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('marketing_opt_in')->default(false)->change();
        });

        DB::table('users')->whereNull('marketing_opt_in_at')->update(['marketing_opt_in' => false]);

        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('status', 20)->default('queued')->change();
        });

        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'dedupe_key')) {
                $table->string('dedupe_key', 191)->nullable()->unique();
            }
            if (! Schema::hasColumn('email_logs', 'template_key')) {
                $table->string('template_key', 100)->nullable()->index();
            }
            if (! Schema::hasColumn('email_logs', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->index();
            }
            if (! Schema::hasColumn('email_logs', 'message_id')) {
                $table->string('message_id', 255)->nullable();
            }
            if (! Schema::hasColumn('email_logs', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable();
            }
        });

        Schema::table('email_campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('email_campaigns', 'template_key')) {
                $table->string('template_key', 100)->nullable();
            }
            if (! Schema::hasColumn('email_campaigns', 'content_json')) {
                $table->json('content_json')->nullable();
            }
            if (! Schema::hasColumn('email_campaigns', 'audience_params')) {
                $table->json('audience_params')->nullable();
            }
            if (! Schema::hasColumn('email_campaigns', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }
            if (! Schema::hasColumn('email_campaigns', 'confirmed_by')) {
                $table->unsignedInteger('confirmed_by')->nullable();
            }
            if (! Schema::hasColumn('email_campaigns', 'paused_at')) {
                $table->timestamp('paused_at')->nullable();
            }
            if (! Schema::hasColumn('email_campaigns', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('email_campaigns', 'last_error')) {
                $table->text('last_error')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'template_key', 'content_json', 'audience_params', 'confirmed_at',
                'confirmed_by', 'paused_at', 'cancelled_at', 'last_error',
            ]);
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropIndex(['template_key']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['dedupe_key', 'template_key', 'user_id', 'message_id', 'last_attempt_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verification_required', 'marketing_opt_in_at', 'marketing_consent_source']);
        });
    }
};
