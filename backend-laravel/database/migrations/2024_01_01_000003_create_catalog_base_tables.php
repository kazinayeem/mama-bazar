<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('icon', 500)->nullable();
            $table->string('banner', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->text('description')->nullable();
            $table->boolean('featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('homepage_visibility')->default(true);
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->string('display_name', 100)->nullable();
            $table->string('hex', 7);
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sizes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->enum('type', ['clothing', 'shoes', 'general', 'custom'])->default('general');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('banner', 500)->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('homepage_visibility')->default(true);
            $table->integer('sort_order')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('logo', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('contact', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('logo', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('contact', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('logo', 500)->nullable();
            $table->string('banner_image', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('website', 500)->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('homepage_visibility')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->string('image', 500);
            $table->string('image_mobile', 500)->nullable();
            $table->string('image_tablet', 500)->nullable();
            $table->string('link', 500)->nullable();
            $table->enum('position', ['hero', 'banner', 'promo', 'sidebar'])->default('hero');
            $table->string('button_text', 100)->nullable();
            $table->integer('priority')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url', 1000);
            $table->string('public_id', 500)->nullable();
            $table->string('filename', 500);
            $table->string('mime_type', 100);
            $table->integer('size')->default(0);
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->enum('provider', ['cloudinary', 'local'])->default('local');
            $table->string('folder', 200)->default('general');
            $table->string('alt', 255)->nullable();
            $table->unsignedInteger('uploader_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('uploader_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('categories');
    }
};
