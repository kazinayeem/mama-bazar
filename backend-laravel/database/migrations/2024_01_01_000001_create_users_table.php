<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('password', 255);
            $table->rememberToken();
            $table->string('phone', 20)->unique();
            $table->string('email', 255)->nullable();
            $table->string('shipping_area', 100)->nullable();
            $table->text('shipping_address')->nullable();
            $table->enum('role', ['admin', 'manager', 'user'])->default('user');
            $table->string('custom_role', 50)->nullable();
            $table->text('permissions_json')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('reset_token_hash', 255)->nullable();
            $table->timestamp('reset_token_expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('recipient_name', 255);
            $table->string('phone', 20);
            $table->string('alternative_phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('upazila', 100)->nullable();
            $table->string('area', 150)->nullable();
            $table->string('shipping_area', 100);
            $table->text('address');
            $table->string('apartment', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('users');
    }
};
