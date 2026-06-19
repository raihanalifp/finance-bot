<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureDashboardAccess;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\TrackActivity;
use App\Services\Security\AuditLogService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'dashboard.access' => EnsureDashboardAccess::class,
        ]);

        $middleware->web(append: [
            RequestId::class,
            AddSecurityHeaders::class,
            TrackActivity::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'telegram/webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): void {
            Log::error('Unhandled application exception.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'request_id' => request()?->attributes->get(RequestId::ATTRIBUTE),
                'path' => request()?->path(),
            ]);

            app(AuditLogService::class)->record('unhandled_exception', context: [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'path' => request()?->path(),
            ]);
        });
    })->create();
