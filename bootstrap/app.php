<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

// ⚠️ NE PAS importer Illuminate\Http\Response ici
// On utilise \Symfony\Component\HttpFoundation\Response qui est le parent
// commun de Response ET RedirectResponse → plus de TypeError

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Exclure les webhooks du CSRF (Stripe + PayPal)
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
            'webhooks/paypal',
        ]);

        // Alias middleware custom
        $middleware->alias([
            'auth'       => \App\Http\Middleware\Authenticate::class,
            'guest.only' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'admin'      => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'auth.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'verified'   => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'track.cart' => \App\Http\Middleware\TrackCartSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ✅ \Symfony\Component\HttpFoundation\Response accepte TOUTES les réponses :
        //    Illuminate\Http\Response, RedirectResponse, JsonResponse, etc.
        $exceptions->respond(function (
            \Symfony\Component\HttpFoundation\Response $response,
            \Throwable $e,
            Request $request
        ) {
            if (
                ! app()->environment(['local', 'testing'])
                && in_array($response->getStatusCode(), [500, 503, 404, 403])
            ) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });

    })->create();