<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $role = $user['role'] ?? '';
        $customRole = $user['customRole'] ?? '';
        $permissions = $user['permissions'] ?? [];

        if (
            $role === 'admin' ||
            $role === 'manager' ||
            $customRole === 'SUPER_ADMIN' ||
            $customRole === 'ADMIN' ||
            $customRole === 'MANAGER' ||
            $customRole === 'EDITOR' ||
            $customRole === 'STAFF' ||
            count($permissions) > 0
        ) {
            return $next($request);
        }

        return response()->json(['success' => false, 'message' => 'Admin access required'], 403);
    }
}
