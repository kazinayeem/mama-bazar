<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\RequirePermissionMiddleware;
use App\Http\Middleware\AdminOnlyMiddleware;

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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('/') || $request->expectsJson(),
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
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot {$request->method()} /{$request->path()}",
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred',
            ], $e->getStatusCode());
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
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
