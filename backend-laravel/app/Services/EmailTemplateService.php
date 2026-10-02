<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Support\EmailHtmlSanitizer;
use App\Support\PdfBranding;
use Throwable;

/**
 * Safe email template rendering.
 *
 * Templates use a fixed allowlist of {{placeholders}}; no PHP/Blade is ever
 * evaluated from admin input. Values are HTML-escaped unless the placeholder
 * is a system-generated HTML fragment (see RAW_HTML_PLACEHOLDERS).
 */
class EmailTemplateService
{
    public const CATEGORIES = [
        'auth' => 'Account & Security',
        'order' => 'Orders',
        'engagement' => 'Engagement',
        'notification' => 'Internal Notifications',
        'marketing' => 'Marketing Campaigns',
    ];

    /** Placeholders whose values are trusted, system-built (or sanitized) HTML. */
    public const RAW_HTML_PLACEHOLDERS = ['items_table', 'order_summary_table', 'announcement_body', 'product_cards', 'hero_image_block', 'coupon_block'];

    /** Placeholders that must appear for a template to work. */
    public const REQUIRED_PLACEHOLDERS = [
        'account_verification_otp' => ['otp_code'],
        'login_otp' => ['otp_code'],
        'email_change_otp' => ['otp_code'],
        'password_reset' => ['reset_url'],
    ];

    /** Placeholders never allowed in a subject line (subjects are stored in logs). */
    public const SUBJECT_FORBIDDEN = ['otp_code', 'reset_url'];

    /** Security-critical templates that are always sent (built-in default used if the stored one is inactive). */
    public const ALWAYS_ACTIVE = ['account_verification_otp', 'login_otp', 'email_change_otp', 'password_reset', 'member_invitation'];

    protected static bool $defaultsEnsured = false;

    /**
     * @return array<string, array<string, string>>
     */
    public static function placeholderGroups(): array
    {
        return [
            'Business' => [
                'business_name' => 'Business name (Business Information)',
                'support_email' => 'Customer support email',
                'support_phone' => 'Customer helpline number',
                'business_address' => 'Business address',
                'website_url' => 'Storefront URL',
                'support_url' => 'Help / contact page URL',
                'logo_url' => 'Logo image URL',
                'copyright_rendered' => 'Copyright notice',
            ],
            'Customer' => [
                'customer_name' => 'Recipient name',
                'customer_email' => 'Recipient email',
            ],
            'Order' => [
                'order_number' => 'Order number (e.g. BS-XXXXXX)',
                'order_date' => 'Order date',
                'order_status' => 'Current order status',
                'order_subtotal' => 'Items subtotal',
                'order_discount' => 'Discount amount',
                'order_shipping' => 'Shipping charge',
                'order_total' => 'Final amount',
                'payment_method' => 'Payment method',
                'payment_status' => 'Payment status',
                'paid_amount' => 'Verified paid amount',
                'payment_date' => 'Payment date',
                'transaction_reference' => 'Payment transaction reference',
                'shipping_address' => 'Delivery address',
                'courier_tracking_number' => 'Courier tracking number',
                'items_table' => 'Ordered items table (HTML)',
                'order_summary_table' => 'Totals table (HTML)',
                'tracking_url' => 'Secure order tracking link',
                'invoice_url' => 'Secure invoice download link',
                'review_url' => 'Product review link',
            ],
            'Account' => [
                'otp_code' => 'One-time verification code',
                'otp_expires_minutes' => 'OTP validity in minutes',
                'reset_url' => 'Password reset link',
                'reset_expires_minutes' => 'Reset link validity in minutes',
                'security_event' => 'Security event description',
                'security_time' => 'Time of the security event',
                'login_url' => 'Sign-in page URL',
                'member_name' => 'Team member name',
                'member_email' => 'Team member email address',
                'member_role' => 'Assigned administrative role',
                'setup_link' => 'Account setup link URL',
                'expires_hours' => 'Invitation link validity in hours',
            ],
            'Contact' => [
                'contact_name' => 'Contact form name',
                'contact_phone' => 'Contact form phone',
                'contact_email' => 'Contact form email',
                'contact_message' => 'Contact form message',
            ],
            'Campaign' => [
                'announcement_title' => 'Campaign headline',
                'announcement_body' => 'Campaign content (HTML)',
                'cta_text' => 'Button label',
                'cta_url' => 'Button link',
                'hero_image_url' => 'Hero image URL',
                'hero_image_block' => 'Hero image (HTML, empty when no image)',
                'coupon_code' => 'Coupon code',
                'offer_expires' => 'Offer expiry date',
                'coupon_block' => 'Coupon box (HTML, empty when no coupon)',
                'product_cards' => 'Selected products grid (HTML)',
                'unsubscribe_url' => 'Signed unsubscribe link',
                'preferences_url' => 'Email preferences link',
            ],
        ];
    }

