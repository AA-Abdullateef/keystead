<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\IdempotentRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
            'role' => CheckRole::class,
            'active' => EnsureUserIsActive::class,
            'verified.email' => EnsureEmailIsVerified::class,
            'idempotent' => IdempotentRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every API route answers with the same JSON envelope, including for errors.
        $wantsJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();
        $envelope = fn (string $message, int $status, $errors = null) => response()->json(
            array_filter(['success' => false, 'message' => $message, 'errors' => $errors], fn ($v) => $v !== null),
            $status,
        );

        $exceptions->render(fn (ValidationException $e, Request $r) => $wantsJson($r)
            ? $envelope('The given data was invalid.', 422, $e->errors()) : null);

        $exceptions->render(fn (AuthenticationException $e, Request $r) => $wantsJson($r)
            ? $envelope('Unauthenticated.', 401) : null);

        $exceptions->render(fn (AuthorizationException $e, Request $r) => $wantsJson($r)
            ? $envelope($e->getMessage() ?: 'This action is unauthorized.', $e->status() ?? 403) : null);

        $exceptions->render(fn (ModelNotFoundException $e, Request $r) => $wantsJson($r)
            ? $envelope('Resource not found.', 404) : null);

        $exceptions->render(fn (NotFoundHttpException $e, Request $r) => $wantsJson($r)
            ? $envelope('Resource not found.', 404) : null);

        $exceptions->render(fn (HttpExceptionInterface $e, Request $r) => $wantsJson($r)
            ? $envelope($e->getMessage() ?: 'Request failed.', $e->getStatusCode()) : null);

        $exceptions->render(fn (\Throwable $e, Request $r) => $wantsJson($r) && ! config('app.debug')
            ? $envelope('Server error.', 500) : null);
    })->create();
