<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'     => $request->user()->id,
                    'name'   => $request->user()->name,
                    'email'  => $request->user()->email,
                    'role'   => $request->user()->role,
                    'avatar' => $request->user()->avatar_url,
                ] : null,
                'client' => $request->user()?->client, // ← ajouter ça

            ],
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
                'warning' => session('warning'),
                'info'    => session('info'),
            ],
            'ziggy' => [
                'url'      => config('app.url'),
                'port'     => null,
                'defaults' => [],
                'routes'   => collect(Route::getRoutes())->mapWithKeys(function ($route) {
                    return [$route->getName() => [
                        'uri'      => $route->uri(),
                        'methods'  => $route->methods(),
                        'bindings' => [],
                    ]];
                })->filter(fn($v, $k) => $k !== null)->toArray(),
                'location' => $request->url(),
            ],
        ]);
    }
}