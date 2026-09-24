<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->integer('rating');
            $table->string('title', 255)->nullable();
            $table->text('comment');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
        });

        Schema::create('policy_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 150)->unique();
            $table->string('title', 200);
            $table->text('content');
            $table->enum('status', ['published', 'draft'])->default('published');
            $table->integer('last_updated')->default(0);
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150);
            $table->string('phone', 20);
            $table->string('email', 255)->nullable();
            $table->text('message');
            $table->enum('status', ['new', 'read', 'archived'])->default('new');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('marketing_integrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->enum('type', [
                'google_tag_manager',
                'google_analytics',
                'facebook_pixel',
                'facebook_conversion_api',
                'tiktok_pixel',
                'custom_script',
            ]);
            $table->string('pixel_id', 255)->nullable();
            $table->text('script_code')->nullable();
            $table->text('access_token')->nullable();
            $table->string('test_event_code', 100)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('tracking_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('event_name', 100);
            $table->string('platform', 50);
            $table->json('payload')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('newsletters', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 255)->unique();
            $table->string('source', 100)->default('homepage');
            $table->enum('status', ['subscribed', 'unsubscribed'])->default('subscribed');
            $table->timestamp('subscribed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
        Schema::dropIfExists('tracking_logs');
        Schema::dropIfExists('marketing_integrations');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('policy_pages');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('reviews');
    }
};