    /**
     * @return array<string, string> placeholder name => description
     */
    public static function allowedPlaceholders(): array
    {
        return array_merge(...array_values(self::placeholderGroups()));
    }

    /**
     * Back-compat: "{{name}}" => description.
     */
    public static function placeholderDefinitions(): array
    {
        $out = [];
        foreach (self::allowedPlaceholders() as $name => $label) {
            $out['{{'.$name.'}}'] = $label;
        }

        return $out;
    }

    /**
     * @return array<int, string> placeholder names used in the text
     */
    public static function extractPlaceholders(string $text): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @return array<int, string> placeholder names not in the allowlist
     */
    public static function unknownPlaceholders(string $text): array
    {
        return array_values(array_diff(self::extractPlaceholders($text), array_keys(self::allowedPlaceholders())));
    }

    /**
     * Validate admin-submitted template content.
     *
     * @return array<string, string> field => error message
     */
    public static function validateTemplateContent(string $key, string $subject, string $html, ?string $plain): array
    {
        $errors = [];

        $unknown = self::unknownPlaceholders($subject.' '.$html.' '.$plain);
        if ($unknown) {
            $errors['body_html'] = 'Unknown placeholders: {{'.implode('}}, {{', $unknown).'}}. Only the listed variables are allowed.';
        }

        $inSubject = array_intersect(self::extractPlaceholders($subject), self::SUBJECT_FORBIDDEN);
        if ($inSubject) {
            $errors['subject'] = 'The subject cannot contain {{'.implode('}}, {{', $inSubject).'}} — subjects are stored in email logs.';
        }

        foreach (self::REQUIRED_PLACEHOLDERS[$key] ?? [] as $required) {
            if (! in_array($required, self::extractPlaceholders($html), true)) {
                $errors['body_html'] = "This template must include {{{$required}}} in the HTML body.";
            }
        }

        return $errors;
    }

    public static function sanitizeTemplateHtml(?string $html): string
    {
        return EmailHtmlSanitizer::sanitize($html);
    }

    /**
     * Insert any missing default templates (never overwrites admin edits).
     */
    public static function ensureDefaultTemplates(): void
    {
        if (self::$defaultsEnsured) {
            return;
        }

        try {
            $existing = EmailTemplate::pluck('subject', 'key')->toArray();
        } catch (Throwable $e) {
            return;
        }

        foreach (self::defaultTemplates() as $key => $item) {
            if (! array_key_exists($key, $existing)) {
                EmailTemplate::create([
                    'key' => $key,
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'subject' => $item['subject'],
                    'body_html' => $item['body_html'],
                    'body_plain' => $item['body_plain'] ?? null,
                    'available_placeholders' => self::extractPlaceholders($item['subject'].' '.$item['body_html']),
                    'is_active' => true,
                ]);

                continue;
            }

            // Earlier defaults leaked the OTP into the subject (and therefore the logs).
            if (in_array('otp_code', self::extractPlaceholders((string) $existing[$key]), true)) {
                EmailTemplate::where('key', $key)->update(['subject' => $item['subject']]);
            }
        }

        self::$defaultsEnsured = true;
    }

    public static function resetDefaultsFlag(): void
    {
        self::$defaultsEnsured = false;
    }

    public static function restoreDefault(EmailTemplate $template): bool
    {
        $default = self::defaultTemplates()[$template->key] ?? null;
        if (! $default) {
            return false;
        }

        $template->update([
            'subject' => $default['subject'],
            'body_html' => $default['body_html'],
            'body_plain' => $default['body_plain'] ?? null,
            'category' => $default['category'],
        ]);

        return true;
    }

    public static function categoryFor(string $templateKey): string
    {
        $defaults = self::defaultTemplates();
        if (isset($defaults[$templateKey])) {
            return $defaults[$templateKey]['category'];
        }

        return (string) (EmailTemplate::where('key', $templateKey)->value('category') ?? 'notification');
    }

