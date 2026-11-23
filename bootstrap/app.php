<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UseCloudflareClientIp;
use App\Modules\Accounts\Http\Middleware\EnsureNotBlocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(UseCloudflareClientIp::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->trustHosts();
        $middleware->web(append: [EnsureNotBlocked::class]);
        $middleware->appendToGroup('web', 'throttle:global');
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('account'));
        $middleware->encryptCookies();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
