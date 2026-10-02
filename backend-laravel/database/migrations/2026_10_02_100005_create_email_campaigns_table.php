<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_campaigns')) {
            Schema::create('email_campaigns', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->string('subject', 255);
                $table->string('sender_name', 255)->nullable();
                $table->string('sender_email', 255)->nullable();
                $table->unsignedInteger('template_id')->nullable();
                $table->longText('body_html');
                $table->text('body_plain')->nullable();
                $table->string('audience_filter', 100)->default('consented_customers');
                $table->enum('status', [
                    'draft',
                    'scheduled',
                    'queued',
                    'sending',
                    'paused',
                    'completed',
                    'failed',
                    'cancelled',
                ])->default('draft')->index();
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('queued_count')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('template_id')->references('id')->on('email_templates')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
