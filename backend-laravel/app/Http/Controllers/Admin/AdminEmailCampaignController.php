<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminPermission;
use App\Models\EmailCampaign;
use App\Models\Product;
use App\Services\EmailCampaignService;
use App\Services\EmailDispatcherService;
use App\Services\EmailSettingService;
use App\Support\EmailQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminEmailCampaignController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $campaigns = EmailCampaign::with('creator:id,name')
            ->when($status && isset(EmailCampaign::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.email.campaigns.index', [
            'campaigns' => $campaigns,
            'status' => $status,
            'audiences' => EmailCampaignService::audienceOptions(),
        ]);
    }

    public function create()
    {
        return view('admin.email.campaigns.form', $this->formData(new EmailCampaign([
            'template_key' => 'campaign_promotional_offer',
            'audience_filter' => 'consented_customers',
            'content_json' => ['cta_text' => 'Shop Now', 'cta_url' => url('/shop')],
        ])));
    }

    public function store(Request $request)
    {
        $campaign = EmailCampaign::create(array_merge($this->validated($request), [
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]));

        return redirect()->route('admin.email.campaigns.show', $campaign->id)->with('success', 'Campaign draft saved. Send yourself a test, then review and confirm.');
    }

    public function edit(int $id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        if (! $campaign->isEditable()) {
            return redirect()->route('admin.email.campaigns.show', $id)->with('error', 'Only draft or scheduled campaigns can be edited.');
        }

        return view('admin.email.campaigns.form', $this->formData($campaign));
    }

    public function update(Request $request, int $id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        if (! $campaign->isEditable()) {
            return redirect()->route('admin.email.campaigns.show', $id)->with('error', 'Only draft or scheduled campaigns can be edited.');
        }

        // Any edit returns a scheduled campaign to draft so it must be re-confirmed.
        $campaign->update(array_merge($this->validated($request), [
            'status' => 'draft',
            'scheduled_at' => null,
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]));

        return redirect()->route('admin.email.campaigns.show', $campaign->id)->with('success', 'Campaign updated. It is back in draft and must be confirmed again.');
    }

    public function show(Request $request, int $id)
    {
        $campaign = EmailCampaign::with(['creator:id,name'])->findOrFail($id);
        $isDraft = in_array($campaign->status, ['draft', 'scheduled'], true);

        $recipients = $campaign->recipients()
            ->when($request->query('recipient_status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.email.campaigns.show', [
            'campaign' => $campaign,
            'recipients' => $recipients,
            'audienceLabel' => EmailCampaignService::AUDIENCES[$campaign->audience_filter]['label'] ?? $campaign->audience_filter,
            'audienceCount' => $isDraft ? EmailCampaignService::countAudience((string) $campaign->audience_filter, (array) ($campaign->audience_params ?? [])) : null,
            'problems' => $isDraft ? EmailCampaignService::readinessProblems($campaign) : [],
            'largeThreshold' => (int) config('email_system.campaigns.large_audience_threshold', 100),
            'canSend' => EnsureAdminPermission::allows($request->user(), ['email.campaigns.send']),
            'queueBackground' => EmailQueue::isBackground(),
            'templateLabel' => EmailCampaignService::CAMPAIGN_TEMPLATES[$campaign->template_key] ?? $campaign->template_key,
        ]);
    }

    public function preview(int $id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        $rendered = EmailCampaignService::render($campaign, 'preview@example.com', 'Karim Uddin');

        return response($rendered['html'])
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', "default-src 'none'; img-src * data:; style-src 'unsafe-inline'; frame-ancestors 'self'")
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function audienceCount(Request $request)
    {
        $data = $request->validate([
            'audience_filter' => ['required', Rule::in(array_keys(EmailCampaignService::AUDIENCES))],
            'days' => 'nullable|integer|min:1|max:3650',
            'product_ids' => 'nullable|array|max:50',
            'product_ids.*' => 'integer',
            'emails' => 'nullable|string|max:20000',
        ]);

        return response()->json([
            'count' => EmailCampaignService::countAudience($data['audience_filter'], $this->audienceParams($data)),
        ]);
    }

    public function sendTest(Request $request, int $id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        $request->validate(['test_email' => 'required|email:rfc|max:191']);
        $to = strtolower(trim((string) $request->input('test_email')));

        $rendered = EmailCampaignService::render($campaign, $to, $request->user()->name);
        $result = EmailDispatcherService::send($to, null, '[Test] '.$rendered['subject'], $rendered['html'], $rendered['plain'], 'test', null, $campaign->id, [], [
            'template_key' => $campaign->template_key,
            'user_id' => $request->user()->id,
            'marketing' => true,
            'unsubscribe_email' => $to,
        ]);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? "Test campaign accepted by SMTP for {$to}." : 'Test failed: '.($result['error'] ?? 'Unknown error')
        );
    }

    /**
     * Final confirmation: admin re-enters the recipient count for large
     * audiences, then the campaign is scheduled or started exactly once.
     */
    public function confirm(Request $request, int $id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        $data = $request->validate([
            'send_mode' => 'required|in:now,schedule',
            'scheduled_at' => 'required_if:send_mode,schedule|nullable|date|after:now',
            'confirm_count' => 'nullable|integer',
            'acknowledge' => 'accepted',
        ], [
            'acknowledge.accepted' => 'Please confirm that you have reviewed the content and audience.',
        ]);

        $count = EmailCampaignService::countAudience((string) $campaign->audience_filter, (array) ($campaign->audience_params ?? []));
        if ($count === 0) {
            return back()->with('error', 'No eligible recipients for this audience.');
        }
        if ($count >= (int) config('email_system.campaigns.large_audience_threshold', 100) && (int) ($data['confirm_count'] ?? -1) !== $count) {
            return back()->withErrors(['confirm_count' => "Type the exact recipient count ({$count}) to confirm this large send."]);
        }

        $scheduleAt = $data['send_mode'] === 'schedule' ? Carbon::parse($data['scheduled_at'], config('app.timezone')) : null;
        $result = EmailCampaignService::confirmAndLaunch($campaign, $request->user()->id, $scheduleAt);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function unschedule(int $id)
    {
        $updated = EmailCampaign::whereKey($id)->where('status', 'scheduled')
            ->update(['status' => 'draft', 'scheduled_at' => null, 'confirmed_at' => null, 'confirmed_by' => null]);

        return back()->with($updated ? 'success' : 'error', $updated ? 'Schedule removed; the campaign is back in draft.' : 'Campaign is not scheduled.');
    }

    public function pause(int $id)
    {
        $ok = EmailCampaignService::pause(EmailCampaign::findOrFail($id));

        return back()->with($ok ? 'success' : 'error', $ok ? 'Campaign paused. Remaining recipients stay pending.' : 'Only sending campaigns can be paused.');
    }

    public function resume(int $id)
    {
        $ok = EmailCampaignService::resume(EmailCampaign::findOrFail($id));

        return back()->with($ok ? 'success' : 'error', $ok ? 'Campaign resumed.' : 'Campaign could not be resumed (is it paused, and is the background queue configured?).');
    }

    public function cancel(int $id)
    {
        $ok = EmailCampaignService::cancel(EmailCampaign::findOrFail($id));

        return back()->with($ok ? 'success' : 'error', $ok ? 'Campaign cancelled. Pending recipients will not be emailed.' : 'This campaign can no longer be cancelled.');
    }

    public function retryFailed(int $id)
    {
        $count = EmailCampaignService::retryFailed(EmailCampaign::findOrFail($id));

        return back()->with($count ? 'success' : 'error', $count ? "{$count} failed recipients re-queued." : 'No failed recipients are eligible for retry.');
    }

    public function duplicate(Request $request, int $id)
    {
        $source = EmailCampaign::findOrFail($id);
        $copy = EmailCampaign::create([
            'name' => mb_substr($source->name.' (copy)', 0, 255),
            'subject' => $source->subject,
            'sender_name' => $source->sender_name,
            'template_key' => $source->template_key,
            'content_json' => $source->content_json,
            'audience_filter' => $source->audience_filter,
            'audience_params' => $source->audience_params,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.email.campaigns.edit', $copy->id)->with('success', 'Campaign duplicated as a new draft.');
    }

    public function destroy(int $id)
    {
        $campaign = EmailCampaign::findOrFail($id);
        if (! in_array($campaign->status, ['draft', 'cancelled'], true)) {
            return back()->with('error', 'Only draft or cancelled campaigns can be deleted.');
        }

        $campaign->delete();

        return redirect()->route('admin.email.campaigns.index')->with('success', 'Campaign deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'subject' => ['required', 'string', 'max:191', 'regex:/^[^\r\n]+$/'],
            'sender_name' => ['nullable', 'string', 'max:120', 'regex:/^[^\r\n<>"]*$/'],
            'template_key' => ['required', Rule::in(array_keys(EmailCampaignService::CAMPAIGN_TEMPLATES))],
            'audience_filter' => ['required', Rule::in(array_keys(EmailCampaignService::AUDIENCES))],
            'days' => 'nullable|integer|min:1|max:3650',
            'product_ids' => 'nullable|array|max:50',
            'product_ids.*' => 'integer|exists:products,id',
            'emails' => 'nullable|string|max:20000',
            'announcement_title' => 'nullable|string|max:191',
            'announcement_body' => 'nullable|string|max:50000',
            'preheader' => 'nullable|string|max:191',
            'cta_text' => 'nullable|string|max:60',
            'cta_url' => 'nullable|string|max:500',
            'hero_image_url' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string|max:50',
            'offer_expires' => 'nullable|string|max:60',
            'featured_product_ids' => 'nullable|array|max:6',
            'featured_product_ids.*' => 'integer|exists:products,id',
        ]);

        $content = EmailCampaignService::sanitizeContent(array_merge($data, ['product_ids' => $data['featured_product_ids'] ?? []]));

        return [
            'name' => $data['name'],
            'subject' => trim($data['subject']),
            'sender_name' => ($data['sender_name'] ?? null) ?: null,
            'template_key' => $data['template_key'],
            'audience_filter' => $data['audience_filter'],
            'audience_params' => $this->audienceParams($data),
            'content_json' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function audienceParams(array $data): array
    {
        return match ($data['audience_filter']) {
            'recent_registered', 'inactive_customers' => ['days' => (int) ($data['days'] ?? ($data['audience_filter'] === 'inactive_customers' ? 60 : 30))],
            'product_buyers' => ['product_ids' => array_values(array_map('intval', $data['product_ids'] ?? []))],
            'custom' => ['emails' => implode("\n", EmailCampaignService::parseEmailList((string) ($data['emails'] ?? '')))],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(EmailCampaign $campaign): array
    {
        $selectedIds = array_unique(array_merge(
            (array) ($campaign->audience_params['product_ids'] ?? []),
            (array) ($campaign->content_json['product_ids'] ?? [])
        ));

        return [
            'campaign' => $campaign,
            'audiences' => EmailCampaignService::AUDIENCES,
            'templates' => EmailCampaignService::CAMPAIGN_TEMPLATES,
            'products' => Product::query()
                ->where(fn ($q) => $q->where('status', 'active')->orWhereIn('id', $selectedIds ?: [0]))
                ->orderBy('title')
                ->limit(500)
                ->get(['id', 'title']),
            'defaultSender' => EmailSettingService::get('mail_from_name'),
        ];
    }
}
