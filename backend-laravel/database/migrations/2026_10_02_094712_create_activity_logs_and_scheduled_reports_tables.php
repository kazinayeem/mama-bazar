<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Comprehensive Activity Logs Table
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('actor_type', 30)->default('system')->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name', 255)->nullable();
            $table->string('actor_role', 50)->nullable()->index();
            $table->string('event_name', 100)->index();
            $table->string('module', 50)->index();
            $table->string('subject_type', 100)->nullable()->index();
            $table->string('subject_id', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source', 30)->default('admin')->index();
            $table->string('status', 20)->default('success')->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('location', 255)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent()->index();

            // Compound indexes for high performance filtering and analytics
            $table->index(['module', 'occurred_at']);
            $table->index(['status', 'occurred_at']);
            $table->index(['event_name', 'occurred_at']);
            $table->index(['actor_type', 'actor_id']);
        });

        // 2. Scheduled Reports Configuration Table
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 255);
            $table->string('report_type', 50)->default('full');
            $table->string('frequency', 20)->default('weekly');
            $table->text('recipients');
            $table->string('format', 10)->default('pdf');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('last_status', 20)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('activity_logs');
    }
};
