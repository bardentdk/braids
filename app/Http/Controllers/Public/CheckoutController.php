<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CheckoutRequest;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\BrevoService;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(protected BrevoService $brevo) {}

    // ── Page de checkout ──────────────────────────────────────────────
    public function index(Request $request): Response|RedirectResponse
    {
        $cart = $this->getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                             ->with('error', 'Votre panier est vide.');
        }

        $cart->load(['items.product', 'coupon']);

        $freeShippingAt = (float) Setting::get('shop_free_shipping_at', 75);
        $shippingCost   = (float) Setting::get('shop_shipping_cost', 4.90);
        $subtotal       = $cart->subtotal;
        $shipping       = $subtotal >= $freeShippingAt ? 0 : $shippingCost;
        $taxRate        = (float) Setting::get('shop_tax_rate', 20);
        $taxAmount      = round(($subtotal - $cart->discount_amount) * ($taxRate / 100), 2);
        $total          = round($subtotal - $cart->discount_amount + $shipping + $taxAmount, 2);

        $user   = $request->user();
        $client = $user?->client;

        return Inertia::render('Public/Checkout', [
            'cart' => [
                'id'              => $cart->id,
                'subtotal'        => $subtotal,
                'discount_amount' => $cart->discount_amount,
                'shipping'        => $shipping,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'coupon'          => $cart->coupon
                    ? ['code' => $cart->coupon->code, 'value' => $cart->discount_amount]
                    : null,
                'items' => $cart->items->map(fn($item) => [
                    'id'          => $item->id,
                    'quantity'    => $item->quantity,
                    'line_total'  => $item->line_total,
                    'product' => [
                        'id'            => $item->product->id,
                        'name'          => $item->product->name,
                        'sku'           => $item->product->sku,
                        'price'         => $item->product->price,
                        'thumbnail_url' => $item->product->thumbnail_url,
                    ],
                ]),
            ],
            'prefill' => [
                'first_name'  => $client?->first_name ?? '',
                'last_name'   => $client?->last_name  ?? '',
                'email'       => $user?->email         ?? '',
                'phone'       => $client?->phone        ?? '',
                'address'     => $client?->address      ?? '',
                'city'        => $client?->city         ?? '',
                'postal_code' => $client?->postal_code  ?? '',
                'country'     => $client?->country      ?? 'France',
            ],
            'payment_methods' => collect(PaymentMethod::cases())->map(fn($m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ]),
            'settings' => [
                'free_shipping_at' => $freeShippingAt,
                'shipping_cost'    => $shippingCost,
            ],
        ]);
    }

    // ── Soumettre le checkout → créer la commande → rediriger vers paiement ─
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $this->getCart();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                             ->with('error', 'Votre panier est vide.');
        }

        $cart->load(['items.product', 'coupon']);

        DB::beginTransaction();

        try {
            $data           = $request->validated();
            $freeShippingAt = (float) Setting::get('shop_free_shipping_at', 75);
            $shippingCost   = (float) Setting::get('shop_shipping_cost', 4.90);
            $taxRate        = (float) Setting::get('shop_tax_rate', 20);
            $subtotal       = $cart->subtotal;
            $discount       = $cart->discount_amount;
            $shipping       = $subtotal >= $freeShippingAt ? 0 : $shippingCost;
            $taxAmount      = round(($subtotal - $discount) * ($taxRate / 100), 2);
            $total          = round($subtotal - $discount + $shipping + $taxAmount, 2);

            // ── Récupérer ou créer le client ──────────────────────────
            $client = auth()->user()?->client
                ?? \App\Models\Client::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'first_name'  => $data['first_name'],
                        'last_name'   => $data['last_name'],
                        'phone'       => $data['phone']        ?? null,
                        'address'     => $data['address']      ?? null,
                        'city'        => $data['city']         ?? null,
                        'postal_code' => $data['postal_code']  ?? null,
                        'country'     => $data['country']      ?? 'France',
                        'user_id'     => auth()->id(),
                    ]
                );

            // ── Vérification des stocks AVANT de créer la commande ────
            // On vérifie mais on NE décrémente PAS encore — ça sera fait
            // dans PaymentController::handleOrderPayment() après paiement confirmé
            foreach ($cart->items as $item) {
                $product = $item->product;
                if ($product->track_stock
                    && ! $product->allow_backorder
                    && $product->stock < $item->quantity
                ) {
                    DB::rollBack();
                    return redirect()->route('cart.index')
                        ->with('error', "Stock insuffisant pour \"{$product->name}\". Veuillez mettre à jour votre panier.");
                }
            }

            // ── Créer la commande en statut PENDING ───────────────────
            $order = Order::create([
                'client_id'        => $client->id,
                'coupon_id'        => $cart->coupon_id,
                'status'           => OrderStatus::Pending,  // ← En attente de paiement
                'subtotal'         => $subtotal,
                'discount_amount'  => $discount,
                'shipping_amount'  => $shipping,
                'tax_rate'         => $taxRate,
                'tax_amount'       => $taxAmount,
                'total'            => $total,
                'shipping_address' => [
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'address'     => $data['address'],
                    'city'        => $data['city'],
                    'postal_code' => $data['postal_code'],
                    'country'     => $data['country'] ?? 'France',
                    'phone'       => $data['phone'],
                ],
                'shipping_method'  => 'standard',
                'client_notes'     => $data['notes'] ?? null,
            ]);

            // ── Créer les lignes de commande ──────────────────────────
            // NOTE : pas de décrémentation stock ici — uniquement après paiement
            foreach ($cart->items as $item) {
                $product = $item->product;

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'product_sku'  => $product->sku,
                    'unit_price'   => $product->price,
                    'quantity'     => $item->quantity,
                    'total'        => $product->price * $item->quantity,
                    'thumbnail'    => $product->thumbnail,
                ]);
            }

            // ── Créer la facture en statut DRAFT ──────────────────────
            $dueDate = now()->addDays((int) Setting::get('invoice_due_days', 30));
            $invoice = Invoice::create([
                'client_id'       => $client->id,
                'order_id'        => $order->id,
                'status'          => InvoiceStatus::Draft,   // ← Brouillon jusqu'au paiement
                'type'            => 'order',
                'client_snapshot' => [
                    'name'        => $client->full_name,
                    'email'       => $client->email,
                    'phone'       => $client->phone,
                    'address'     => $data['address']      ?? $client->address,
                    'city'        => $data['city']         ?? $client->city,
                    'postal_code' => $data['postal_code']  ?? $client->postal_code,
                    'country'     => $data['country']      ?? $client->country ?? 'France',
                ],
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'amount_paid'     => 0,
                'amount_due'      => $total,
                'issue_date'      => today(),
                'due_date'        => $dueDate,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item->product_name,
                    'unit_price'  => $item->unit_price,
                    'quantity'    => $item->quantity,
                    'total'       => $item->total,
                ]);
            }

            // ── Notifier l'admin (nouvelle commande en attente) ───────
            $admin = \App\Models\User::admin()->first();
            if ($admin) {
                \App\Models\AppNotification::create([
                    'user_id'  => $admin->id,
                    'type'     => 'order_new',
                    'title'    => 'Nouvelle commande (en attente)',
                    'message'  => "{$order->order_number} — {$client->full_name} — " . number_format($total, 2, ',', ' ') . '€',
                    'link'     => route('admin.commandes.show', $order),
                    'severity' => 'info',
                    'icon'     => 'PhShoppingBag',
                ]);
            }

            // ── NE PAS encore : vider le panier, décrémenter stock, envoyer email ──
            // Tout ça sera fait dans PaymentController::handleOrderPayment()
            // après confirmation du paiement Stripe ou PayPal

            DB::commit();

            // ── Rediriger vers la page de PAIEMENT ────────────────────
            return redirect()->route('payment.show', [
                'type' => 'order',
                'id'   => $order->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Checkout error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Une erreur est survenue. Veuillez réessayer.');
        }
    }

    // ── Page de confirmation (après paiement validé) ──────────────────
    public function confirmation(Order $order): Response|RedirectResponse
    {
        // Sécurité : la commande doit appartenir au client connecté
        if (auth()->check() && $order->client?->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items', 'client']);

        return Inertia::render('Public/OrderConfirmation', [
            'order' => [
                'id'              => $order->id,
                'order_number'    => $order->order_number,
                'status'          => $order->status->value,
                'status_label'    => $order->status->label(),
                'subtotal'        => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount'      => $order->tax_amount,
                'total'           => $order->total,
                'shipping_address'=> $order->shipping_address,
                'created_at'      => $order->created_at->locale('fr')->isoFormat('D MMMM YYYY à HH:mm'),
                'client'          => [
                    'full_name' => $order->client->full_name,
                    'email'     => $order->client->email,
                ],
                'items' => $order->items->map(fn($item) => [
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'unit_price'   => $item->unit_price,
                    'total'        => $item->total,
                    'thumbnail'    => $item->thumbnail
                        ? \Illuminate\Support\Facades\Storage::url($item->thumbnail)
                        : null,
                ]),
            ],
        ]);
    }

    // ── Helper panier ─────────────────────────────────────────────────
    private function getCart(): ?Cart
    {
        if (auth()->check()) {
            return Cart::where('user_id', auth()->id())->with('items.product')->first();
        }

        $sessionId = session()->get('cart_session_id');
        if (! $sessionId) return null;

        return Cart::where('session_id', $sessionId)->with('items.product')->first();
    }
}