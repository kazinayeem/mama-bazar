<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('recipient_email', 255)->index();
                $table->string('recipient_name', 255)->nullable();
                $table->string('subject', 255);
                $table->string('email_type', 50)->default('transactional')->index();
                $table->unsignedInteger('order_id')->nullable()->index();
                $table->unsignedInteger('campaign_id')->nullable()->index();
                $table->enum('status', ['queued', 'sent', 'failed', 'skipped'])->default('queued')->index();
                $table->unsignedTinyInteger('attempts')->default(1);
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
                $table->foreign('campaign_id')->references('id')->on('email_campaigns')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
