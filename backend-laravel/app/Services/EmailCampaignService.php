<?php

namespace App\Services;

use App\Jobs\SendCampaignBatchJob;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Models\EmailTemplate;
use App\Models\Newsletter;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\EmailHtmlSanitizer;
use App\Support\EmailQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Marketing campaigns: consent-filtered audiences, a materialized
 * recipient list (one row per address = no duplicate sends), atomic
 * launch, rate-limited queue batches and pause/resume/cancel.
 */
class EmailCampaignService
{
    /** @var array<string, array{label: string, description: string}> */
    public const AUDIENCES = [
        'consented_customers' => ['label' => 'All consented customers & subscribers', 'description' => 'Every address that opted in to marketing (accounts, newsletter, checkout consent).'],
        'recent_registered' => ['label' => 'Recently registered customers', 'description' => 'Consented accounts created in the last N days.'],
        'verified_customers' => ['label' => 'Email-verified customers', 'description' => 'Consented accounts with a verified email address.'],
        'order_customers' => ['label' => 'Customers who purchased', 'description' => 'Consented customers with at least one order.'],
        'product_buyers' => ['label' => 'Buyers of selected products', 'description' => 'Consented customers who ordered any of the selected products.'],
        'inactive_customers' => ['label' => 'Inactive customers', 'description' => 'Consented customers whose last order is older than N days.'],
        'newsletter' => ['label' => 'Newsletter subscribers only', 'description' => 'Active newsletter subscribers.'],
        'custom' => ['label' => 'Custom list', 'description' => 'Specific addresses you enter — only those with marketing consent are included.'],
    ];

    public const CAMPAIGN_TEMPLATES = [
        'campaign_new_arrival' => 'New Arrival',
        'campaign_promotional_offer' => 'Promotional Offer',
        'campaign_announcement' => 'Customer Announcement',
        'campaign_newsletter' => 'Newsletter',
        'promotional_campaign' => 'Basic Promotion',
    ];

    public const CONTENT_FIELDS = ['announcement_title', 'announcement_body', 'cta_text', 'cta_url', 'hero_image_url', 'coupon_code', 'offer_expires'];

    /**
     * @return array<string, string>
     */
    public static function audienceOptions(): array
    {
        return array_map(fn ($a) => $a['label'], self::AUDIENCES);
    }

    /**
     * Resolve the consent-filtered, suppression-filtered, de-duplicated audience.
     *
     * @param  array<string, mixed>  $params
     * @return Collection<string, array{email: string, name: string, user_id: int|null}>
     */
    public static function resolveRecipients(string $audience, array $params = []): Collection
    {
        $recipients = collect();
        $add = function (?string $email, ?string $name, ?int $userId) use (&$recipients) {
            $email = strtolower(trim((string) $email));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $recipients->has($email)) {
                return;
            }
            $recipients->put($email, ['email' => $email, 'name' => $name ?: 'Valued Customer', 'user_id' => $userId]);
        };

        $days = max(1, (int) ($params['days'] ?? ($audience === 'inactive_customers' ? 60 : 30)));
        $users = self::consentedUsers();
        $includeGuests = false;
        $includeNewsletter = false;

