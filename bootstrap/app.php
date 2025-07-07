<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckInstallation;
use App\Http\Middleware\EnsurePushConfig;
use App\Http\Middleware\CheckDomainMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\DomainMiddleware;
use App\Http\Middleware\CheckUserAccess;
use App\Http\Middleware\RateLimitMiddleware;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(CheckInstallation::class);
        // $middleware->prepend(CheckDomainMiddleware::class); // chain middleware sample

        // ONLINE UNCOMMENT AFTER ACTIVATION
        // $middleware->prepend(PermissionMiddleware::class);
        // $middleware->prepend(DomainMiddleware::class);
        // $middleware->prepend(CheckUserAccess::class);
        // $middleware->prepend(RateLimitMiddleware::class);
        
        $middleware->alias([
            'ensure_push_config' => EnsurePushConfig::class,
        ]);
        $middleware->group('install', [
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            RateLimitMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
