<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'route_name',
        'path',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'twitter_card',
        'robots',
        'schema_type',
        'schema_json',
        'is_sitemap_eligible',
        'change_frequency',
        'priority',
    ];

    protected $casts = [
        'is_sitemap_eligible' => 'boolean',
        'priority' => 'float',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
