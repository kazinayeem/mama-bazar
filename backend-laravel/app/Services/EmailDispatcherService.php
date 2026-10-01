<?php

namespace App\Services;

use App\Mail\RenderedEmail;
use App\Models\EmailLog;
use Illuminate\Database\QueryException;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Sends one email and records it in email_logs.
 *
 * "sent" means the SMTP server accepted the message. It does not prove
 * inbox delivery; bounces/complaints arrive asynchronously (if at all).
 */
class EmailDispatcherService
{
    /**
     * @param  array<int, array{data: string, name: string, mime?: string}>  $attachments
     * @param  array{
     *     dedupe_key?: string|null,
     *     template_key?: string|null,
     *     user_id?: int|null,
     *     replay?: array|null,
     *     redact?: array<int, string>,
     *     marketing?: bool,
     *     unsubscribe_email?: string|null,
     *     from_name?: string|null,
     *     from_address?: string|null,
     *     log_id?: int|null,
     *     metadata?: array,
     * }  $options
     * @return array{success: bool, skipped?: bool, duplicate?: bool, log_id: int|null, error: string|null}
     */
    public static function send(
        string $to,
        ?string $recipientName,
        string $subject,
        string $htmlContent,
        ?string $plainContent = null,
        string $emailType = 'transactional',
        ?int $orderId = null,
        ?int $campaignId = null,
        array $attachments = [],
        array $options = []
    ): array {
        $to = strtolower(trim($to));
        $redact = array_values(array_filter($options['redact'] ?? [], fn ($v) => is_string($v) && $v !== ''));
        $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject) ?? '');
        $recipientName = $recipientName !== null ? trim(preg_replace('/[\r\n]+/', ' ', $recipientName) ?? '') : null;
        $loggedSubject = self::redact($subject, $redact);

        $log = self::openLog($to, $recipientName, $loggedSubject, $emailType, $orderId, $campaignId, $attachments, $options);
        if ($log === null) {
            return ['success' => false, 'skipped' => true, 'duplicate' => true, 'log_id' => null, 'error' => 'Duplicate email suppressed.'];
        }
        if ($log->wasRecentlyCreated === false && in_array($log->status, ['sent', 'delivered'], true)) {
            return ['success' => false, 'skipped' => true, 'duplicate' => true, 'log_id' => $log->id, 'error' => 'Already sent.'];
        }

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $log->update(['status' => 'failed', 'error_message' => 'Invalid recipient email address.']);

            return ['success' => false, 'log_id' => $log->id, 'error' => 'Invalid recipient email address.'];
        }

        if (! EmailSettingService::isSendingEnabled()) {
            $log->update(['status' => 'skipped', 'error_message' => 'Outgoing email is disabled in Email Settings.']);

            return ['success' => false, 'skipped' => true, 'log_id' => $log->id, 'error' => 'Email delivery is disabled in settings.'];
        }

        try {
            $fromAddress = $options['from_address'] ?? null ?: EmailSettingService::get('mail_from_address');
            $fromName = $options['from_name'] ?? null ?: EmailSettingService::get('mail_from_name', 'Mama Bazar');
            $replyTo = EmailSettingService::get('mail_reply_to');
            $marketing = (bool) ($options['marketing'] ?? false);
            $unsubscribeEmail = $options['unsubscribe_email'] ?? ($marketing ? $to : null);

            $headers = [];
            if ($marketing && $unsubscribeEmail) {
                $headers['List-Unsubscribe'] = '<'.EmailPreferenceService::oneClickUrl($unsubscribeEmail).'>';
                $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
                $headers['Precedence'] = 'bulk';
            }

            $uniqueAttachments = collect($attachments)
                ->filter(fn ($att) => ! empty($att['data']) && ! empty($att['name']))
                ->unique('name')
                ->values()
                ->all();

            $mailable = new RenderedEmail(
                renderedSubject: $subject,
                renderedHtml: $htmlContent,
                renderedText: $plainContent,
                fileAttachments: $uniqueAttachments,
                extraHeaders: $headers,
                fromAddress: $fromAddress,
                fromName: $fromName,
                replyToAddress: ! empty($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : null,
                emailType: $emailType,
            );

            $sent = Mail::mailer(EmailSettingService::mailerName())
                ->to($to, $recipientName ?: null)
                ->send($mailable);

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
                'message_id' => $sent instanceof SentMessage ? mb_substr((string) $sent->getMessageId(), 0, 255) : null,
            ]);

            return ['success' => true, 'log_id' => $log->id, 'error' => null];
        } catch (Throwable $e) {
            $error = self::redact(EmailSettingService::sanitizeError($e->getMessage()), $redact);

            $log->update(['status' => 'failed', 'error_message' => mb_substr($error, 0, 1000)]);

            if ($e instanceof TransportExceptionInterface) {
                EmailSettingService::recordSendFailure($error);
            }

            return ['success' => false, 'log_id' => $log->id, 'error' => $error];
        }
    }

    /**
     * Create the log row, or reuse an existing one (retry / same dedupe key).
     * Returns null when a concurrent request already claimed the dedupe key.
     */
    protected static function openLog(
        string $to,
        ?string $name,
        string $subject,
        string $type,
        ?int $orderId,
        ?int $campaignId,
        array $attachments,
        array $options
    ): ?EmailLog {
        $metadata = array_merge($options['metadata'] ?? [], [
            'attachment_names' => array_values(array_unique(array_column($attachments, 'name'))),
        ]);
        if (! empty($options['replay'])) {
            $metadata['replay'] = $options['replay'];
        }

        $attributes = [
            'recipient_email' => mb_substr($to, 0, 255),
            'recipient_name' => $name ? mb_substr($name, 0, 255) : null,
            'subject' => mb_substr($subject, 0, 255),
            'email_type' => $type,
            'template_key' => $options['template_key'] ?? null,
            'order_id' => $orderId,
            'campaign_id' => $campaignId,
            'user_id' => $options['user_id'] ?? null,
            'last_attempt_at' => now(),
            'metadata' => $metadata,
        ];

        $existing = null;
        if (! empty($options['log_id'])) {
            $existing = EmailLog::find($options['log_id']);
        } elseif (! empty($options['dedupe_key'])) {
            $existing = EmailLog::where('dedupe_key', $options['dedupe_key'])->first();
        }

        if ($existing) {
            if (in_array($existing->status, ['sent', 'delivered'], true)) {
                return $existing;
            }
            $existing->fill($attributes);
            $existing->status = 'queued';
            $existing->attempts = (int) $existing->attempts + 1;
            $existing->save();

            return $existing;
        }

        try {
            return EmailLog::create(array_merge($attributes, [
                'dedupe_key' => $options['dedupe_key'] ?? null,
                'status' => 'queued',
                'attempts' => 1,
            ]));
        } catch (QueryException $e) {
            if (! empty($options['dedupe_key'])) {
                return null;
            }
            throw $e;
        }
    }

    public static function alreadySent(string $dedupeKey): bool
    {
        return EmailLog::where('dedupe_key', $dedupeKey)->whereIn('status', ['sent', 'delivered'])->exists();
    }

    /**
     * @param  array<int, string>  $secrets
     */
    public static function redact(string $text, array $secrets): string
    {
        foreach ($secrets as $secret) {
            $text = str_replace($secret, str_repeat('•', min(8, max(4, strlen($secret)))), $text);
        }

        return $text;
    }

    /**
     * Render a stored template and send it in one step.
     *
     * @return array{success: bool, skipped?: bool, duplicate?: bool, log_id: int|null, error: string|null}
     */
    public static function sendTemplate(
        string $templateKey,
        string $to,
        ?string $name,
        array $data,
        string $emailType,
        array $options = []
    ): array {
        if (! in_array($templateKey, EmailTemplateService::ALWAYS_ACTIVE, true) && ! EmailTemplateService::isActive($templateKey)) {
            $log = EmailLog::create([
                'recipient_email' => strtolower(trim($to)),
                'recipient_name' => $name,
                'subject' => '['.$templateKey.']',
                'email_type' => $emailType,
                'template_key' => $templateKey,
                'order_id' => $options['order_id'] ?? null,
                'campaign_id' => $options['campaign_id'] ?? null,
                'user_id' => $options['user_id'] ?? null,
                'status' => 'skipped',
                'error_message' => 'Template is inactive.',
            ]);

            return ['success' => false, 'skipped' => true, 'log_id' => $log->id, 'error' => 'Template is inactive.'];
        }

        $rendered = EmailTemplateService::render($templateKey, array_merge([
            'customer_name' => $name ?: 'Valued Customer',
            'customer_email' => $to,
        ], $data), ['marketing' => $options['marketing'] ?? null, 'preheader' => $options['preheader'] ?? '']);

        return self::send(
            $to,
            $name,
            $rendered['subject'],
            $rendered['html'],
            $rendered['plain'],
            $emailType,
            $options['order_id'] ?? null,
            $options['campaign_id'] ?? null,
            $options['attachments'] ?? [],
            array_merge(['template_key' => $templateKey], $options)
        );
    }
}
