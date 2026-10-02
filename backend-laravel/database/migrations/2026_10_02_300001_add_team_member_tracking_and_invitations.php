<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend users table with login metadata & invitation attributes
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->string('last_login_location', 255)->nullable()->after('last_login_ip');
            $table->boolean('must_change_password')->default(false)->after('last_login_location');
            $table->string('invitation_token_hash', 255)->nullable()->after('must_change_password');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token_hash');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_sent_at');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_expires_at');
        });

        // 2. Member login histories table
        Schema::create('member_login_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->timestamp('login_at')->index();
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('status', 20)->default('success')->index();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_login_histories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_ip',
                'last_login_location',
                'must_change_password',
                'invitation_token_hash',
                'invitation_sent_at',
                'invitation_expires_at',
                'invitation_accepted_at',
            ]);
        });
    }
};
