<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Draft campaigns have no frozen body yet: the template HTML is snapshotted
 * into body_html only when the campaign launches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->longText('body_html')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('email_campaigns')->whereNull('body_html')->update(['body_html' => '']);

        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->longText('body_html')->nullable(false)->change();
        });
    }
};
