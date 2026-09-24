<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 100)->unique();
            $table->string('module', 50);
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role_name', 50);
            $table->string('permission_code', 100);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['role_name', 'permission_code'], 'idx_role_permission');
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('permission_code', 100);
            $table->boolean('granted')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'permission_code'], 'idx_user_permission');
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('actor_name', 255);
            $table->string('actor_email', 255)->nullable();
            $table->string('action', 100);
            $table->string('target_type', 50)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->text('details')->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->enum('status', ['success', 'failure'])->default('success');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
            $table->index('actor_id', 'idx_audit_actor');
            $table->index('action', 'idx_audit_action');
            $table->index('created_at', 'idx_audit_created');
        });

        Schema::create('admin_backups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('filename', 255)->unique();
            $table->string('filepath', 500);
            $table->integer('size')->default(0);
            $table->enum('type', ['manual', 'safety_auto'])->default('manual');
            $table->integer('table_count')->default(0);
            $table->integer('record_count')->default(0);
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->index('created_at', 'idx_backup_created');
            $table->index('type', 'idx_backup_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_backups');
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_roles');
    }
};
