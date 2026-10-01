<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Str;

class EmailTemplateService
{
    /**
     * Standard allowed placeholder definitions.
     */
    public static function placeholderDefinitions(): array
    {
        return [
            '{{customer_name}}' => 'Recipient / Customer Name',
            '{{customer_email}}' => 'Customer Email Address',
            '{{order_number}}' => 'Order Reference (e.g. BS-XXXXXX)',
            '{{order_total}}' => 'Total Amount (e.g. ৳1,450)',
            '{{order_status}}' => 'Order Status (e.g. Confirmed, Shipped)',
            '{{order_date}}' => 'Date order was placed',
            '{{payment_method}}' => 'Payment Method (COD, bKash, etc.)',
            '{{payment_status}}' => 'Payment Status (Paid, Pending)',
            '{{shipping_address}}' => 'Delivery Address',
            '{{items_table}}' => 'Formatted HTML table of ordered items',
            '{{tracking_url}}' => 'Public order tracking page URL',
            '{{invoice_url}}' => 'Invoice download URL',
            '{{otp_code}}' => '6-digit OTP Verification Code',
            '{{otp_expires_minutes}}' => 'OTP validity duration in minutes',
            '{{reset_url}}' => 'Password reset action URL',
            '{{business_name}}' => 'Configured Business Name',
            '{{support_email}}' => 'Official Customer Support Email',
            '{{support_phone}}' => 'Customer Helpline Phone Number',
            '{{support_url}}' => 'Customer Support Help Center URL',
            '{{website_url}}' => 'Storefront Website URL',
            '{{copyright_rendered}}' => 'Dynamic Copyright Notice',
            '{{unsubscribe_url}}' => 'Signed Marketing Unsubscribe Link',
            '{{announcement_title}}' => 'Campaign / Announcement Title',
            '{{announcement_body}}' => 'Campaign / Announcement Message',
            '{{cta_url}}' => 'Action Button Destination URL',
            '{{cta_text}}' => 'Action Button Label',
        ];
    }

