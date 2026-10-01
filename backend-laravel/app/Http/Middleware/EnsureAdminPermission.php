<?php

namespace App\Http\Middleware;

use App\Services\RbacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session-based RBAC check for admin web routes: `admin.can:perm.a|perm.b`
 * passes when the user holds any of the listed permissions.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (self::allows($user, explode('|', $permissions))) {
            return $next($request);
        }

        $message = 'You do not have permission to access this area.';
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return redirect()->route('admin.dashboard')->with('error', $message);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public static function allows(?object $user, array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        if (in_array($user->role ?? '', ['admin', 'super_admin'], true)) {
            return true;
        }

        $resolved = RbacService::resolveUserPermissions((int) $user->id, (string) ($user->role ?? ''), $user->custom_role ?? null);
        $context = $resolved + ['id' => (int) $user->id, 'role' => (string) ($user->role ?? '')];

        foreach ($permissions as $permission) {
            if (RbacService::hasPermission($context, trim($permission))) {
                return true;
            }
        }

        return false;
    }
}
