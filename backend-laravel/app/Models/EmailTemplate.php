<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'email_templates';

    protected $fillable = [
        'key',
        'name',
        'category',
        'subject',
        'body_html',
        'body_plain',
        'available_placeholders',
        'is_active',
    ];

    protected $casts = [
        'available_placeholders' => 'array',
        'is_active' => 'boolean',
    ];

    public function campaigns()
    {
        return $this->hasMany(EmailCampaign::class, 'template_id');
    }
}