        switch ($audience) {
            case 'recent_registered':
                $users->where('created_at', '>=', now()->subDays($days));
                break;
            case 'verified_customers':
                $users->whereNotNull('email_verified_at');
                break;
            case 'order_customers':
                $users->whereIn('id', Order::whereNotNull('user_id')->select('user_id'));
                $includeGuests = true;
                break;
            case 'product_buyers':
                $productIds = array_values(array_filter(array_map('intval', (array) ($params['product_ids'] ?? []))));
                if ($productIds === []) {
                    return collect();
                }
                $orderIds = DB::table('order_items')->whereIn('product_id', $productIds)->select('order_id');
                $users->whereIn('id', Order::whereIn('id', $orderIds)->whereNotNull('user_id')->select('user_id'));
                foreach ($users->get(['id', 'name', 'email']) as $user) {
                    $add($user->email, $user->name, $user->id);
                }
                Order::whereIn('id', DB::table('order_items')->whereIn('product_id', $productIds)->select('order_id'))
                    ->whereNull('user_id')->where('marketing_consent', true)->whereNotNull('email')
                    ->orderByDesc('id')->get(['email', 'customer_name'])
                    ->each(fn ($o) => $add($o->email, $o->customer_name, null));

                return self::withoutSuppressed($recipients);
            case 'inactive_customers':
                $users->whereIn('id', Order::whereNotNull('user_id')->select('user_id'))
                    ->whereNotIn('id', Order::whereNotNull('user_id')->where('created_at', '>=', now()->subDays($days))->select('user_id'));
                break;
            case 'newsletter':
                $users = null;
                $includeNewsletter = true;
                break;
            case 'custom':
                $emails = self::parseEmailList((string) ($params['emails'] ?? ''));
                if ($emails === []) {
                    return collect();
                }
                $users->whereIn(DB::raw('LOWER(email)'), $emails);
                foreach ($users->get(['id', 'name', 'email']) as $user) {
                    $add($user->email, $user->name, $user->id);
                }
                Newsletter::where('status', 'subscribed')->whereIn(DB::raw('LOWER(email)'), $emails)->pluck('email')
                    ->each(fn ($e) => $add($e, null, null));
                Order::whereNull('user_id')->where('marketing_consent', true)->whereIn(DB::raw('LOWER(email)'), $emails)
                    ->get(['email', 'customer_name'])->each(fn ($o) => $add($o->email, $o->customer_name, null));

                return self::withoutSuppressed($recipients);
            case 'consented_customers':
            default:
                $includeGuests = true;
                $includeNewsletter = true;
                break;
        }

        if ($users) {
            foreach ($users->get(['id', 'name', 'email']) as $user) {
                $add($user->email, $user->name, $user->id);
            }
        }

        if ($includeGuests) {
            Order::whereNull('user_id')->where('marketing_consent', true)->whereNotNull('email')->where('email', '!=', '')
                ->orderByDesc('id')->get(['email', 'customer_name'])
                ->each(fn ($o) => $add($o->email, $o->customer_name, null));
        }

        if ($includeNewsletter) {
            Newsletter::where('status', 'subscribed')->pluck('email')->each(fn ($e) => $add($e, null, null));
        }

