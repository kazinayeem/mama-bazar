<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 150)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('route_name', 100)->nullable();
            $table->string('path', 255)->nullable();
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('twitter_title', 255)->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 500)->nullable();
            $table->string('twitter_card', 50)->default('summary_large_image');
            $table->string('robots', 100)->default('index, follow');
            $table->string('schema_type', 100)->nullable();
            $table->longText('schema_json')->nullable();
            $table->boolean('is_sitemap_eligible')->default(true);
            $table->string('change_frequency', 20)->default('daily');
            $table->decimal('priority', 3, 2)->default(0.80);
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('route_name');
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
