<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $route = $request->user()->role === UserRole::Admin
                ? route('admin.dashboard')
                : route('home');

            return redirect($route);
        }

        return $next($request);
    }
}