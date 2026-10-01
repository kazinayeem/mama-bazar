<?php

namespace App\Http\Middleware;

use App\Services\EmailSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks accounts that were created with mandatory email verification and
 * have not verified yet. Grandfathered accounts and guests pass through.
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustVerifyEmail() && EmailSettingService::verificationEnforced()) {
            $message = 'Please verify your email address to continue.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message, 'requires_email_verification' => true], 403);
            }

            return redirect()->guest(route('auth.verify-otp'))->with('info', $message);
        }

        return $next($request);
    }
}
