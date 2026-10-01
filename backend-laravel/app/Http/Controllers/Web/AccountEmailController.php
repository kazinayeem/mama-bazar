<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailOtpService;
use App\Services\EmailPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountEmailController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $pending = $request->session()->get('email_change_pending');

        return view('web.account-email', [
            'user' => $user,
            'subscribed' => $user->email ? EmailPreferenceService::isMarketingSubscribed($user->email) : false,
            'pendingEmail' => $pending,
            'pendingMasked' => $pending ? AuthWebController::maskEmail($pending) : null,
            'cooldown' => $pending ? EmailOtpService::cooldownRemaining($pending, EmailOtpService::TYPE_EMAIL_CHANGE) : 0,
            'codeLength' => EmailOtpService::length(),
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        if (! $user->email) {
            return back()->with('error', 'Add an email address to your account first.');
        }

        $optIn = $request->boolean('marketing_opt_in');
        EmailPreferenceService::setUserConsent($user, $optIn, 'account_settings');

        if ($optIn && ! EmailPreferenceService::isMarketingSubscribed($user->email)) {
            return back()->with('error', 'We could not re-subscribe this address automatically. Please contact support.');
        }

        return back()->with('success', $optIn
            ? 'You are subscribed to offers and newsletters.'
            : 'You will no longer receive promotional emails. Order and account emails are unaffected.');
    }

    public function requestChange(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'new_email' => ['required', 'email:rfc', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => 'required|string',
        ], [
            'new_email.unique' => 'That email address is already used by another account.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.'])->withInput();
        }

        $newEmail = EmailOtpService::normalize($data['new_email']);
        if ($newEmail === EmailOtpService::normalize((string) $user->email)) {
            return back()->with('error', 'That is already your email address.');
        }

        $result = EmailOtpService::issueAndSend($newEmail, EmailOtpService::TYPE_EMAIL_CHANGE, $user->name, $user->id);
        if ($result['success'] || ($result['cooldown'] ?? false)) {
            $request->session()->put('email_change_pending', $newEmail);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function confirmChange(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => 'required|string|max:12']);
        $newEmail = (string) $request->session()->get('email_change_pending', '');

        if ($newEmail === '') {
            return back()->with('error', 'No email change is pending. Please start again.');
        }

        if (User::whereRaw('LOWER(email) = ?', [$newEmail])->where('id', '!=', $user->id)->exists()) {
            $request->session()->forget('email_change_pending');

            return back()->with('error', 'That email address is already used by another account.');
        }

        $result = EmailOtpService::verifyOtp($newEmail, (string) $request->input('code'), EmailOtpService::TYPE_EMAIL_CHANGE);
        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $oldEmail = $user->email;
        $wasSubscribed = $oldEmail ? EmailPreferenceService::isMarketingSubscribed($oldEmail) : false;

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => now(),
        ])->save();
        $request->session()->forget('email_change_pending');

        if ($wasSubscribed) {
            EmailPreferenceService::setUserConsent($user, true, 'email_change');
        }

        if ($oldEmail) {
            AuthWebController::queueSecurityNotice(
                $user,
                'The email address on your account was changed to '.AuthWebController::maskEmail($newEmail).'.',
                $oldEmail
            );
        }

        return back()->with('success', 'Your email address has been updated and verified.');
    }
}
