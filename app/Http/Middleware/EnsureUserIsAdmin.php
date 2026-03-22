<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== UserRole::Admin) {
            if ($request->inertia()) {
                return redirect()->route('home')->with('error', 'Accès non autorisé.');
            }
            abort(403, 'Accès réservé à l\'administratrice.');
        }

        return $next($request);
    }
}