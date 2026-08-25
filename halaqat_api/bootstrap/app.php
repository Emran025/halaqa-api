<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API authentication and authorization are attached at route level.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $fieldErrors = [];
            foreach ($exception->errors() as $field => $messages) {
                $fieldErrors[] = [
                    'field' => (string) $field,
                    'messages' => array_values($messages),
                ];
            }

            return response()->json([
                'message' => 'The provided data is invalid.',
                'field_errors' => $fieldErrors,
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $requestId = $request->header('X-Request-Id');
            $requestId = is_string($requestId) && Str::isUuid($requestId) ? $requestId : null;

            return response()->json([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Authentication is required.',
                    'details' => [
                        'request_id' => $requestId,
                        'field_errors' => [],
                        'resource' => null,
                        'resource_id' => null,
                        'retry_after_seconds' => null,
                    ],
                ],
            ], 401);
        });
    })->create();
