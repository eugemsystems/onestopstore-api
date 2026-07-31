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
        $middleware->use([
            \Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class,
            \Illuminate\Http\Middleware\TrustHosts::class,
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        $middleware->trimStrings(except: ['description', 'short_description', 'specifications', 'notes']);

        // Add banned vendor check to web routes
        $middleware->web(append: [
            \App\Http\Middleware\CheckBannedVendor::class,
        ]);

        $middleware->api(prepend: [
            //\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CoerceMobileResponse::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'localization' => \App\Http\Middleware\localization::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability'   => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'public.apicache' => \App\Http\Middleware\PublicApiCache::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.banned.vendor' => \App\Http\Middleware\CheckBannedVendor::class,
            'restrict.email' => \App\Http\Middleware\RestrictToSpecificEmail::class,
            'filter.bots' => \App\Http\Middleware\FilterBotTraffic::class,
            'crm.auth' => \App\Http\Middleware\CrmAuth::class,
        ]);


    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
