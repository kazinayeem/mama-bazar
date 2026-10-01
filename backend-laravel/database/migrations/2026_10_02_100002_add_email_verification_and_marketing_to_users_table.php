<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'marketing_opt_in')) {
                $table->boolean('marketing_opt_in')->default(true)->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'unsubscribe_token')) {
                $table->string('unsubscribe_token', 64)->nullable()->unique()->after('marketing_opt_in');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'marketing_opt_in', 'unsubscribe_token']);
        });
    }
};
