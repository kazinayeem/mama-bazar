<?php

namespace App\Http\Middleware;

use App\Services\SmtpLockService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSmtpUnlocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! SmtpLockService::isUnlocked()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP Settings are locked. Enter security PIN to unlock.',
                ], 403);
            }

            abort(403, 'SMTP Settings are locked. Enter security PIN to unlock.');
        }

        return $next($request);
    }
}
