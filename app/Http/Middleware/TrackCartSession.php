<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackCartSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Fusion panier session → panier user à la connexion
        if ($request->user() && $request->session()->has('cart_session_id')) {
            $sessionId  = $request->session()->get('cart_session_id');
            $sessionCart = Cart::where('session_id', $sessionId)->with('items')->first();

            if ($sessionCart) {
                $userCart = Cart::firstOrCreate(
                    ['user_id' => $request->user()->id],
                    ['session_id' => null]
                );

                foreach ($sessionCart->items as $item) {
                    $existing = $userCart->items()->where('product_id', $item->product_id)->first();
                    if ($existing) {
                        $existing->increment('quantity', $item->quantity);
                    } else {
                        $userCart->items()->create([
                            'product_id' => $item->product_id,
                            'quantity'   => $item->quantity,
                            'options'    => $item->options,
                        ]);
                    }
                }

                $sessionCart->delete();
                $request->session()->forget('cart_session_id');
            }
        }

        return $next($request);
    }
}