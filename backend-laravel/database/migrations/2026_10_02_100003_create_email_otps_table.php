<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('email_otps')) {
            Schema::create('email_otps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('email', 255)->index();
                $table->string('otp_hash', 255);
                $table->string('type', 50)->default('account_verification')->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedTinyInteger('max_attempts')->default(5);
                $table->timestamp('expires_at')->index();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
