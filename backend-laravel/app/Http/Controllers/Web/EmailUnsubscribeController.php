<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\EmailPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Public, signed unsubscribe / preference page. Links are bound to the
 * APP_KEY signature, so an address can only be changed through a link we
 * actually emailed to it. Transactional email is never affected.
 */
class EmailUnsubscribeController extends Controller
{
    public function show(Request $request)
    {
        $email = EmailPreferenceService::decodeEmail($request->query('e'));
        abort_unless($email, 404);

        return view('web.unsubscribe', [
            'maskedEmail' => AuthWebController::maskEmail($email),
            'subscribed' => EmailPreferenceService::isMarketingSubscribed($email),
            'actionUrl' => URL::signedRoute('email.unsubscribe.submit', ['e' => EmailPreferenceService::encodeEmail($email)]),
        ]);
    }

    public function update(Request $request)
    {
        $email = EmailPreferenceService::decodeEmail($request->query('e'));
        abort_unless($email, 404);

        $request->validate(['action' => 'required|in:unsubscribe,resubscribe']);

        if ($request->input('action') === 'resubscribe') {
            $ok = EmailPreferenceService::resubscribe($email, 'preferences_page');

            return redirect(URL::signedRoute('email.unsubscribe', ['e' => EmailPreferenceService::encodeEmail($email)]))
                ->with($ok ? 'success' : 'error', $ok
                    ? 'You are subscribed again. Thanks for staying with us!'
                    : 'This address cannot be re-subscribed automatically. Please contact our support team.');
        }

        EmailPreferenceService::unsubscribe($email, 'unsubscribe_link');

        return redirect(URL::signedRoute('email.unsubscribe', ['e' => EmailPreferenceService::encodeEmail($email)]))
            ->with('success', 'You have been unsubscribed from promotional emails. You will still receive order and account notifications.');
    }

    /** RFC 8058 one-click endpoint used by mail clients (no CSRF token available). */
    public function oneClick(Request $request)
    {
        $email = EmailPreferenceService::decodeEmail($request->query('e'));
        if ($email) {
            EmailPreferenceService::unsubscribe($email, 'one_click');
        }

        return response('Unsubscribed', 200)->header('Content-Type', 'text/plain');
    }
}
