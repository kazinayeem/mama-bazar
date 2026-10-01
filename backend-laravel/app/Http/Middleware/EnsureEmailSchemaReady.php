<?php

namespace App\Http\Middleware;

use App\Support\EmailSchema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows setup instructions on Email Management pages until the email system
 * migrations have been run, instead of a database error.
 */
class EnsureEmailSchemaReady
{
    public function handle(Request $request, Closure $next): Response
    {
        if (EmailSchema::isReady()) {
            return $next($request);
        }

        $missing = EmailSchema::missing();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Email system database migrations have not been run yet.',
            ], 503);
        }

        return response()->view('admin.email.setup-required', ['missing' => $missing], 503);
    }
}
