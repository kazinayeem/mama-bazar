@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-4xl">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Email Automation Triggers</h1>
            <p class="text-xs text-slate-500">Enable or disable individual automated transactional emails and PDF invoice attachments.</p>
        </div>
        <div>
            <a href="{{ route('admin.email.dashboard') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.email.automation.update') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Authentication Automations --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Authentication &amp; Account Security</h2>
                <p class="text-xs text-slate-500">Automated emails triggered during registration and password recovery.</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Account Verification OTP</span>
                        <span class="text-[11px] text-slate-400">Sends 6-digit verification code when customer registers a new account.</span>
                    </div>
                    <input type="checkbox" name="email_auto_account_otp" value="1" {{ !empty($settings['email_auto_account_otp']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Welcome Email</span>
                        <span class="text-[11px] text-slate-400">Sends branded welcome email upon successful registration / verification.</span>
                    </div>
                    <input type="checkbox" name="email_auto_welcome" value="1" {{ !empty($settings['email_auto_welcome']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Password Reset Email</span>
                        <span class="text-[11px] text-slate-400">Sends reset link and verification code when customer clicks "Forgot Password".</span>
                    </div>
                    <input type="checkbox" name="email_auto_password_reset" value="1" {{ !empty($settings['email_auto_password_reset']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Login OTP</span>
                        <span class="text-[11px] text-slate-400">Sends 6-digit code for passwordless or two-factor login requests.</span>
                    </div>
                    <input type="checkbox" name="email_auto_login_otp" value="1" {{ !empty($settings['email_auto_login_otp']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>
            </div>
        </div>

        {{-- Order & Transaction Automations --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Orders, Invoices &amp; Payments</h2>
                <p class="text-xs text-slate-500">Transactional emails dispatched during customer order and payment lifecycles.</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Order Confirmation Email</span>
                        <span class="text-[11px] text-slate-400">Immediately sends order receipt with item breakdown and tracking link.</span>
                    </div>
                    <input type="checkbox" name="email_auto_order_created" value="1" {{ !empty($settings['email_auto_order_created']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Payment Verified Confirmation</span>
                        <span class="text-[11px] text-slate-400">Sends receipt notification when admin verifies bKash, Nagad, or Bank payment.</span>
                    </div>
                    <input type="checkbox" name="email_auto_payment_confirmed" value="1" {{ !empty($settings['email_auto_payment_confirmed']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Order Lifecycle Status Updates</span>
                        <span class="text-[11px] text-slate-400">Sends emails when status changes: Confirmed, Processing, Shipped, Delivered, Cancelled.</span>
                    </div>
                    <input type="checkbox" name="email_auto_order_status" value="1" {{ !empty($settings['email_auto_order_status']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-xl border border-emerald-300 bg-emerald-50/40 hover:border-emerald-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-emerald-900 block">Automatically Attach PDF Invoice 📎</span>
                        <span class="text-[11px] text-slate-500">Generates and attaches the official PDF invoice (with Bornosoft attribution) to confirmation emails.</span>
                    </div>
                    <input type="checkbox" name="email_auto_invoice_pdf" value="1" {{ !empty($settings['email_auto_invoice_pdf']) ? 'checked' : '' }} class="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500">
                </label>
            </div>
        </div>

        {{-- Engagement & Support Automations --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">Engagement &amp; Support</h2>
                <p class="text-xs text-slate-500">Automated post-purchase followups and customer service responses.</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Product Review Invitation</span>
                        <span class="text-[11px] text-slate-400">Invites verified customers to review their purchased goods.</span>
                    </div>
                    <input type="checkbox" name="email_auto_review_invitation" value="1" {{ !empty($settings['email_auto_review_invitation']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>

                <label class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-brand-green-500 transition cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Contact Form Auto-Acknowledgment</span>
                        <span class="text-[11px] text-slate-400">Sends confirmation when a visitor submits a contact message.</span>
                    </div>
                    <input type="checkbox" name="email_auto_contact_form" value="1" {{ !empty($settings['email_auto_contact_form']) ? 'checked' : '' }} class="h-4 w-4 rounded text-brand-green-600 focus:ring-brand-green-500">
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="rounded-xl bg-brand-green-600 px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-brand-green-700 transition">
                Save Automation Settings
            </button>
        </div>

    </form>

</div>
@endsection
