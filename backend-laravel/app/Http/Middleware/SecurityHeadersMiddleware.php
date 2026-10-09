<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach recommended security & SEO headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Canonical domain enforcement (HTTPS & non-www)
        // If accessed over HTTP in production or with www., redirect to primary canonical URL
        if (config('app.env') === 'production') {
            $host = $request->header('host');
            if ($host && str_starts_with(strtolower($host), 'www.')) {
                $cleanHost = substr($host, 4);

                return redirect()->to('https://'.$cleanHost.$request->getRequestUri(), 301);
            }

            if (! $request->isSecure() && $request->header('x-forwarded-proto') !== 'https') {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS header (Strict-Transport-Security) for HTTPS
        if ($request->isSecure() || $request->header('x-forwarded-proto') === 'https' || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