        return self::withoutSuppressed($recipients);
    }

    public static function countAudience(string $audience, array $params = []): int
    {
        return self::resolveRecipients($audience, $params)->count();
    }

    /**
     * @return array<int, string>
     */
    public static function parseEmailList(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', strtolower($raw)) ?: [];

        return array_values(array_unique(array_filter($parts, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
    }

    protected static function consentedUsers(): Builder
    {
        return User::query()
            ->where('marketing_opt_in', true)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '!=', '');
    }

    protected static function withoutSuppressed(Collection $recipients): Collection
    {
        if ($recipients->isEmpty()) {
            return $recipients;
        }

        $suppressed = [];
        foreach ($recipients->keys()->chunk(500) as $chunk) {
            foreach (EmailSuppression::whereIn('email', $chunk->all())->pluck('email') as $email) {
                $suppressed[strtolower($email)] = true;
            }
        }

        return $recipients->reject(fn ($r, $email) => isset($suppressed[$email]));
    }

    /**
     * Validate a campaign before it can be confirmed.
     *
     * @return array<int, string> problems (empty = ready)
     */
    public static function readinessProblems(EmailCampaign $campaign): array
    {
        $problems = [];
        if (! EmailSettingService::isSendingEnabled()) {
            $problems[] = 'Email sending is disabled in SMTP Settings.';
        }
        if (! EmailQueue::isBackground()) {
            $problems[] = 'Bulk campaigns need a background queue. Set EMAIL_QUEUE_CONNECTION=database and enable the cron worker (see deployment guide).';
        }
        if (trim((string) $campaign->subject) === '') {
            $problems[] = 'Subject is required.';
        }
        if (trim(strip_tags((string) ($campaign->content_json['announcement_body'] ?? ''))) === '' && trim((string) ($campaign->content_json['announcement_title'] ?? '')) === '') {
            $problems[] = 'Add a headline or message body.';
        }
        if (! isset(self::CAMPAIGN_TEMPLATES[$campaign->template_key ?? ''])) {
            $problems[] = 'Choose a campaign template.';
        }
        if ($campaign->audience_filter === 'product_buyers' && empty($campaign->audience_params['product_ids'])) {
            $problems[] = 'Select at least one product for the "Buyers of selected products" audience.';
        }

        return $problems;
    }

    /**
     * Confirm and either schedule or start immediately. Atomic: a second
     * confirmation of the same campaign does nothing.
     *
     * @return array{success: bool, message: string}
     */
    public static function confirmAndLaunch(EmailCampaign $campaign, int $userId, ?\DateTimeInterface $scheduleAt = null): array
    {
        $problems = self::readinessProblems($campaign);
        if ($problems) {
            return ['success' => false, 'message' => implode(' ', $problems)];
        }

        if ($scheduleAt && $scheduleAt > now()->addMinute()) {
            $updated = EmailCampaign::where('id', $campaign->id)->where('status', 'draft')->update([
                'status' => 'scheduled',
                'scheduled_at' => $scheduleAt,
                'confirmed_at' => now(),
                'confirmed_by' => $userId,
            ]);

            return $updated
                ? ['success' => true, 'message' => 'Campaign scheduled for '.$scheduleAt->format('d M Y, h:i A').'.']
                : ['success' => false, 'message' => 'This campaign has already been confirmed.'];
        }

        EmailCampaign::where('id', $campaign->id)->whereIn('status', ['draft', 'scheduled'])
            ->update(['confirmed_at' => now(), 'confirmed_by' => $userId]);

        return self::start($campaign->fresh());
    }

    /**
     * Materialize recipients and queue batches. Only one caller can move a
     * campaign out of draft/scheduled, which prevents double launches.
     *
     * @return array{success: bool, message: string}
     */
    public static function start(EmailCampaign $campaign): array
    {
        if (! $campaign->confirmed_at) {
            return ['success' => false, 'message' => 'Campaign must be confirmed before sending.'];
        }

        $claimed = EmailCampaign::where('id', $campaign->id)
            ->whereIn('status', EmailCampaign::LAUNCHABLE_STATUSES)
            ->update(['status' => 'queued', 'started_at' => now(), 'last_error' => null]);
        if ($claimed === 0) {
            return ['success' => false, 'message' => 'This campaign is already sending or finished.'];
        }

        $campaign->refresh();
        $template = self::templateSource((string) $campaign->template_key);
        $campaign->update(['body_html' => $template['body_html']]);

        $recipients = self::resolveRecipients((string) $campaign->audience_filter, (array) ($campaign->audience_params ?? []));
        $now = now();
        foreach ($recipients->values()->chunk(500) as $chunk) {
            EmailCampaignRecipient::insertOrIgnore($chunk->map(fn ($r) => [
                'campaign_id' => $campaign->id,
                'email' => $r['email'],
                'name' => mb_substr((string) $r['name'], 0, 191),
                'user_id' => $r['user_id'],
                'status' => EmailCampaignRecipient::STATUS_PENDING,
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }

        $total = $campaign->recipients()->count();
        $campaign->update([
            'total_recipients' => $total,
            'queued_count' => $total,
            'sent_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
        ]);

        if ($total === 0) {
            $campaign->update(['status' => 'completed', 'completed_at' => now(), 'last_error' => 'No eligible recipients (consent / suppression filters removed everyone).']);

            return ['success' => true, 'message' => 'No eligible recipients — campaign closed without sending.'];
        }

        $campaign->update(['status' => 'sending']);
        $batches = self::dispatchPending($campaign);

        return ['success' => true, 'message' => "Campaign started: {$total} recipients in {$batches} queued batches."];
    }

    /**
     * Queue all pending recipients in rate-limited batches.
     */
    public static function dispatchPending(EmailCampaign $campaign, int $initialDelaySeconds = 0): int
    {
        $batchSize = max(1, (int) config('email_system.campaigns.batch_size', 25));
        $perMinute = max(1, (int) config('email_system.campaigns.max_per_minute', 60));
        $secondsPerBatch = (int) ceil($batchSize / $perMinute * 60);

        $batches = 0;
        $campaign->recipients()
            ->where('status', EmailCampaignRecipient::STATUS_PENDING)
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($rows) use (&$batches, $campaign, $batchSize, $secondsPerBatch, $initialDelaySeconds) {
                foreach ($rows->pluck('id')->chunk($batchSize) as $ids) {
                    $delay = $initialDelaySeconds + $batches * $secondsPerBatch;
                    EmailQueue::dispatch(
                        new SendCampaignBatchJob($campaign->id, $ids->values()->all()),
                        $delay > 0 ? now()->addSeconds($delay) : null
                    );
                    $batches++;
                }
            });

        return $batches;
    }

    public static function pause(EmailCampaign $campaign): bool
    {
        return EmailCampaign::where('id', $campaign->id)->whereIn('status', ['queued', 'sending'])
            ->update(['status' => 'paused', 'paused_at' => now()]) > 0;
    }

    public static function resume(EmailCampaign $campaign): bool
    {
        if (! EmailQueue::isBackground()) {
            return false;
        }

        $resumed = EmailCampaign::where('id', $campaign->id)->where('status', 'paused')
            ->update(['status' => 'sending', 'paused_at' => null, 'last_error' => null]) > 0;

        if ($resumed) {
            self::releaseStale($campaign);
            self::dispatchPending($campaign->fresh());
        }

        return $resumed;
    }

    public static function cancel(EmailCampaign $campaign): bool
    {
        $cancelled = EmailCampaign::where('id', $campaign->id)
            ->whereIn('status', ['draft', 'scheduled', 'queued', 'sending', 'paused'])
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]) > 0;

        if ($cancelled) {
            $campaign->recipients()->whereIn('status', [EmailCampaignRecipient::STATUS_PENDING, EmailCampaignRecipient::STATUS_PROCESSING])
                ->update(['status' => EmailCampaignRecipient::STATUS_SKIPPED, 'error_message' => 'Campaign cancelled']);
            self::syncCounts($campaign->fresh());
        }

        return $cancelled;
    }

    /**
     * Re-queue failed recipients that still have attempts left.
     */
    public static function retryFailed(EmailCampaign $campaign): int
    {
        if (! EmailQueue::isBackground() || ! in_array($campaign->status, ['completed', 'failed', 'sending', 'paused'], true)) {
            return 0;
        }

        $max = max(1, (int) config('email_system.campaigns.max_attempts_per_recipient', 3));
        $count = $campaign->recipients()
            ->where('status', EmailCampaignRecipient::STATUS_FAILED)
            ->where('attempts', '<', $max)
            ->update(['status' => EmailCampaignRecipient::STATUS_PENDING, 'error_message' => null]);

        if ($count > 0) {
            $campaign->update(['status' => 'sending', 'completed_at' => null, 'paused_at' => null]);
            self::dispatchPending($campaign->fresh());
            self::syncCounts($campaign->fresh());
        }

        return $count;
    }

    /** Recipients stuck in "processing" (e.g. a worker crashed) go back to pending. */
    public static function releaseStale(EmailCampaign $campaign): void
    {
        $campaign->recipients()
            ->where('status', EmailCampaignRecipient::STATUS_PROCESSING)
            ->where('updated_at', '<', now()->subMinutes(10))
            ->update(['status' => EmailCampaignRecipient::STATUS_PENDING]);
    }

    /**
     * Recalculate counters from the recipient table and close the campaign
     * when nothing is left to send.
     */
    public static function syncCounts(EmailCampaign $campaign): void
    {
        $counts = $campaign->recipients()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $pending = (int) ($counts[EmailCampaignRecipient::STATUS_PENDING] ?? 0) + (int) ($counts[EmailCampaignRecipient::STATUS_PROCESSING] ?? 0);
        $sent = (int) ($counts[EmailCampaignRecipient::STATUS_SENT] ?? 0);
        $failed = (int) ($counts[EmailCampaignRecipient::STATUS_FAILED] ?? 0);

        $update = [
            'queued_count' => $pending,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'skipped_count' => (int) ($counts[EmailCampaignRecipient::STATUS_SKIPPED] ?? 0),
        ];

        if ($pending === 0 && in_array($campaign->status, ['queued', 'sending'], true)) {
            $update['status'] = ($sent === 0 && $failed > 0) ? 'failed' : 'completed';
            $update['completed_at'] = now();
        }

        $campaign->update($update);
    }

    /**
     * @return array{subject: string, body_html: string}
     */
    public static function templateSource(string $templateKey): array
    {
        EmailTemplateService::ensureDefaultTemplates();
        $stored = EmailTemplate::where('key', $templateKey)->where('is_active', true)->first();
        if ($stored) {
            return ['subject' => (string) $stored->subject, 'body_html' => (string) $stored->body_html];
        }

        $default = EmailTemplateService::defaultTemplates()[$templateKey] ?? EmailTemplateService::defaultTemplates()['promotional_campaign'];

        return ['subject' => $default['subject'], 'body_html' => $default['body_html']];
    }

    /**
     * Render the campaign for one recipient.
     *
     * @return array{subject: string, html: string, plain: string}
     */
    public static function render(EmailCampaign $campaign, string $email, ?string $name): array
    {
        $content = (array) ($campaign->content_json ?? []);
        $body = (string) ($campaign->body_html ?: self::templateSource((string) $campaign->template_key)['body_html']);

        $data = array_merge(
            array_intersect_key($content, array_flip(self::CONTENT_FIELDS)),
            [
                'customer_name' => $name ?: 'Valued Customer',
                'customer_email' => $email,
                'unsubscribe_url' => EmailPreferenceService::unsubscribeUrl($email),
                'preferences_url' => EmailPreferenceService::preferencesUrl($email),
                'hero_image_block' => self::heroBlock((string) ($content['hero_image_url'] ?? '')),
                'coupon_block' => self::couponBlock((string) ($content['coupon_code'] ?? ''), (string) ($content['offer_expires'] ?? '')),
                'product_cards' => self::productCards((array) ($content['product_ids'] ?? [])),
            ]
        );
        if (empty($data['cta_text'])) {
            unset($data['cta_text']);
        }
        if (empty($data['cta_url'])) {
            unset($data['cta_url']);
        }

        return EmailTemplateService::renderContent((string) $campaign->subject, $body, null, $data, [
            'marketing' => true,
            'preheader' => (string) ($content['preheader'] ?? ''),
        ]);
    }

    public static function heroBlock(string $url): string
    {
        $url = trim($url);
        if ($url === '' || ! preg_match('#^https?://#i', $url) && ! str_starts_with($url, '/')) {
            return '';
        }

        $src = e(EmailTemplateService::absoluteUrl($url));

        return '<img src="'.$src.'" alt="" width="532" style="display:block;width:100%;max-width:532px;height:auto;border:0;border-radius:10px;margin:0 0 20px 0;">';
    }

    public static function couponBlock(string $code, string $expires): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }

        $expiry = trim($expires) !== '' ? '<p style="margin:6px 0 0 0;font-size:12px;color:#9a3412;">Valid until '.e($expires).'</p>' : '';

        return '<div style="margin:20px 0;padding:16px;border:2px dashed #ea580c;border-radius:10px;background:#fff7ed;text-align:center;">'
            .'<p style="margin:0;font-size:12px;color:#9a3412;text-transform:uppercase;letter-spacing:1px;">Use code at checkout</p>'
            .'<p style="margin:6px 0 0 0;font-size:24px;font-weight:bold;letter-spacing:3px;color:#c2410c;font-family:Courier New,monospace;">'.e($code).'</p>'
            .$expiry.'</div>';
    }

    /**
     * @param  array<int, mixed>  $productIds
     */
    public static function productCards(array $productIds): string
    {
        $ids = array_slice(array_values(array_filter(array_map('intval', $productIds))), 0, 6);
        if ($ids === []) {
            return '';
        }

        $products = Product::whereIn('id', $ids)->get(['id', 'title', 'slug', 'price', 'sale_price', 'images'])
            ->sortBy(fn ($p) => array_search($p->id, $ids, true))
            ->values();
        if ($products->isEmpty()) {
            return '';
        }

        $cells = $products->map(function (Product $p) {
            $image = collect($p->images ?? [])->map(fn ($i) => is_array($i) ? ($i['url'] ?? $i['src'] ?? null) : $i)->filter()->first();
            $img = $image
                ? '<img src="'.e(EmailTemplateService::absoluteUrl((string) $image)).'" alt="'.e($p->title).'" width="160" style="display:block;width:100%;max-width:160px;height:auto;margin:0 auto 8px auto;border-radius:8px;border:0;">'
                : '';
            $price = $p->sale_price && $p->sale_price > 0 && $p->sale_price < $p->price
                ? '<span style="color:#c2410c;font-weight:bold;">'.e(OrderEmailService::money($p->sale_price)).'</span> <span style="color:#94a3b8;text-decoration:line-through;font-size:11px;">'.e(OrderEmailService::money($p->price)).'</span>'
                : '<span style="color:#0f4d2c;font-weight:bold;">'.e(OrderEmailService::money($p->price)).'</span>';
            $url = e(route('products.show', $p->slug));

            return '<td width="33%" valign="top" style="padding:8px;text-align:center;font-size:12px;">'
                .'<a href="'.$url.'" style="text-decoration:none;color:#1e293b;">'.$img
                .'<span style="display:block;font-weight:bold;margin-bottom:4px;">'.e(mb_strimwidth((string) $p->title, 0, 60, '…')).'</span></a>'
                .$price.'</td>';
        });

        $rows = $cells->chunk(3)->map(fn ($row) => '<tr>'.$row->implode('').str_repeat('<td width="33%"></td>', 3 - $row->count()).'</tr>')->implode('');

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">'.$rows.'</table>';
    }

    /**
     * Normalize admin input for campaign content.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function sanitizeContent(array $input): array
    {
        $content = [];
        foreach (['announcement_title', 'cta_text', 'coupon_code', 'offer_expires', 'preheader'] as $field) {
            $content[$field] = trim(strip_tags((string) ($input[$field] ?? '')));
        }
        $content['announcement_body'] = EmailHtmlSanitizer::sanitize((string) ($input['announcement_body'] ?? ''));
        foreach (['cta_url', 'hero_image_url'] as $field) {
            $url = trim((string) ($input[$field] ?? ''));
            $content[$field] = $url !== '' && (preg_match('#^https?://#i', $url) || str_starts_with($url, '/')) ? $url : '';
        }
        $content['product_ids'] = array_slice(array_values(array_unique(array_filter(array_map('intval', (array) ($input['product_ids'] ?? []))))), 0, 6);

        return $content;
    }
}
