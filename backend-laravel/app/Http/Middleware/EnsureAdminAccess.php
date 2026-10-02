<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web session guard for /admin/* — complements AdminAuthController's login-time
 * check so a storefront customer session can never browse the admin panel.
 */
class EnsureAdminAccess
{
    public const ALLOWED_ROLES = ['admin', 'manager', 'editor', 'staff', 'super_admin'];

    public static function isAdminLike(?object $user): bool
    {
        if (! $user) {
            return false;
        }

        if (($user->status ?? 'active') !== 'active') {
            return false;
        }

        if (in_array($user->role ?? '', self::ALLOWED_ROLES, true)) {
            return true;
        }

        return ! empty($user->custom_role);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! self::isAdminLike($user)) {
            abort(403, 'You do not have permission to access the admin panel.');
        }

        return $next($request);
    }
}
