<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            if ($request->routeIs('admin.password.*') || $request->routeIs('admin.logout') || $request->is('admin/logout')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password change required before accessing administrative features.',
                    'redirect' => route('admin.password.change'),
                ], 403);
            }

            return redirect()->route('admin.password.change')
                ->with('info', 'Please set your permanent password to continue.');
        }

        return $next($request);
    }
}
