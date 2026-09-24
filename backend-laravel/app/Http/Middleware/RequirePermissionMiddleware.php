<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\RbacService;

class RequirePermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $authUser = $request->attributes->get('auth_user');
        if (!$authUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!RbacService::hasPermission($authUser, $permission)) {
            return response()->json([
                'success' => false,
                'message' => "Access denied. Requires '{$permission}' permission.",
                'data' => ['requiredPermission' => $permission],
            ], 403);
        }

        return $next($request);
    }
}
