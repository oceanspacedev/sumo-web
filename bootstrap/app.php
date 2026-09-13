<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Keep the legacy proxy header contract while using Laravel's built-in
        // middleware stack.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->trimStrings([
            'current_password',
            'password',
            'password_confirmation',
        ]);
        $middleware->encryptCookies();
        $middleware->validateCsrfTokens();
        $middleware->preventRequestsDuringMaintenance();

        $middleware->alias([
            'checkRole' => App\Http\Middleware\checkRole::class,
        ]);

        // The old API group was stateless and used the named 60 RPM limiter.
        $middleware->throttleApi('api');
        $middleware->redirectUsersTo('/home');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry's Laravel integration is the single application reporting path.
        Integration::handles($exceptions);
    })
    ->create();
