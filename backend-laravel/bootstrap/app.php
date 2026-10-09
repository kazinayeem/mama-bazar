<?php

use App\Http\Middleware\AdminOnlyMiddleware;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureAdminPasswordChanged;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsureEmailSchemaReady;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureSmtpUnlocked;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\RequirePermissionMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => JwtAuthMiddleware::class,
            'require.permission' => RequirePermissionMiddleware::class,
            'admin.only' => AdminOnlyMiddleware::class,
            'admin.access' => EnsureAdminAccess::class,
            'admin.can' => EnsureAdminPermission::class,
            'email.verified' => EnsureEmailVerified::class,
            'email.schema' => EnsureEmailSchemaReady::class,
            'smtp.unlocked' => EnsureSmtpUnlocked::class,
            'admin.password.changed' => EnsureAdminPasswordChanged::class,
        ]);

        // RFC 8058 one-click unsubscribe is POSTed by mail clients without a CSRF token (route is signed).
        $middleware->validateCsrfTokens(except: ['email/unsubscribe/one-click']);

        // Trusted proxies: Only trust forwarded headers if explicitly configured via TRUSTED_PROXIES
        $trustedProxies = env('TRUSTED_PROXIES');
        if ($trustedProxies !== null && $trustedProxies !== '') {
            $middleware->trustProxies(at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)));
        }

        $middleware->web(append: [
            SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $allMessages = [];
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $msg) {
                        $allMessages[] = $msg;
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => implode(', ', $allMessages),
                ], 400);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot {$request->method()} /{$request->path()}",
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred',
                ], $e->getStatusCode());
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
                $message = (config('app.env') === 'production' && $status === 500)
                    ? 'Internal Server Error'
                    : ($e->getMessage() ?: 'Internal Server Error');

                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], $status);
            }
        });
    })->create();
