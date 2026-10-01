<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Addresses that must never receive marketing email (unsubscribed,
 * bounced, complained, or manually blocked). Transactional email is
 * not affected unless the reason is a hard bounce.
 */
class EmailSuppression extends Model
{
    public const REASONS = [
        'unsubscribed' => 'Unsubscribed',
        'bounced' => 'Hard bounce',
        'complaint' => 'Spam complaint',
        'manual' => 'Manually blocked',
    ];

    protected $table = 'email_suppressions';

    protected $fillable = [
        'email',
        'reason',
        'source',
        'note',
    ];

    public static function isSuppressed(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->exists();
    }
}
