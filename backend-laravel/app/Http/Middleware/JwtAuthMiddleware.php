<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;
use App\Services\RbacService;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $resolved = RbacService::resolveUserPermissions(
                (int) $user->id,
                $user->role ?? 'user',
                $user->custom_role ?? null
            );
            $authUser = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'customRole' => $resolved['customRole'],
                'permissions' => $resolved['permissions'],
            ];
            $request->attributes->set('auth_user', $authUser);
            $request->attributes->set('user', $authUser);

            return $next($request);
        }

        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $token = substr($authHeader, 7);
        $decoded = JwtService::verify($token);

        if (!$decoded || !isset($decoded['id'])) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired token'], 401);
        }

        $resolved = RbacService::resolveUserPermissions(
            (int) $decoded['id'],
            $decoded['role'] ?? 'user',
            $decoded['customRole'] ?? null
        );

        $authUser = array_merge($decoded, [
            'permissions' => $resolved['permissions'],
            'customRole' => $resolved['customRole'],
        ]);

        $request->attributes->set('auth_user', $authUser);
        $request->attributes->set('user', $authUser);

        return $next($request);
    }
}
