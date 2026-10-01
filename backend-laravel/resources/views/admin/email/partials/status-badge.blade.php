@php
    $variant = match($status) {
        'sent', 'delivered', 'completed' => 'success',
        'failed', 'bounced', 'cancelled' => 'destructive',
        'queued', 'scheduled', 'pending', 'processing' => 'warning',
        'sending' => 'default',
        'paused', 'skipped', 'draft' => 'secondary',
        default => 'muted',
    };
    $label = \App\Models\EmailLog::STATUSES[$status] ?? \App\Models\EmailCampaign::STATUSES[$status] ?? ucfirst((string) $status);
@endphp
<x-admin.badge :variant="$variant">{{ $label }}</x-admin.badge>
