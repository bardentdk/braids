<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(): Response
    {
        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.category', 'coupon']);

        $freeShippingAt = (float) Setting::get('shop_free_shipping_at', 75);
        $shippingCost   = (float) Setting::get('shop_shipping_cost', 4.90);
        $subtotal       = $cart->subtotal;
        $shipping       = $subtotal >= $freeShippingAt ? 0 : $shippingCost;

        return Inertia::render('Public/Cart', [
            'cart' => [
                'id'              => $cart->id,
                'subtotal'        => $subtotal,
                'discount_amount' => $cart->discount_amount,
                'shipping'        => $shipping,
                'total'           => max(0, $subtotal - $cart->discount_amount + $shipping),
                'items_count'     => $cart->items_count,
                'coupon'          => $cart->coupon ? [
                    'code'  => $cart->coupon->code,
                    'type'  => $cart->coupon->type->value,
                    'value' => $cart->coupon->value,
                ] : null,
                'items' => $cart->items->map(fn($item) => [
                    'id'          => $item->id,
                    'quantity'    => $item->quantity,
                    'line_total'  => $item->line_total,
                    'product' => [
                        'id'           => $item->product->id,
                        'name'         => $item->product->name,
                        'slug'         => $item->product->slug,
                        'price'        => $item->product->price,
                        'compare_price'=> $item->product->compare_price,
                        'is_on_sale'   => $item->product->is_on_sale,
                        'thumbnail_url'=> $item->product->thumbnail_url,
                        'stock'        => $item->product->stock,
                        'is_in_stock'  => $item->product->is_in_stock,
                        'track_stock'  => $item->product->track_stock,
                    ],
                ]),
            ],
            'settings' => [
                'free_shipping_at' => $freeShippingAt,
                'shipping_cost'    => $shippingCost,
            ],
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:99',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (! $product->is_active) {
            return back()->with('error', 'Ce produit n\'est plus disponible.');
        }

        if ($product->track_stock && $product->stock < $request->quantity && ! $product->allow_backorder) {
            return back()->with('error', "Stock insuffisant. Seulement {$product->stock} disponible(s).");
        }

        $cart = $this->getOrCreateCart();

        $existing = $cart->items()->where('product_id', $product->id)->first();

        if ($existing) {
            $newQty = $existing->quantity + $request->quantity;
            if ($product->track_stock && $newQty > $product->stock && ! $product->allow_backorder) {
                return back()->with('error', "Vous ne pouvez pas ajouter plus de {$product->stock} article(s).");
            }
            $existing->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity'   => $request->quantity,
            ]);
        }

        return back()->with('success', "\"{$product->name}\" ajouté au panier.");
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeCartItem($item);

        $request->validate(['quantity' => 'required|integer|min:1|max:99']);

        $product = $item->product;
        if ($product->track_stock && $request->quantity > $product->stock && ! $product->allow_backorder) {
            return back()->with('error', "Stock insuffisant. Maximum : {$product->stock}.");
        }

        $item->update(['quantity' => $request->quantity]);

        return back();
    }

    public function remove(CartItem $item): RedirectResponse
    {
        $this->authorizeCartItem($item);
        $name = $item->product->name;
        $item->delete();

        return back()->with('success', "\"{$name}\" retiré du panier.");
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|max:50']);

        $coupon = Coupon::where('code', strtoupper($request->code))->first();

        if (! $coupon || ! $coupon->is_valid) {
            return back()->with('error', 'Ce code promo est invalide ou expiré.');
        }

        $cart     = $this->getOrCreateCart();
        $subtotal = $cart->subtotal;

        if ($subtotal < $coupon->min_order_amount) {
            return back()->with('error', "Ce coupon est valable à partir de {$coupon->min_order_amount}€ d'achat.");
        }

        $discount = $coupon->calculateDiscount($subtotal);
        $cart->update([
            'coupon_id'       => $coupon->id,
            'coupon_code'     => $coupon->code,
            'discount_amount' => $discount,
        ]);

        return back()->with('success', "Code \"{$coupon->code}\" appliqué — {$discount}€ de réduction.");
    }

    private function getOrCreateCart(): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        $sessionId = session()->get('cart_session_id', \Str::uuid()->toString());
        session()->put('cart_session_id', $sessionId);

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    private function authorizeCartItem(CartItem $item): void
    {
        $cart = $this->getOrCreateCart();
        abort_if($item->cart_id !== $cart->id, 403);
    }
}