    public static function isActive(string $templateKey): bool
    {
        self::ensureDefaultTemplates();
        $active = EmailTemplate::where('key', $templateKey)->value('is_active');

        return $active === null ? isset(self::defaultTemplates()[$templateKey]) : (bool) $active;
    }

    /**
     * Render a stored template. Inactive templates fall back to the built-in default.
     *
     * @return array{subject: string, html: string, plain: string, category: string}
     */
    public static function render(string $templateKey, array $data = [], array $options = []): array
    {
        self::ensureDefaultTemplates();

        $template = EmailTemplate::where('key', $templateKey)->where('is_active', true)->first();
        $defaults = self::defaultTemplates();

        if ($template) {
            $subject = $template->subject;
            $html = $template->body_html;
            $plain = $template->body_plain;
            $category = $template->category;
        } elseif (isset($defaults[$templateKey])) {
            $subject = $defaults[$templateKey]['subject'];
            $html = $defaults[$templateKey]['body_html'];
            $plain = $defaults[$templateKey]['body_plain'] ?? null;
            $category = $defaults[$templateKey]['category'];
        } else {
            $subject = 'Notification from {{business_name}}';
            $html = '<p>{{announcement_body}}</p>';
            $plain = null;
            $category = 'notification';
        }

        $options['marketing'] = $options['marketing'] ?? ($category === 'marketing');

        return array_merge(
            self::renderContent($subject, $html, $plain, $data, $options),
            ['category' => $category]
        );
    }

    /**
     * Render raw subject/body content with the allowlisted placeholder system.
     *
     * @return array{subject: string, html: string, plain: string}
     */
    public static function renderContent(string $subject, string $html, ?string $plain, array $data = [], array $options = []): array
    {
        $values = array_merge(self::businessValues(), [
            'customer_name' => 'Valued Customer',
            'otp_expires_minutes' => (string) config('email_system.otp.expires_minutes', 5),
            'cta_text' => 'Shop Now',
            'cta_url' => url('/shop'),
            'login_url' => route('login'),
        ], array_filter($data, fn ($v) => is_scalar($v) || $v === null));

        $allowed = self::allowedPlaceholders();
        $values = array_intersect_key($values, $allowed);

        if (isset($values['announcement_body'])) {
            $values['announcement_body'] = EmailHtmlSanitizer::sanitize((string) $values['announcement_body']);
        }

        $htmlBody = self::replace(EmailHtmlSanitizer::sanitize($html), $values, 'html');
        $renderedSubject = self::replace($subject, $values, 'text');
        $renderedSubject = trim(preg_replace('/[\r\n\t]+/', ' ', $renderedSubject) ?? '');

        $plainBody = $plain !== null && trim($plain) !== ''
            ? self::replace($plain, $values, 'text')
            : self::htmlToText($htmlBody);

        $marketing = (bool) ($options['marketing'] ?? false);
        $preheader = (string) ($options['preheader'] ?? '');

        $footerText = self::plainFooter($values, $marketing);

        return [
            'subject' => $renderedSubject,
            'html' => self::wrapInLayout($htmlBody, $values, $marketing, $preheader),
            'plain' => trim($plainBody)."\n\n".$footerText,
        ];
    }

