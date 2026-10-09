<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated SMTP PIN lock has been removed; SMTP access is directly governed by admin permission middleware.
 */
class EnsureSmtpUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