    /**
     * Seed or restore all default system templates.
     */
    public static function ensureDefaultTemplates(): void
    {
        $templates = self::defaultTemplates();

        foreach ($templates as $key => $item) {
            EmailTemplate::firstOrCreate(
                ['key' => $key],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'subject' => $item['subject'],
                    'body_html' => $item['body_html'],
                    'body_plain' => $item['body_plain'] ?? null,
                    'available_placeholders' => $item['placeholders'] ?? array_keys(self::placeholderDefinitions()),
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Render subject, HTML body, and plain-text body with variables.
     */
    public static function render(string $templateKey, array $data = []): array
    {
        self::ensureDefaultTemplates();

        $template = EmailTemplate::where('key', $templateKey)->where('is_active', true)->first();

        // Fallback to default definition if not found in DB or inactive
        if (!$template) {
            $defaults = self::defaultTemplates();
            if (isset($defaults[$templateKey])) {
                $rawSubject = $defaults[$templateKey]['subject'];
                $rawHtml = $defaults[$templateKey]['body_html'];
                $rawPlain = $defaults[$templateKey]['body_plain'] ?? '';
            } else {
                $rawSubject = "Notification from {{business_name}}";
                $rawHtml = "<p>{{announcement_body}}</p>";
                $rawPlain = "{{announcement_body}}";
            }
        } else {
            $rawSubject = $template->subject;
            $rawHtml = $template->body_html;
            $rawPlain = $template->body_plain ?: strip_tags($template->body_html);
        }

        // Merge dynamic business settings
        $business = BusinessSettingService::all();
        $mergedData = array_merge([
            'business_name' => $business['business_name'] ?? 'Mama Bazar',
            'support_email' => $business['support_email'] ?? 'support@mamabazar.com',
            'support_phone' => $business['primary_phone'] ?? '01700-000000',
            'support_url' => $business['support_url'] ?? (url('/contact')),
            'website_url' => $business['website_url'] ?? url('/'),
            'copyright_rendered' => $business['copyright_rendered'] ?? ('© ' . date('Y') . ' Mama Bazar. All rights reserved.'),
            'otp_expires_minutes' => '5',
            'customer_name' => 'Valued Customer',
            'order_status' => 'Confirmed',
            'cta_text' => 'Visit Mama Bazar',
            'cta_url' => url('/'),
        ], $data);

        // Build replacement map
        $replacements = [];
        foreach ($mergedData as $key => $val) {
            if (is_scalar($val) || is_null($val)) {
                $replacements['{{' . $key . '}}'] = (string) ($val ?? '');
            }
        }

        $subject = str_replace(array_keys($replacements), array_values($replacements), $rawSubject);
        $contentHtml = str_replace(array_keys($replacements), array_values($replacements), $rawHtml);
        $contentPlain = str_replace(array_keys($replacements), array_values($replacements), $rawPlain);

        // Wrap into responsive email container
        $wrappedHtml = self::wrapInLayout($contentHtml, $mergedData);

        return [
            'subject' => $subject,
            'html' => $wrappedHtml,
            'plain' => $contentPlain,
        ];
    }

    /**
     * Wrap body HTML inside a universal responsive email layout.
     */
    public static function wrapInLayout(string $bodyHtml, array $data): string
    {
        $business = BusinessSettingService::all();
        $businessName = htmlspecialchars($data['business_name'] ?? $business['business_name'] ?? 'Mama Bazar');
        $logoUrl = $business['logo_url'] ?: url('/brandlogo.png');
        $primaryColor = '#0F4D2C';
        $accentColor = '#F97316';
        $unsubscribeUrl = $data['unsubscribe_url'] ?? null;
        $copyright = $data['copyright_rendered'] ?? $business['copyright_rendered'] ?? ('© ' . date('Y') . ' ' . $businessName);

        $unsubscribeHtml = '';
        if ($unsubscribeUrl) {
            $unsubscribeHtml = '<p style="margin: 8px 0 0 0; font-size: 11px; color: #94a3b8;">'
                . 'You received this email because you opted into marketing updates. '
                . '<a href="' . htmlspecialchars($unsubscribeUrl) . '" style="color: #64748b; text-decoration: underline;">Unsubscribe safely</a>'
                . '</p>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$businessName}</title>
<style>
    body { margin: 0; padding: 0; background-color: #f8faf8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased; }
    table { border-collapse: collapse; }
    img { max-width: 100%; height: auto; }
    @media only screen and (max-width: 600px) {
        .container { width: 100% !important; border-radius: 0 !important; }
        .content { padding: 24px 20px !important; }
    }
</style>
</head>
<body style="margin:0; padding:24px 0; background-color:#f8faf8;">
<table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#f8faf8;">
    <tr>
        <td align="center">
            <table class="container" width="600" border="0" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 4px 12px rgba(15,77,44,0.04);">
                <!-- Header -->
                <tr>
                    <td style="background-color:{$primaryColor}; padding:24px 32px; text-align:center;">
                        <a href="{$data['website_url']}" target="_blank" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                            <span style="font-size:24px; font-weight:800; color:#ffffff; letter-spacing:-0.5px;">
                                {$businessName}
                            </span>
                        </a>
                    </td>
                </tr>

                <!-- Main Content Body -->
                <tr>
                    <td class="content" style="padding:36px 36px 28px 36px; font-size:14px; line-height:1.6; color:#334155;">
                        {$bodyHtml}
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background-color:#f1f5f9; padding:24px 32px; text-align:center; font-size:12px; line-height:1.6; color:#64748b; border-top:1px solid #e2e8f0;">
                        <p style="margin:0 0 6px 0; font-weight:600; color:#475569;">
                            Need help? Contact our Helpline: <a href="tel:{$data['support_phone']}" style="color:{$primaryColor}; font-weight:bold; text-decoration:none;">{$data['support_phone']}</a> &middot;
                            Email: <a href="mailto:{$data['support_email']}" style="color:{$primaryColor}; font-weight:bold; text-decoration:none;">{$data['support_email']}</a>
                        </p>
                        <p style="margin:4px 0 0 0; font-size:11px; color:#94a3b8;">
                            {$copyright}
                        </p>
                        {$unsubscribeHtml}
                        <p style="margin:12px 0 0 0; padding-top:10px; border-top:1px solid #e2e8f0; font-size:10px; color:#94a3b8;">
                            Crafted by <a href="https://bornosoft.bd/" target="_blank" rel="noopener noreferrer" style="color:#64748b; font-weight:bold; text-decoration:none;">Bornosoft</a> &middot; bornosoft.bd
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

    /**
     * All 16 required default templates.
     */
    public static function defaultTemplates(): array
    {
        return [
            // 1. Welcome Email
            'welcome_email' => [
                'name' => 'Welcome Email',
                'category' => 'auth',
                'subject' => 'Welcome to {{business_name}} — Your Account is Ready!',
                'body_html' => <<<HTML
<h2 style="margin:0 0 16px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Welcome, {{customer_name}}! 🎉</h2>
<p>Thank you for joining <strong>{{business_name}}</strong>. We are thrilled to deliver fresh groceries, authentic essentials, and daily necessities directly to your doorstep.</p>
<div style="margin:24px 0; text-align:center;">
    <a href="{{website_url}}" style="display:inline-block; padding:12px 28px; background-color:#0f4d2c; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:30px; box-shadow:0 2px 6px rgba(15,77,44,0.2);">Start Shopping Now &rarr;</a>
</div>
<p style="font-size:13px; color:#64748b;">If you have any questions or need assistance, feel free to reply to this email or call our helpline at {{support_phone}}.</p>
HTML
            ],

            // 2. Account Verification OTP
            'account_verification_otp' => [
                'name' => 'Account Verification OTP',
                'category' => 'auth',
                'subject' => '{{otp_code}} is your {{business_name}} verification code',
                'body_html' => <<<HTML
<h2 style="margin:0 0 16px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Verify Your Email Address</h2>
<p>Hello {{customer_name}}, please use the verification code below to verify your email address on {{business_name}}.</p>
<div style="margin:28px 0; text-align:center;">
    <div style="display:inline-block; padding:16px 36px; background-color:#f0fdf4; border:2px dashed #16a34a; border-radius:12px;">
        <span style="font-size:32px; font-weight:900; letter-spacing:6px; color:#0f4d2c; font-family:monospace;">{{otp_code}}</span>
    </div>
    <p style="margin:8px 0 0 0; font-size:12px; color:#64748b;">This code will expire in {{otp_expires_minutes}} minutes.</p>
</div>
<p style="font-size:13px; color:#64748b;">If you did not request this verification code, please ignore this email.</p>
HTML
            ],

            // 3. Login OTP
            'login_otp' => [
                'name' => 'Login OTP',
                'category' => 'auth',
                'subject' => '{{otp_code}} is your {{business_name}} login code',
                'body_html' => <<<HTML
<h2 style="margin:0 0 16px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Your Secure Login Code</h2>
<p>Hello {{customer_name}}, use the 6-digit OTP below to log in to your account.</p>
<div style="margin:28px 0; text-align:center;">
    <div style="display:inline-block; padding:16px 36px; background-color:#f0fdf4; border:2px dashed #16a34a; border-radius:12px;">
        <span style="font-size:32px; font-weight:900; letter-spacing:6px; color:#0f4d2c; font-family:monospace;">{{otp_code}}</span>
    </div>
    <p style="margin:8px 0 0 0; font-size:12px; color:#64748b;">Expires in {{otp_expires_minutes}} minutes. Never share this code with anyone.</p>
</div>
HTML
            ],

            // 4. Password Reset
            'password_reset' => [
                'name' => 'Password Reset',
                'category' => 'auth',
                'subject' => 'Reset your {{business_name}} password',
                'body_html' => <<<HTML
<h2 style="margin:0 0 16px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Reset Your Password</h2>
<p>Hello {{customer_name}}, we received a request to reset the password for your {{business_name}} account.</p>
<div style="margin:24px 0; text-align:center;">
    <a href="{{reset_url}}" style="display:inline-block; padding:12px 28px; background-color:#ea580c; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:30px; box-shadow:0 2px 6px rgba(234,88,12,0.2);">Reset My Password &rarr;</a>
</div>
<p style="font-size:12px; color:#64748b;">If the button does not work, copy and paste this link into your browser:<br><a href="{{reset_url}}" style="color:#0f4d2c; word-break:break-all;">{{reset_url}}</a></p>
<p style="font-size:12px; color:#94a3b8;">If you did not request a password reset, no further action is required.</p>
HTML
            ],

            // 5. Order Confirmation
            'order_confirmation' => [
                'name' => 'Order Confirmation',
                'category' => 'order',
                'subject' => 'Order Confirmed — {{order_number}} ({{order_total}})',
                'body_html' => <<<HTML
<h2 style="margin:0 0 8px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Thank You for Your Order! 🛍️</h2>
<p style="margin:0 0 20px 0;">Hello {{customer_name}}, your order <strong>{{order_number}}</strong> has been placed successfully and is currently being processed by our team.</p>

<table width="100%" cellpadding="8" cellspacing="0" style="margin:16px 0; border:1px solid #e2e8f0; border-radius:8px; font-size:13px;">
    <tr style="background:#f8faf8;"><td><strong>Order ID:</strong></td><td align="right">{{order_number}}</td></tr>
    <tr><td><strong>Order Date:</strong></td><td align="right">{{order_date}}</td></tr>
    <tr style="background:#f8faf8;"><td><strong>Total Amount:</strong></td><td align="right" style="font-size:16px; font-weight:800; color:#0f4d2c;">{{order_total}}</td></tr>
    <tr><td><strong>Payment Method:</strong></td><td align="right">{{payment_method}} ({{payment_status}})</td></tr>
    <tr style="background:#f8faf8;"><td><strong>Delivery Address:</strong></td><td align="right">{{shipping_address}}</td></tr>
</table>

{{items_table}}

<div style="margin:24px 0; text-align:center;">
    <a href="{{tracking_url}}" style="display:inline-block; padding:12px 28px; background-color:#0f4d2c; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:30px;">Track Your Order Live &rarr;</a>
</div>
HTML
            ],

            // 6. Payment Confirmation
            'payment_confirmation' => [
                'name' => 'Payment Confirmation',
                'category' => 'order',
                'subject' => 'Payment Received for Order {{order_number}}',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Payment Verified &amp; Received ✅</h2>
<p>Hello {{customer_name}}, we have successfully verified your payment of <strong>{{order_total}}</strong> for Order <strong>{{order_number}}</strong>.</p>
<p>Your items are now moving to our dispatch station. You can view your invoice anytime using the link below.</p>
<div style="margin:24px 0; text-align:center;">
    <a href="{{invoice_url}}" style="display:inline-block; padding:12px 28px; background-color:#0f4d2c; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:30px;">Download Official Invoice &rarr;</a>
</div>
HTML
            ],

            // 7. Order Processing
            'order_processing' => [
                'name' => 'Order Processing',
                'category' => 'order',
                'subject' => 'Order {{order_number}} is Now Being Prepared',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">We are Packaging Your Order 📦</h2>
<p>Hello {{customer_name}}, your order <strong>{{order_number}}</strong> is currently being inspected, packaged, and prepared for dispatch by our fulfillment specialists.</p>
<div style="margin:20px 0; text-align:center;">
    <a href="{{tracking_url}}" style="display:inline-block; padding:10px 24px; background-color:#0f4d2c; color:#ffffff; font-size:12px; font-weight:700; text-decoration:none; border-radius:24px;">View Order Status &rarr;</a>
</div>
HTML
            ],

            // 8. Order Shipped
            'order_shipped' => [
                'name' => 'Order Shipped',
                'category' => 'order',
                'subject' => 'Order {{order_number}} has been Shipped 🚚',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Your Order is On the Way! 🚚</h2>
<p>Great news, {{customer_name}}! Order <strong>{{order_number}}</strong> has been dispatched with our delivery partner and is en route to your shipping address.</p>
<p><strong>Delivery Address:</strong> {{shipping_address}}</p>
<div style="margin:24px 0; text-align:center;">
    <a href="{{tracking_url}}" style="display:inline-block; padding:12px 28px; background-color:#ea580c; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:30px;">Track Delivery Live &rarr;</a>
</div>
HTML
            ],

            // 9. Out for Delivery
            'out_for_delivery' => [
                'name' => 'Out for Delivery',
                'category' => 'order',
                'subject' => 'Order {{order_number}} is Out for Delivery Today',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Arriving Today! 🛵</h2>
<p>Hello {{customer_name}}, our delivery courier is in your area and will deliver Order <strong>{{order_number}}</strong> today.</p>
<p>Please ensure your contact number is reachable. Total payable upon delivery: <strong>{{order_total}}</strong> (if COD).</p>
HTML
            ],

            // 10. Order Delivered
            'order_delivered' => [
                'name' => 'Order Delivered',
                'category' => 'order',
                'subject' => 'Order {{order_number}} Successfully Delivered 🎉',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Your Order Has Been Delivered! 🎉</h2>
<p>Hello {{customer_name}}, Order <strong>{{order_number}}</strong> has been successfully handed over. We hope you enjoy your purchase!</p>
<p>If anything was damaged, defective, or missing, please contact our support team within 7 days for a hassle-free return or replacement.</p>
HTML
            ],

            // 11. Order Cancelled
            'order_cancelled' => [
                'name' => 'Order Cancelled',
                'category' => 'order',
                'subject' => 'Order {{order_number}} Has Been Cancelled',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#b91c1c;">Order {{order_number}} Cancelled</h2>
<p>Hello {{customer_name}}, order <strong>{{order_number}}</strong> has been cancelled. If any payment was deducted, a refund will be processed in accordance with our refund policy.</p>
<p>For questions or assistance, please reach our customer support team at {{support_email}}.</p>
HTML
            ],

            // 12. Refund Notification
            'refund_notification' => [
                'name' => 'Refund Notification',
                'category' => 'order',
                'subject' => 'Refund Processed for Order {{order_number}}',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Refund Processed Successfully</h2>
<p>Hello {{customer_name}}, we have processed a refund of <strong>{{order_total}}</strong> for Order <strong>{{order_number}}</strong>.</p>
<p>Depending on your payment provider (bKash, Nagad, Card), funds typically reflect within 3 to 7 business days.</p>
HTML
            ],

            // 13. Invoice Email
            'invoice_email' => [
                'name' => 'Invoice Email',
                'category' => 'order',
                'subject' => 'Your {{business_name}} Invoice — {{order_number}}',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Official Invoice for Order {{order_number}}</h2>
<p>Hello {{customer_name}}, your official purchase invoice for order <strong>{{order_number}}</strong> is attached as a PDF to this email.</p>
<p><strong>Total Amount:</strong> {{order_total}}<br>
<strong>Payment Status:</strong> {{payment_status}}</p>
<div style="margin:20px 0; text-align:center;">
    <a href="{{invoice_url}}" style="display:inline-block; padding:10px 24px; background-color:#0f4d2c; color:#ffffff; font-size:12px; font-weight:700; text-decoration:none; border-radius:24px;">View &amp; Print Invoice Online &rarr;</a>
</div>
HTML
            ],

            // 14. Review Invitation
            'review_invitation' => [
                'name' => 'Review Invitation',
                'category' => 'marketing',
                'subject' => 'How was your recent order with {{business_name}}?',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">We Value Your Feedback! ⭐</h2>
<p>Hello {{customer_name}}, thank you for shopping with {{business_name}}. We would love to hear about your experience with Order <strong>{{order_number}}</strong>.</p>
<p>Your honest review helps our local farmers, suppliers, and fellow shoppers.</p>
<div style="margin:24px 0; text-align:center;">
    <a href="{{cta_url}}" style="display:inline-block; padding:12px 28px; background-color:#f97316; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:30px;">Leave a Verified Review &rarr;</a>
</div>
HTML
            ],

            // 15. Promotional Campaign
            'promotional_campaign' => [
                'name' => 'Promotional Campaign',
                'category' => 'marketing',
                'subject' => 'Special Deals from {{business_name}} — Limited Time Only!',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">{{announcement_title}}</h2>
<p>Hello {{customer_name}},</p>
<div style="margin:16px 0; font-size:14px; line-height:1.7;">
    {{announcement_body}}
</div>
<div style="margin:24px 0; text-align:center;">
    <a href="{{cta_url}}" style="display:inline-block; padding:12px 30px; background-color:#0f4d2c; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; border-radius:30px; box-shadow:0 3px 8px rgba(15,77,44,0.25);">{{cta_text}} &rarr;</a>
</div>
HTML
            ],

            // 16. Contact Form Notification
            'contact_form_notification' => [
                'name' => 'Contact Form Notification',
                'category' => 'notification',
                'subject' => 'We Received Your Message — {{business_name}}',
                'body_html' => <<<HTML
<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:800; color:#0f4d2c;">Thank You for Contacting Us</h2>
<p>Hello {{customer_name}}, we have received your direct message through the {{business_name}} website.</p>
<p>A member of our customer care team will review your inquiry and get back to you within 24 business hours.</p>
<p style="font-size:12px; color:#64748b;">For urgent assistance, call our helpline directly at {{support_phone}}.</p>
HTML
            ],
        ];
    }
}
