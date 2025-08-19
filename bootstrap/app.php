<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'presence' => \App\Http\Middleware\UserPresenceMiddleware::class,
        ]);

        // Presence middleware removed from web group to prevent API conflicts
        // Online status is now handled via PusherService and explicit API calls
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
