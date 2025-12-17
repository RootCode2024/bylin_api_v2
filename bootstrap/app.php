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
    ->withMiddleware(function (Middleware $middleware): void {

        // --- AJOUTER CETTE LIGNE OBLIGATOIREMENT ---
        // Elle active le support des cookies/sessions pour Sanctum sur les routes API
        $middleware->statefulApi();


        // API-first: Return JSON error instead of redirecting to login
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('v1/*')) {
                abort(401, 'Unauthenticated.');
            }
            // For web routes (if any), redirect to login
            return route('login');
        });

        // Register custom middleware aliases
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'customer.auth' => \App\Http\Middleware\EnsureUserIsCustomer::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'fedapay.signature' => \App\Http\Middleware\VerifyFedaPaySignature::class,
            'track.login' => \App\Http\Middleware\TrackLoginActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
