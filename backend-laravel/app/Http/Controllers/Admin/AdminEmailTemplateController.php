<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\EmailCampaignService;
use App\Services\EmailDispatcherService;
use App\Services\EmailPreferenceService;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminEmailTemplateController extends Controller
{
    public function index()
    {
        EmailTemplateService::ensureDefaultTemplates();

        $templates = EmailTemplate::orderBy('category')->orderBy('name')->get()->groupBy('category');

        return view('admin.email.templates.index', [
            'templates' => $templates,
            'categories' => EmailTemplateService::CATEGORIES,
        ]);
    }

    public function edit(int $id)
    {
        $template = EmailTemplate::findOrFail($id);

        return view('admin.email.templates.edit', [
            'template' => $template,
            'placeholderGroups' => EmailTemplateService::placeholderGroups(),
            'required' => EmailTemplateService::REQUIRED_PLACEHOLDERS[$template->key] ?? [],
            'alwaysActive' => in_array($template->key, EmailTemplateService::ALWAYS_ACTIVE, true),
            'hasDefault' => isset(EmailTemplateService::defaultTemplates()[$template->key]),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $data = $this->validatedContent($request, $template);

        $template->update([
            'subject' => $data['subject'],
            'body_html' => $data['body_html'],
            'body_plain' => $data['body_plain'],
            'is_active' => in_array($template->key, EmailTemplateService::ALWAYS_ACTIVE, true) || $request->boolean('is_active'),
            'available_placeholders' => EmailTemplateService::extractPlaceholders($data['subject'].' '.$data['body_html']),
        ]);

        return redirect()->route('admin.email.templates.edit', $template->id)->with('success', "Template \"{$template->name}\" saved.");
    }

    /**
     * Render unsaved editor content with sample data (sanitized, no scripts).
     */
    public function preview(Request $request, int $id)
    {
        $template = EmailTemplate::findOrFail($id);

        if ($request->isMethod('post')) {
            $data = $this->validatedContent($request, $template);
            $rendered = EmailTemplateService::renderContent($data['subject'], $data['body_html'], $data['body_plain'], $this->sampleData('preview@example.com'), [
                'marketing' => $template->category === 'marketing',
            ]);
        } else {
            $rendered = EmailTemplateService::renderContent($template->subject, $template->body_html, $template->body_plain, $this->sampleData('preview@example.com'), [
                'marketing' => $template->category === 'marketing',
            ]);
        }

        return response($rendered['html'])
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', "default-src 'none'; img-src * data:; style-src 'unsafe-inline'; frame-ancestors 'self'")
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function sendTest(Request $request, int $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $request->validate(['test_email' => 'required|email:rfc|max:191']);
        $to = strtolower(trim((string) $request->input('test_email')));

        $rendered = EmailTemplateService::renderContent($template->subject, $template->body_html, $template->body_plain, $this->sampleData($to), [
            'marketing' => $template->category === 'marketing',
        ]);

        $result = EmailDispatcherService::send($to, null, '[Test] '.$rendered['subject'], $rendered['html'], $rendered['plain'], 'test', null, null, [], [
            'template_key' => $template->key,
            'user_id' => $request->user()?->id,
        ]);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? "Test of \"{$template->name}\" accepted by SMTP for {$to}." : 'Test failed: '.($result['error'] ?? 'Unknown error')
        );
    }

    public function restore(int $id)
    {
        $template = EmailTemplate::findOrFail($id);

        return EmailTemplateService::restoreDefault($template)
            ? back()->with('success', "\"{$template->name}\" restored to the built-in default.")
            : back()->with('error', 'This template has no built-in default.');
    }

    /**
     * @return array{subject: string, body_html: string, body_plain: string|null}
     */
    protected function validatedContent(Request $request, EmailTemplate $template): array
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string|max:100000',
            'body_plain' => 'nullable|string|max:50000',
        ]);

        $subject = trim(preg_replace('/[\r\n\t]+/', ' ', $data['subject']) ?? '');
        $html = EmailTemplateService::sanitizeTemplateHtml($data['body_html']);
        $plain = isset($data['body_plain']) && trim($data['body_plain']) !== '' ? strip_tags($data['body_plain']) : null;

        $errors = EmailTemplateService::validateTemplateContent($template->key, $subject, $html, $plain);
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return ['subject' => $subject, 'body_html' => $html, 'body_plain' => $plain];
    }

    /**
     * @return array<string, string>
     */
    protected function sampleData(string $email): array
    {
        $sampleItems = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 0 0;border:1px solid #e2e8f0;font-size:12px;">'
            .'<tr style="background:#f1f5f9;color:#475569;font-size:11px;"><th align="left" style="padding:8px 10px;">Item</th><th align="center" style="padding:8px 10px;">Qty</th><th align="right" style="padding:8px 10px;">Unit price</th><th align="right" style="padding:8px 10px;">Total</th></tr>'
            .'<tr><td style="padding:8px 10px;">Organic Mango (1 kg)</td><td align="center" style="padding:8px 10px;">2</td><td align="right" style="padding:8px 10px;">৳180</td><td align="right" style="padding:8px 10px;font-weight:bold;">৳360</td></tr>'
            .'</table>';

        return [
            'customer_name' => 'Karim Uddin',
            'customer_email' => $email,
            'order_number' => 'BS-123456',
            'order_date' => now()->format('d M Y, h:i A'),
            'order_status' => 'Processing',
            'order_subtotal' => '৳360',
            'order_discount' => '৳0',
            'order_shipping' => '৳60',
            'order_total' => '৳420',
            'payment_method' => 'Cash on Delivery',
            'payment_status' => 'Due On Delivery',
            'paid_amount' => '৳420',
            'payment_date' => now()->format('d M Y'),
            'transaction_reference' => 'TRX-SAMPLE',
            'shipping_address' => 'House 14, Road 5, Sector 3, Uttara, Dhaka',
            'customer_phone' => '01700-000000',
            'tracking_url' => url('/track'),
            'invoice_url' => url('/'),
            'review_url' => url('/shop'),
            'courier_name' => 'Sample Courier',
            'tracking_number' => 'TRK-0001',
            'cancellation_reason' => 'Requested by customer',
            'refund_amount' => '৳420',
            'items_table' => $sampleItems,
            'order_summary_table' => '',
            'otp_code' => '000000',
            'otp_expires_minutes' => (string) config('email_system.otp.expires_minutes', 5),
            'reset_url' => url('/reset-password/sample-preview-link'),
            'reset_expires_minutes' => (string) config('email_system.password_reset_expires_minutes', 60),
            'security_event' => 'Your password was changed.',
            'security_time' => now()->format('d M Y, h:i A'),
            'contact_name' => 'Karim Uddin',
            'contact_phone' => '01700-000000',
            'contact_email' => $email,
            'contact_message' => 'Sample contact message.',
            'announcement_title' => 'Fresh arrivals this week',
            'announcement_body' => '<p>Hand-picked seasonal produce, delivered to your doorstep.</p>',
            'cta_text' => 'Shop Now',
            'cta_url' => url('/shop'),
            'coupon_code' => 'SAMPLE10',
            'offer_expires' => now()->addWeek()->format('d M Y'),
            'coupon_block' => EmailCampaignService::couponBlock('SAMPLE10', now()->addWeek()->format('d M Y')),
            'unsubscribe_url' => EmailPreferenceService::unsubscribeUrl($email),
            'preferences_url' => EmailPreferenceService::preferencesUrl($email),
        ];
    }
}
