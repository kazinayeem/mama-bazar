<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_campaign_recipients')) {
            Schema::create('email_campaign_recipients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('campaign_id');
                $table->string('email', 191);
                $table->string('name', 255)->nullable();
                $table->unsignedInteger('user_id')->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedBigInteger('email_log_id')->nullable();
                $table->string('error_message', 1000)->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['campaign_id', 'email']);
                $table->foreign('campaign_id')->references('id')->on('email_campaigns')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('email_suppressions')) {
            Schema::create('email_suppressions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('email', 191)->unique();
                $table->string('reason', 30)->default('unsubscribed');
                $table->string('source', 50)->nullable();
                $table->string('note', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_suppressions');
    }
};
