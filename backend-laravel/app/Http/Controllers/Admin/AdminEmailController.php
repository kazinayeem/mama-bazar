<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Services\BusinessSettingService;
use App\Services\EmailCampaignService;
use App\Services\EmailDispatcherService;
use App\Services\EmailSettingService;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminEmailController extends Controller
{
    protected function authorizeAdmin(): void
    {
        $role = Auth::user()?->role;
        if (!in_array($role, ['admin', 'manager', 'superadmin'], true)) {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }

    /**
     * Email Management Dashboard.
     */
    public function dashboard()
    {
        $this->authorizeAdmin();

        $stats = [
            'sent_today' => EmailLog::where('status', 'sent')->whereDate('created_at', today())->count(),
            'total_sent' => EmailLog::where('status', 'sent')->count(),
            'total_failed' => EmailLog::where('status', 'failed')->count(),
            'total_queued' => EmailLog::where('status', 'queued')->count(),
            'active_campaigns' => EmailCampaign::whereIn('status', ['queued', 'sending'])->count(),
            'total_campaigns' => EmailCampaign::count(),
            'mail_enabled' => EmailSettingService::isSendingEnabled(),
            'last_status' => EmailSettingService::get('mail_last_status'),
            'last_tested' => EmailSettingService::get('mail_last_tested_at'),
            'last_latency' => EmailSettingService::get('mail_last_latency_ms'),
            'last_error' => EmailSettingService::get('mail_last_error'),
        ];

        $recentLogs = EmailLog::latest()->take(10)->get();
        $recentCampaigns = EmailCampaign::latest()->take(5)->get();

        return view('admin.email.dashboard', compact('stats', 'recentLogs', 'recentCampaigns'));
    }

    /**
     * SMTP Configuration Settings Page.
     */
    public function settings()
    {
        $this->authorizeAdmin();

        $settings = EmailSettingService::all();
        $business = BusinessSettingService::all();

        return view('admin.email.settings', compact('settings', 'business'));
    }

    /**
     * Save SMTP Settings.
     */
    public function updateSettings(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'mail_mailer' => 'required|string|in:smtp,log,sendmail',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|string|in:ssl,tls,starttls,none',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            'mail_reply_to' => 'nullable|email|max:255',
            'mail_timeout' => 'required|integer|min:5|max:120',
            'mail_enabled' => 'nullable|boolean',
        ]);

        $data['mail_enabled'] = $request->boolean('mail_enabled', false) ? 1 : 0;
        if (($data['mail_encryption'] ?? '') === 'none') {
            $data['mail_encryption'] = '';
        }

        EmailSettingService::updateSettings($data);

        return back()->with('success', 'SMTP settings updated successfully.');
    }

    /**
     * Test SMTP Connection.
     */
    public function testConnection()
    {
        $this->authorizeAdmin();

        $result = EmailSettingService::testConnection();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Send Test Email.
     */
    public function sendTestEmail(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate(['test_email' => 'required|email']);
        $recipient = strtolower(trim($request->input('test_email')));

        $business = BusinessSettingService::all();
        $rendered = EmailTemplateService::render('welcome_email', [
            'customer_name' => 'Admin Tester',
            'customer_email' => $recipient,
        ]);

        $subject = "[SMTP Test] Verification from " . ($business['business_name'] ?? 'Mama Bazar');

        $result = EmailDispatcherService::send(
            $recipient,
            'Mama Bazar Admin',
            $subject,
            $rendered['html'],
            $rendered['plain'],
            'test'
        );

        if ($result['success']) {
            return back()->with('success', "Test email delivered successfully to {$recipient}!");
        }

        return back()->with('error', "Failed to send test email: " . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Email Templates Listing.
     */
    public function templates()
    {
        $this->authorizeAdmin();

        EmailTemplateService::ensureDefaultTemplates();
        $templates = EmailTemplate::orderBy('category')->orderBy('name')->get();

        return view('admin.email.templates.index', compact('templates'));
    }

    /**
     * Edit Email Template.
     */
    public function editTemplate($id)
    {
        $this->authorizeAdmin();

        $template = EmailTemplate::findOrFail($id);
        $placeholders = EmailTemplateService::placeholderDefinitions();

        return view('admin.email.templates.edit', compact('template', 'placeholders'));
    }

    /**
     * Update Email Template.
     */
    public function updateTemplate(Request $request, $id)
    {
        $this->authorizeAdmin();

        $template = EmailTemplate::findOrFail($id);

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_plain' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $template->update($data);

        return redirect()->route('admin.email.templates')->with('success', "Template '{$template->name}' updated successfully.");
    }

    /**
     * Preview Email Template with Sample Data.
     */
    public function previewTemplate(Request $request, $id)
    {
        $this->authorizeAdmin();

        $template = EmailTemplate::findOrFail($id);

        $sampleData = [
            'customer_name' => 'Karim Uddin',
            'customer_email' => 'karim@example.com',
            'order_number' => 'BS-982341',
            'order_total' => '৳1,850',
            'order_date' => now()->format('M d, Y · h:i A'),
            'payment_method' => 'CASH ON DELIVERY',
            'payment_status' => 'Pending',
            'shipping_address' => 'House 14, Road 5, Sector 3, Uttara, Dhaka',
            'otp_code' => '582914',
            'otp_expires_minutes' => '5',
            'reset_url' => url('/reset-password?token=sample'),
            'tracking_url' => url('/track?order_id=BS-982341'),
            'invoice_url' => url('/admin/orders/1/invoice'),
            'announcement_title' => 'Fresh Organic Mangoes Just Arrived!',
            'announcement_body' => '<p>Directly harvested from Rajshahi orchards and delivered to your doorstep within 24 hours.</p>',
            'unsubscribe_url' => url('/unsubscribe?sample=true'),
            'items_table' => '<table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #e2e8f0; font-size:12px;"><tr style="background:#f8faf8;"><th>Item</th><th>Qty</th><th>Price</th></tr><tr><td>Organic Mango (1kg)</td><td>2</td><td>৳360</td></tr></table>',
        ];

        $rendered = EmailTemplateService::render($template->key, $sampleData);

        return response($rendered['html'])->header('Content-Type', 'text/html');
    }

    /**
     * Send Template Test Email to Admin.
     */
    public function sendTemplateTest(Request $request, $id)
    {
        $this->authorizeAdmin();

        $template = EmailTemplate::findOrFail($id);
        $request->validate(['test_email' => 'required|email']);
        $recipient = strtolower(trim($request->input('test_email')));

        $rendered = EmailTemplateService::render($template->key, [
            'customer_name' => 'Admin Preview',
            'customer_email' => $recipient,
            'order_number' => 'BS-SAMPLE',
            'order_total' => '৳1,250',
            'otp_code' => '123456',
            'reset_url' => url('/reset-password'),
            'tracking_url' => url('/track'),
            'invoice_url' => url('/'),
            'unsubscribe_url' => url('/unsubscribe'),
        ]);

        $res = EmailDispatcherService::send(
            $recipient,
            'Admin Tester',
            '[Preview] ' . $rendered['subject'],
            $rendered['html'],
            $rendered['plain'],
            'test'
        );

        if ($res['success']) {
            return back()->with('success', "Preview of '{$template->name}' sent to {$recipient}.");
        }

        return back()->with('error', "Failed to send preview: " . ($res['error'] ?? 'Unknown error'));
    }

    /**
     * Email Campaigns Listing.
     */
    public function campaigns()
    {
        $this->authorizeAdmin();

        $campaigns = EmailCampaign::with('creator')->latest()->paginate(15);
        $audiences = EmailCampaignService::audienceOptions();

        return view('admin.email.campaigns.index', compact('campaigns', 'audiences'));
    }

    /**
     * Create Campaign Form.
     */
    public function createCampaign()
    {
        $this->authorizeAdmin();

        EmailTemplateService::ensureDefaultTemplates();
        $templates = EmailTemplate::where('category', 'marketing')->orWhere('key', 'promotional_campaign')->get();
        $audiences = EmailCampaignService::audienceOptions();

        // Calculate counts for audience options
        $counts = [];
        foreach (array_keys($audiences) as $key) {
            $counts[$key] = EmailCampaignService::countAudience($key);
        }

        return view('admin.email.campaigns.create', compact('templates', 'audiences', 'counts'));
    }

    /**
     * Store New Campaign.
     */
    public function storeCampaign(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'audience_filter' => 'required|string|in:' . implode(',', array_keys(EmailCampaignService::audienceOptions())),
            'body_html' => 'required|string',
            'body_plain' => 'nullable|string',
            'action' => 'required|in:save_draft,send_now',
        ]);

        $campaign = EmailCampaign::create([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'sender_name' => $data['sender_name'] ?: EmailSettingService::get('mail_from_name'),
            'sender_email' => $data['sender_email'] ?: EmailSettingService::get('mail_from_address'),
            'audience_filter' => $data['audience_filter'],
            'body_html' => $data['body_html'],
            'body_plain' => $data['body_plain'] ?? strip_tags($data['body_html']),
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        if ($data['action'] === 'send_now') {
            EmailCampaignService::queueCampaign($campaign);
            return redirect()->route('admin.email.campaigns.show', $campaign->id)->with('success', 'Campaign has been queued for background sending.');
        }

        return redirect()->route('admin.email.campaigns.show', $campaign->id)->with('success', 'Campaign draft saved.');
    }

    /**
     * View Campaign Details & Real-Time Delivery Progress.
     */
    public function showCampaign($id)
    {
        $this->authorizeAdmin();

        $campaign = EmailCampaign::with(['creator', 'template'])->findOrFail($id);
        $logs = EmailLog::where('campaign_id', $campaign->id)->latest()->paginate(20);

        return view('admin.email.campaigns.show', compact('campaign', 'logs'));
    }

    /**
     * Trigger Campaign Sending.
     */
    public function sendCampaign($id)
    {
        $this->authorizeAdmin();

        $campaign = EmailCampaign::findOrFail($id);
        EmailCampaignService::queueCampaign($campaign);

        return back()->with('success', "Campaign '{$campaign->name}' is now queued and sending.");
    }

    /**
     * Pause Campaign.
     */
    public function pauseCampaign($id)
    {
        $this->authorizeAdmin();

        $campaign = EmailCampaign::findOrFail($id);
        $campaign->update(['status' => 'paused']);

        return back()->with('success', "Campaign paused.");
    }

    /**
     * Cancel Campaign.
     */
    public function cancelCampaign($id)
    {
        $this->authorizeAdmin();

        $campaign = EmailCampaign::findOrFail($id);
        $campaign->update(['status' => 'cancelled']);

        return back()->with('success', "Campaign cancelled.");
    }

    /**
     * Send Campaign Test Email to Admin.
     */
    public function sendCampaignTest(Request $request, $id)
    {
        $this->authorizeAdmin();

        $campaign = EmailCampaign::findOrFail($id);
        $request->validate(['test_email' => 'required|email']);
        $recipient = strtolower(trim($request->input('test_email')));

        $rendered = EmailTemplateService::render('promotional_campaign', [
            'customer_name' => 'Admin Tester',
            'customer_email' => $recipient,
            'announcement_title' => $campaign->subject,
            'announcement_body' => $campaign->body_html,
            'unsubscribe_url' => url('/unsubscribe?sample=true'),
            'cta_url' => url('/'),
            'cta_text' => 'Shop Now',
        ]);

        $res = EmailDispatcherService::send(
            $recipient,
            'Admin Tester',
            '[Campaign Test] ' . $campaign->subject,
            $rendered['html'],
            $rendered['plain'],
            'test'
        );

        if ($res['success']) {
            return back()->with('success', "Test campaign email sent to {$recipient}.");
        }

        return back()->with('error', "Failed to send test: " . ($res['error'] ?? 'Unknown error'));
    }

    /**
     * Automation Toggles Page.
     */
    public function automation()
    {
        $this->authorizeAdmin();

        $settings = EmailSettingService::all();

        return view('admin.email.automation', compact('settings'));
    }

    /**
     * Update Automation Toggles.
     */
    public function updateAutomation(Request $request)
    {
        $this->authorizeAdmin();

        $toggles = [
            'email_auto_welcome',
            'email_auto_account_otp',
            'email_auto_login_otp',
            'email_auto_password_reset',
            'email_auto_order_created',
            'email_auto_payment_confirmed',
            'email_auto_order_status',
            'email_auto_invoice_pdf',
            'email_auto_review_invitation',
            'email_auto_contact_form',
        ];

        $data = [];
        foreach ($toggles as $key) {
            $data[$key] = $request->boolean($key) ? 1 : 0;
        }

        EmailSettingService::updateSettings($data);

        return back()->with('success', 'Automation triggers updated successfully.');
    }

    /**
     * Email Logs and Monitoring.
     */
    public function logs(Request $request)
    {
        $this->authorizeAdmin();

        $query = EmailLog::with(['order', 'campaign'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $query->where('email_type', $request->input('type'));
        }
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('recipient_email', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%");
            });
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.email.logs', compact('logs'));
    }

    /**
     * Retry sending a failed email log.
     */
    public function retryLog($id)
    {
        $this->authorizeAdmin();

        $log = EmailLog::findOrFail($id);
        $log->increment('attempts');

        $rendered = EmailTemplateService::render('welcome_email', [
            'customer_email' => $log->recipient_email,
            'customer_name' => $log->recipient_name ?: 'Customer',
        ]);

        $res = EmailDispatcherService::send(
            $log->recipient_email,
            $log->recipient_name,
            $log->subject,
            $rendered['html'],
            $rendered['plain'],
            $log->email_type,
            $log->order_id,
            $log->campaign_id
        );

        if ($res['success']) {
            return back()->with('success', "Email to {$log->recipient_email} resent successfully.");
        }

        return back()->with('error', "Retry failed: " . ($res['error'] ?? 'Unknown error'));
    }
}
