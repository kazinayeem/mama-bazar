<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Background Queue
    |--------------------------------------------------------------------------
    |
    | Email jobs are pushed to this connection/queue. "database" works on
    | shared hosting when paired with the scheduler-driven worker below.
    | When set to "sync", transactional emails run after the HTTP response
    | is sent and bulk campaigns are refused.
    |
    */

    'queue_connection' => env('EMAIL_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),

    'queue_name' => env('EMAIL_QUEUE_NAME', 'emails'),

    /*
    | Run `queue:work --stop-when-empty` from the Laravel scheduler every
    | minute (cPanel friendly). Disable when a Supervisor-managed worker
    | already processes the email queue.
    */
    'scheduler_queue_worker' => (bool) env('EMAIL_SCHEDULER_QUEUE_WORKER', true),

    /*
    |--------------------------------------------------------------------------
    | One-Time Passwords
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'length' => (int) env('EMAIL_OTP_LENGTH', 6),
        'expires_minutes' => (int) env('EMAIL_OTP_EXPIRES_MINUTES', 5),
        'max_attempts' => (int) env('EMAIL_OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('EMAIL_OTP_RESEND_COOLDOWN', 60),
        'max_per_hour' => (int) env('EMAIL_OTP_MAX_PER_HOUR', 5),
    ],

    'password_reset_expires_minutes' => (int) env('PASSWORD_RESET_EXPIRES_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Bulk Campaign Delivery
    |--------------------------------------------------------------------------
    */

    'campaigns' => [
        'batch_size' => (int) env('EMAIL_CAMPAIGN_BATCH_SIZE', 25),
        'max_per_minute' => (int) env('EMAIL_CAMPAIGN_MAX_PER_MINUTE', 60),
        'max_attempts_per_recipient' => (int) env('EMAIL_CAMPAIGN_MAX_ATTEMPTS', 3),
        'large_audience_threshold' => (int) env('EMAIL_CAMPAIGN_LARGE_AUDIENCE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs & Automation
    |--------------------------------------------------------------------------
    */

    'log_retention_days' => (int) env('EMAIL_LOG_RETENTION_DAYS', 180),

    'review_invitation_delay_days' => (int) env('EMAIL_REVIEW_INVITATION_DELAY_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | SMTP Settings Security Lock PIN
    |--------------------------------------------------------------------------
    |
    | Admins must enter this PIN to unlock and access SMTP settings.
    | Can be plaintext or a bcrypt/argon2 hash.
    | Defaults to '6969' in development/local.
    |
    */
    'smtp_pin' => env('SMTP_SETTINGS_PIN'),

    'smtp_unlock_duration_minutes' => (int) env('SMTP_SETTINGS_UNLOCK_MINUTES', 15),

];
