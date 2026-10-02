<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $table = 'scheduled_reports';

    protected $fillable = [
        'title',
        'report_type',
        'frequency',
        'recipients',
        'format',
        'is_active',
        'last_run_at',
        'next_run_at',
        'last_status',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get array of recipient email addresses.
     *
     * @return array<int, string>
     */
    public function getRecipientEmailsAttribute(): array
    {
        if (empty($this->recipients)) {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $this->recipients);

        return array_values(array_filter($parts, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }
}