    /**
     * Replace placeholders. HTML mode escapes values except trusted fragments;
     * text mode converts HTML fragments to text. Unknown placeholders are removed.
     */
    protected static function replace(string $content, array $values, string $mode): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($values, $mode) {
            $name = $m[1];
            if (! array_key_exists($name, $values)) {
                return '';
            }

            $value = (string) ($values[$name] ?? '');

            if (in_array($name, self::RAW_HTML_PLACEHOLDERS, true)) {
                return $mode === 'html' ? $value : self::htmlToText($value);
            }

            return $mode === 'html' ? e($value) : $value;
        }, $content) ?? '';
    }

    /**
     * @return array<string, string>
     */
    public static function businessValues(): array
    {
        $b = BusinessSettingService::all();
        $website = (string) ($b['website_url'] ?: url('/'));

        return [
            'business_name' => (string) ($b['business_name'] ?? 'Mama Bazar'),
            'support_email' => (string) (($b['support_email'] ?? '') ?: ($b['primary_email'] ?? '')),
            'support_phone' => (string) (($b['support_phone'] ?? '') ?: ($b['primary_phone'] ?? '')),
            'business_address' => (string) ($b['formatted_address'] ?? $b['contact_address'] ?? ''),
            'website_url' => $website,
            'support_url' => self::absoluteUrl((string) ($b['support_url'] ?? '/contact')),
            'logo_url' => self::absoluteUrl((string) ($b['logo_url'] ?? '/brandlogo.png')),
            'copyright_rendered' => (string) ($b['copyright_rendered'] ?? ('© '.date('Y').' Mama Bazar')),
        ];
    }

    public static function absoluteUrl(string $path): string
    {
        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return url($path);
    }

    public static function htmlToText(string $html): string
    {
        $text = preg_replace('/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $html) ?? $html;
        $text = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/tr|\/li)\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/t[dh]>/i', "\t", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text) ?? $text;

        return trim(implode("\n", array_map('trim', explode("\n", $text))));
    }

    protected static function plainFooter(array $values, bool $marketing): string
    {
        $lines = [
            '—',
            $values['business_name'].' · '.$values['website_url'],
            'Support: '.$values['support_phone'].' · '.$values['support_email'],
        ];

        if ($marketing && ! empty($values['unsubscribe_url'])) {
            $lines[] = 'Unsubscribe from marketing emails: '.$values['unsubscribe_url'];
        }

        return implode("\n", $lines);
    }

    /**
     * Responsive, table-based layout that renders in common email clients.
     */
    public static function wrapInLayout(string $bodyHtml, array $values, bool $marketing = false, string $preheader = ''): string
    {
        $b = BusinessSettingService::all();
        $e = fn ($v) => e((string) $v);
        $primary = '#0F4D2C';

        $name = $e($values['business_name'] ?? 'Mama Bazar');
        $website = $e($values['website_url'] ?? url('/'));
        $logo = $values['logo_url'] ?? '';
        $header = $logo
            ? '<img src="'.$e($logo).'" alt="'.$name.'" height="44" style="display:block;margin:0 auto;height:44px;width:auto;max-width:200px;border:0;">'
            : '<span style="font-size:24px;font-weight:800;color:#ffffff;">'.$name.'</span>';

        $socials = '';
        foreach (($b['social_links'] ?? []) as $social) {
            $socials .= '<a href="'.$e($social['url']).'" target="_blank" rel="noopener noreferrer" style="color:#475569;text-decoration:none;margin:0 6px;font-weight:600;">'.$e($social['label']).'</a>';
        }
        $socialsRow = $socials ? '<p style="margin:8px 0 0 0;font-size:11px;">'.$socials.'</p>' : '';

        $marketingFooter = '';
        if ($marketing) {
            $links = [];
            if (! empty($values['unsubscribe_url'])) {
                $links[] = '<a href="'.$e($values['unsubscribe_url']).'" style="color:#64748b;text-decoration:underline;">Unsubscribe</a>';
            }
            if (! empty($values['preferences_url'])) {
                $links[] = '<a href="'.$e($values['preferences_url']).'" style="color:#64748b;text-decoration:underline;">Email preferences</a>';
            }
            $marketingFooter = '<p style="margin:10px 0 0 0;font-size:11px;color:#94a3b8;">You are receiving this because you opted in to '.$name.' marketing emails. '
                .implode(' &middot; ', $links).'</p>';
        }

        $address = ! empty($values['business_address'])
            ? '<p style="margin:4px 0 0 0;font-size:11px;color:#94a3b8;">'.$e($values['business_address']).'</p>'
            : '';

        $preheaderHtml = $preheader !== ''
            ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">'.$e($preheader).'</div>'
            : '';

        $bornosoftUrl = $e(PdfBranding::URL);
        $bornosoftName = $e(PdfBranding::MAKER);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<title>{$name}</title>
<style>
    @media only screen and (max-width: 600px) {
        .mb-container { width: 100% !important; border-radius: 0 !important; }
        .mb-content { padding: 24px 18px !important; }
    }
</style>
</head>
<body style="margin:0;padding:0;background-color:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
{$preheaderHtml}
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#f4f7f5;">
    <tr>
        <td align="center" style="padding:24px 8px;">
            <table role="presentation" class="mb-container" width="600" border="0" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;border:1px solid #e2e8f0;">
                <tr>
                    <td align="center" style="background-color:{$primary};padding:22px 28px;border-radius:14px 14px 0 0;">
                        <a href="{$website}" target="_blank" style="text-decoration:none;">{$header}</a>
                    </td>
                </tr>
                <tr>
                    <td class="mb-content" style="padding:32px 34px 26px 34px;font-size:14px;line-height:1.6;color:#334155;">
                        {$bodyHtml}
                    </td>
                </tr>
                <tr>
                    <td align="center" style="background-color:#f1f5f9;padding:22px 28px;font-size:12px;line-height:1.6;color:#64748b;border-top:1px solid #e2e8f0;border-radius:0 0 14px 14px;">
                        <p style="margin:0;font-weight:bold;color:#475569;">
                            Need help? Call <a href="tel:{$e($values['support_phone'] ?? '')}" style="color:{$primary};text-decoration:none;">{$e($values['support_phone'] ?? '')}</a>
                            &middot; <a href="mailto:{$e($values['support_email'] ?? '')}" style="color:{$primary};text-decoration:none;">{$e($values['support_email'] ?? '')}</a>
                        </p>
                        {$address}
                        {$socialsRow}
                        <p style="margin:8px 0 0 0;font-size:11px;color:#94a3b8;">{$e($values['copyright_rendered'] ?? '')}</p>
                        {$marketingFooter}
                        <p style="margin:12px 0 0 0;padding-top:10px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;">
                            Crafted by <a href="{$bornosoftUrl}" target="_blank" rel="noopener noreferrer" style="color:#64748b;font-weight:bold;text-decoration:none;">{$bornosoftName}</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
HTML;
    }

    protected static function button(string $url, string $label, string $color = '#0f4d2c'): string
    {
        return '<table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin:24px auto;"><tr>'
            .'<td align="center" bgcolor="'.$color.'" style="border-radius:28px;">'
            .'<a href="'.$url.'" target="_blank" style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:28px;">'.$label.'</a>'
            .'</td></tr></table>';
    }

    protected static function heading(string $text, string $color = '#0f4d2c'): string
    {
        return '<h2 style="margin:0 0 14px 0;font-size:20px;font-weight:bold;color:'.$color.';">'.$text.'</h2>';
    }

    protected static function codeBlock(): string
    {
        return '<table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin:26px auto 8px auto;"><tr>'
            .'<td align="center" style="padding:14px 34px;background-color:#f0fdf4;border:2px dashed #16a34a;border-radius:12px;">'
            .'<span style="font-size:30px;font-weight:bold;letter-spacing:6px;color:#0f4d2c;font-family:Courier New,monospace;">{{otp_code}}</span>'
            .'</td></tr></table>'
            .'<p style="margin:0 0 18px 0;text-align:center;font-size:12px;color:#64748b;">This code expires in {{otp_expires_minutes}} minutes. Never share it with anyone — our team will never ask for it.</p>';
    }

    protected static function orderFacts(array $rows): string
    {
        $html = '<table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="margin:16px 0;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">';
        foreach ($rows as $i => [$label, $value]) {
            $bg = $i % 2 === 0 ? ' style="background:#f8faf8;"' : '';
            $html .= '<tr'.$bg.'><td><strong>'.$label.'</strong></td><td align="right">'.$value.'</td></tr>';
        }

        return $html.'</table>';
    }

    /**
     * Built-in default templates. Admin edits are stored in email_templates.
     *
     * @return array<string, array{name: string, category: string, subject: string, body_html: string, body_plain?: string}>
     */
    public static function defaultTemplates(): array
    {
        $muted = 'style="font-size:12px;color:#64748b;"';

        return [
            'welcome_email' => [
                'name' => 'Welcome Email',
                'category' => 'auth',
                'subject' => 'Welcome to {{business_name}} — your account is ready',
                'body_html' => self::heading('Welcome, {{customer_name}}!')
                    .'<p>Thank you for joining <strong>{{business_name}}</strong>. Your email is verified and your account is ready for faster checkout and order tracking.</p>'
                    .self::button('{{website_url}}', 'Start Shopping')
                    .'<p '.$muted.'>Questions? Reply to this email or call {{support_phone}}.</p>',
            ],
            'account_verification_otp' => [
                'name' => 'Account Verification OTP',
                'category' => 'auth',
                'subject' => 'Your {{business_name}} verification code',
                'body_html' => self::heading('Verify your email address')
                    .'<p>Hello {{customer_name}}, use this code to verify your email address on {{business_name}}.</p>'
                    .self::codeBlock()
                    .'<p '.$muted.'>If you did not create an account, you can safely ignore this email.</p>',
            ],
            'login_otp' => [
                'name' => 'Login OTP',
                'category' => 'auth',
                'subject' => 'Your {{business_name}} sign-in code',
                'body_html' => self::heading('Your sign-in code')
                    .'<p>Hello {{customer_name}}, use this code to finish signing in to {{business_name}}.</p>'
                    .self::codeBlock()
                    .'<p '.$muted.'>If you did not try to sign in, ignore this email — your account stays secure.</p>',
            ],
            'email_change_otp' => [
                'name' => 'Email Change Verification',
                'category' => 'auth',
                'subject' => 'Confirm your new {{business_name}} email address',
                'body_html' => self::heading('Confirm your new email')
                    .'<p>Hello {{customer_name}}, enter this code to confirm this address as your new {{business_name}} account email.</p>'
                    .self::codeBlock()
                    .'<p '.$muted.'>If you did not request this change, ignore this email.</p>',
            ],
            'password_reset' => [
                'name' => 'Password Reset',
                'category' => 'auth',
                'subject' => 'Reset your {{business_name}} password',
                'body_html' => self::heading('Reset your password')
                    .'<p>Hello {{customer_name}}, we received a request to reset your {{business_name}} password. This link is valid for {{reset_expires_minutes}} minutes and can be used once.</p>'
                    .self::button('{{reset_url}}', 'Reset My Password', '#ea580c')
                    .'<p '.$muted.'>If you did not request a reset, ignore this email — your password will not change.</p>',
            ],
            'account_security_notice' => [
                'name' => 'Account Security Notice',
                'category' => 'auth',
                'subject' => 'Security alert for your {{business_name}} account',
                'body_html' => self::heading('Account security notice')
                    .'<p>Hello {{customer_name}}, this is a confirmation that the following change was made to your account:</p>'
                    .'<p style="padding:12px 16px;background:#f8fafc;border-left:4px solid #0f4d2c;"><strong>{{security_event}}</strong><br><span '.$muted.'>{{security_time}}</span></p>'
                    .'<p>If this was you, no action is needed. If not, reset your password immediately and contact us at {{support_phone}}.</p>',
            ],
            'member_invitation' => [
                'name' => 'Team Member Invitation',
                'category' => 'auth',
                'subject' => 'Welcome to the {{business_name}} Admin Team',
                'body_html' => self::heading('Welcome to {{business_name}}')
                    .'<p>Hello {{member_name}},</p>'
                    .'<p>Welcome to the <strong>{{business_name}}</strong> admin team. Your administrator account has been created successfully.</p>'
                    .self::orderFacts([
                        ['Name', '{{member_name}}'],
                        ['Email', '{{member_email}}'],
                        ['Assigned role', '{{member_role}}'],
                    ])
                    .'<p>To activate your account and create your password, use the secure account setup link below:</p>'
                    .self::button('{{setup_link}}', 'Set Up Password', '#0f4d2c')
                    .'<p '.$muted.'>For security reasons, this link expires after {{expires_hours}} hours and can only be used once.</p>'
                    .'<p '.$muted.'>Please create a strong, unique password before accessing your account.</p>'
                    .'<p '.$muted.'>If you did not expect this invitation, please contact your administrator.</p>',
            ],
            'order_confirmation' => [
                'name' => 'Order Confirmation',
                'category' => 'order',
                'subject' => 'Order confirmed — {{order_number}}',
                'body_html' => self::heading('Thank you for your order!')
                    .'<p>Hello {{customer_name}}, we have received your order <strong>{{order_number}}</strong>.</p>'
                    .self::orderFacts([
                        ['Order number', '{{order_number}}'],
                        ['Order date', '{{order_date}}'],
                        ['Payment', '{{payment_method}} ({{payment_status}})'],
                        ['Delivery address', '{{shipping_address}}'],
                    ])
                    .'{{items_table}}{{order_summary_table}}'
                    .self::button('{{tracking_url}}', 'Track Your Order'),
            ],
            'payment_confirmation' => [
                'name' => 'Payment Confirmation',
                'category' => 'order',
                'subject' => 'Payment received — {{order_number}}',
                'body_html' => self::heading('Payment verified')
                    .'<p>Hello {{customer_name}}, we have verified your payment for order <strong>{{order_number}}</strong>.</p>'
                    .self::orderFacts([
                        ['Paid amount', '{{paid_amount}}'],
                        ['Payment method', '{{payment_method}}'],
                        ['Transaction reference', '{{transaction_reference}}'],
                        ['Payment date', '{{payment_date}}'],
                    ])
                    .self::button('{{invoice_url}}', 'Download Invoice')
                    .'<p '.$muted.'>Track your order anytime: <a href="{{tracking_url}}" style="color:#0f4d2c;">{{tracking_url}}</a></p>',
            ],
            'order_confirmed' => [
                'name' => 'Order Confirmed (Status)',
                'category' => 'order',
                'subject' => 'Order {{order_number}} is confirmed',
                'body_html' => self::heading('Your order is confirmed')
                    .'<p>Hello {{customer_name}}, order <strong>{{order_number}}</strong> has been confirmed and will be prepared for dispatch shortly.</p>'
                    .self::button('{{tracking_url}}', 'View Order Status'),
            ],
            'order_processing' => [
                'name' => 'Order Processing',
                'category' => 'order',
                'subject' => 'Order {{order_number}} is being prepared',
                'body_html' => self::heading('We are preparing your order')
                    .'<p>Hello {{customer_name}}, order <strong>{{order_number}}</strong> is being picked and packed by our team.</p>'
                    .self::button('{{tracking_url}}', 'View Order Status'),
            ],
            'order_shipped' => [
                'name' => 'Order Shipped',
                'category' => 'order',
                'subject' => 'Order {{order_number}} has shipped',
                'body_html' => self::heading('Your order is on the way')
                    .'<p>Good news, {{customer_name}}! Order <strong>{{order_number}}</strong> has been handed to our delivery partner.</p>'
                    .self::orderFacts([
                        ['Delivery address', '{{shipping_address}}'],
                        ['Courier tracking', '{{courier_tracking_number}}'],
                    ])
                    .self::button('{{tracking_url}}', 'Track Delivery', '#ea580c'),
            ],
            'out_for_delivery' => [
                'name' => 'Out for Delivery',
                'category' => 'order',
                'subject' => 'Order {{order_number}} is out for delivery',
                'body_html' => self::heading('Arriving soon')
                    .'<p>Hello {{customer_name}}, order <strong>{{order_number}}</strong> is out for delivery. Please keep your phone reachable.</p>'
                    .'<p>Amount: <strong>{{order_total}}</strong> · Payment: {{payment_method}} ({{payment_status}})</p>'
                    .self::button('{{tracking_url}}', 'Track Delivery'),
            ],
            'order_delivered' => [
                'name' => 'Order Delivered',
                'category' => 'order',
                'subject' => 'Order {{order_number}} delivered',
                'body_html' => self::heading('Your order has been delivered')
                    .'<p>Hello {{customer_name}}, order <strong>{{order_number}}</strong> has been delivered. We hope you enjoy your purchase!</p>'
                    .'<p '.$muted.'>If anything is damaged, wrong or missing, contact us within 7 days at {{support_phone}}.</p>',
            ],
            'order_cancelled' => [
                'name' => 'Order Cancelled',
                'category' => 'order',
                'subject' => 'Order {{order_number}} has been cancelled',
                'body_html' => self::heading('Order cancelled', '#b91c1c')
                    .'<p>Hello {{customer_name}}, order <strong>{{order_number}}</strong> has been cancelled. If you already paid, any refund will be processed according to our refund policy.</p>'
                    .'<p '.$muted.'>Questions? Contact {{support_email}} or {{support_phone}}.</p>',
            ],
            'refund_notification' => [
                'name' => 'Refund Notification',
                'category' => 'order',
                'subject' => 'Refund update for order {{order_number}}',
                'body_html' => self::heading('Refund processed')
                    .'<p>Hello {{customer_name}}, a refund for order <strong>{{order_number}}</strong> ({{order_total}}) has been processed.</p>'
                    .'<p>Depending on your payment provider, funds usually appear within 3–7 business days.</p>',
            ],
            'invoice_email' => [
                'name' => 'Invoice Email',
                'category' => 'order',
                'subject' => 'Your {{business_name}} Invoice — {{order_number}}',
                'body_html' => self::heading('Your invoice for order {{order_number}}')
                    .'<p>Hello {{customer_name}}, thank you for shopping with {{business_name}}. Your invoice for order <strong>{{order_number}}</strong> is attached as a PDF.</p>'
                    .'{{items_table}}{{order_summary_table}}'
                    .self::button('{{tracking_url}}', 'Track Your Order')
                    .'<p '.$muted.'>You can also download the invoice here: <a href="{{invoice_url}}" style="color:#0f4d2c;">Download invoice</a></p>',
            ],
            'review_invitation' => [
                'name' => 'Review Invitation',
                'category' => 'engagement',
                'subject' => 'How was your order {{order_number}}?',
                'body_html' => self::heading('We would love your feedback')
                    .'<p>Hello {{customer_name}}, thank you for shopping with {{business_name}}. How were the products from order <strong>{{order_number}}</strong>?</p>'
                    .self::button('{{review_url}}', 'Write a Review', '#f97316')
                    .'<p '.$muted.'>Your honest review helps other shoppers choose with confidence.</p>',
            ],
            'contact_form_notification' => [
                'name' => 'Contact Form Auto-Reply',
                'category' => 'engagement',
                'subject' => 'We received your message — {{business_name}}',
                'body_html' => self::heading('Thank you for contacting us')
                    .'<p>Hello {{customer_name}}, we have received your message. Our customer care team will get back to you within one business day.</p>'
                    .'<p '.$muted.'>For urgent help call {{support_phone}}.</p>',
            ],
            'contact_form_admin' => [
                'name' => 'Contact Form Admin Alert',
                'category' => 'notification',
                'subject' => 'New contact message from {{contact_name}}',
                'body_html' => self::heading('New contact form message')
                    .self::orderFacts([
                        ['Name', '{{contact_name}}'],
                        ['Phone', '{{contact_phone}}'],
                        ['Email', '{{contact_email}}'],
                    ])
                    .'<p style="white-space:pre-line;padding:12px 16px;background:#f8fafc;border-radius:8px;">{{contact_message}}</p>',
            ],
            'promotional_campaign' => [
                'name' => 'Promotional Campaign (Basic)',
                'category' => 'marketing',
                'subject' => '{{announcement_title}}',
                'body_html' => self::heading('{{announcement_title}}')
                    .'<p>Hello {{customer_name}},</p>'
                    .'<div>{{announcement_body}}</div>'
                    .self::button('{{cta_url}}', '{{cta_text}}'),
            ],
            'campaign_new_arrival' => [
                'name' => 'Campaign — New Arrival',
                'category' => 'marketing',
                'subject' => 'New arrivals at {{business_name}}',
                'body_html' => '{{hero_image_block}}'
                    .self::heading('{{announcement_title}}')
                    .'<p>Hello {{customer_name}},</p><div>{{announcement_body}}</div>'
                    .'{{product_cards}}'
                    .self::button('{{cta_url}}', '{{cta_text}}'),
            ],
            'campaign_promotional_offer' => [
                'name' => 'Campaign — Promotional Offer',
                'category' => 'marketing',
                'subject' => '{{announcement_title}}',
                'body_html' => '{{hero_image_block}}'
                    .self::heading('{{announcement_title}}', '#ea580c')
                    .'<p>Hello {{customer_name}},</p><div>{{announcement_body}}</div>'
                    .'{{coupon_block}}'
                    .'{{product_cards}}'
                    .self::button('{{cta_url}}', '{{cta_text}}', '#ea580c'),
            ],
            'campaign_announcement' => [
                'name' => 'Campaign — Customer Announcement',
                'category' => 'marketing',
                'subject' => '{{announcement_title}}',
                'body_html' => self::heading('{{announcement_title}}')
                    .'<p>Dear {{customer_name}},</p><div>{{announcement_body}}</div>'
                    .self::button('{{cta_url}}', '{{cta_text}}')
                    .'<p '.$muted.'>Need help? Contact {{support_email}} or {{support_phone}}.</p>',
            ],
            'campaign_newsletter' => [
                'name' => 'Campaign — Newsletter',
                'category' => 'marketing',
                'subject' => '{{business_name}} newsletter — {{announcement_title}}',
                'body_html' => self::heading('{{announcement_title}}')
                    .'<p>Hello {{customer_name}}, here is what is new at {{business_name}}.</p>'
                    .'<div>{{announcement_body}}</div>'
                    .'{{product_cards}}'
                    .self::button('{{cta_url}}', '{{cta_text}}'),
            ],
        ];
    }
}
