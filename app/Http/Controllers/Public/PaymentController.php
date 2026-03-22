<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Setting;
use App\Services\StripeService;
use App\Services\PaypalService;
use App\Services\BrevoService;
use App\Enums\OrderStatus;
use App\Enums\InvoiceStatus;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected StripeService  $stripe,
        protected PaypalService  $paypal,
        protected BrevoService   $brevo,
    ) {}

    // ══════════════════════════════════════════════════════════════════
    // ── STRIPE ────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Créer un PaymentIntent Stripe (AJAX depuis le frontend Vue).
     * Utilisé pour la boutique ET les acomptes RDV.
     */
    public function stripeCreateIntent(Request $request): JsonResponse
    {
        $request->validate([
            'type'   => 'required|in:order,appointment',
            'id'     => 'required|integer',
        ]);

        try {
            if ($request->type === 'order') {
                $model       = Order::findOrFail($request->id);
                $amountCents = StripeService::toCents($model->total);
                $description = "Commande {$model->order_number} — Patricia Braids";
                $metadata    = ['type' => 'order', 'order_id' => $model->id];
            } else {
                $model       = Appointment::findOrFail($request->id);
                $amountCents = StripeService::toCents($model->deposit_amount ?: $model->price);
                $description = "RDV {$model->reference} — {$model->service->name}";
                $metadata    = ['type' => 'appointment', 'appointment_id' => $model->id];
            }

            $intent = $this->stripe->createPaymentIntent(
                amountCents: $amountCents,
                description: $description,
                metadata:    $metadata
            );

            return response()->json([
                'client_secret'    => $intent->client_secret,
                'payment_intent_id'=> $intent->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Stripe createIntent error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Impossible d\'initialiser le paiement.'], 500);
        }
    }

    /**
     * Confirmer un paiement Stripe réussi côté frontend.
     * Appelé après que Stripe Elements confirme le paiement.
     */
    public function stripeConfirm(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'type'              => 'required|in:order,appointment',
            'id'                => 'required|integer',
        ]);

        try {
            $intent = $this->stripe->retrievePaymentIntent($request->payment_intent_id);

            if ($intent->status !== 'succeeded') {
                return response()->json(['error' => 'Paiement non confirmé.'], 400);
            }

            DB::beginTransaction();

            if ($request->type === 'order') {
                $this->handleOrderPayment(
                    orderId:         $request->id,
                    transactionId:   $intent->id,
                    amount:          $intent->amount / 100,
                    method:          PaymentMethod::Card,
                );
            } else {
                $this->handleAppointmentDeposit(
                    appointmentId:   $request->id,
                    transactionId:   $intent->id,
                    amount:          $intent->amount / 100,
                    method:          PaymentMethod::Card,
                );
            }

            DB::commit();

            return response()->json(['success' => true, 'redirect' => $this->getRedirectUrl($request->type, $request->id)]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Stripe confirm error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la confirmation du paiement.'], 500);
        }
    }

    /**
     * Webhook Stripe (asynchrone — fiable pour les échecs réseau).
     */
    public function stripeWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        $event = $this->stripe->validateWebhook($payload, $signature);

        if (! $event) {
            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->onStripeSuccess($event->data->object),
            'payment_intent.payment_failed' => $this->onStripeFailure($event->data->object),
            default => null,
        };

        return response('OK', 200);
    }

    private function onStripeSuccess(\Stripe\PaymentIntent $intent): void
    {
        $meta = $intent->metadata;

        DB::transaction(function () use ($intent, $meta) {
            if (($meta['type'] ?? '') === 'order') {
                $this->handleOrderPayment(
                    orderId:       (int) ($meta['order_id'] ?? 0),
                    transactionId: $intent->id,
                    amount:        $intent->amount / 100,
                    method:        PaymentMethod::Card,
                );
            } elseif (($meta['type'] ?? '') === 'appointment') {
                $this->handleAppointmentDeposit(
                    appointmentId: (int) ($meta['appointment_id'] ?? 0),
                    transactionId: $intent->id,
                    amount:        $intent->amount / 100,
                    method:        PaymentMethod::Card,
                );
            }
        });
    }

    private function onStripeFailure(\Stripe\PaymentIntent $intent): void
    {
        Log::warning('Stripe paiement échoué', [
            'intent_id' => $intent->id,
            'metadata'  => $intent->metadata->toArray(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ── PAYPAL ────────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Initier un paiement PayPal — redirige vers PayPal.
     */
    public function paypalCreate(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:order,appointment',
            'id'   => 'required|integer',
        ]);

        try {
            if ($request->type === 'order') {
                $model       = Order::findOrFail($request->id);
                $amount      = $model->total;
                $description = "Commande {$model->order_number} — Patricia Braids";
                $customId    = "order_{$model->id}";
            } else {
                $model       = Appointment::findOrFail($request->id);
                $amount      = $model->deposit_amount ?: $model->price;
                $description = "Acompte RDV {$model->reference}";
                $customId    = "appointment_{$model->id}";
            }

            $returnUrl = route('payment.paypal.capture', [
                'type' => $request->type,
                'id'   => $request->id,
            ]);
            $cancelUrl = route('payment.cancel', [
                'type' => $request->type,
                'id'   => $request->id,
            ]);

            $order       = $this->paypal->createOrder($amount, $description, $returnUrl, $cancelUrl, $customId);
            $approvalUrl = $this->paypal->getApprovalUrl($order);

            if (! $approvalUrl) {
                throw new \RuntimeException('URL PayPal introuvable.');
            }

            // Si AJAX → retourner l'URL, sinon rediriger
            if ($request->wantsJson()) {
                return response()->json(['approval_url' => $approvalUrl, 'order_id' => $order['id']]);
            }

            return redirect($approvalUrl);

        } catch (\Throwable $e) {
            Log::error('PayPal createOrder error', ['error' => $e->getMessage()]);
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Impossible d\'initier le paiement PayPal.'], 500);
            }
            return back()->with('error', 'Impossible d\'initier le paiement PayPal.');
        }
    }

    /**
     * Capturer le paiement après retour de PayPal.
     */
    public function paypalCapture(Request $request, string $type, int $id): RedirectResponse
    {
        $request->validate(['token' => 'required|string']); // PayPal order ID

        try {
            $order = $this->paypal->captureOrder($request->token);

            if (! $this->paypal->isOrderCompleted($order)) {
                return redirect($this->getRedirectUrl($type, $id))
                    ->with('error', 'Paiement PayPal non complété.');
            }

            // Extraire les infos de capture
            $capture     = $order['purchase_units'][0]['payments']['captures'][0] ?? null;
            $amount      = (float) ($capture['amount']['value'] ?? 0);
            $captureId   = $capture['id'] ?? $request->token;

            DB::transaction(function () use ($type, $id, $captureId, $amount) {
                if ($type === 'order') {
                    $this->handleOrderPayment(
                        orderId:       $id,
                        transactionId: $captureId,
                        amount:        $amount,
                        method:        PaymentMethod::Paypal,
                    );
                } else {
                    $this->handleAppointmentDeposit(
                        appointmentId: $id,
                        transactionId: $captureId,
                        amount:        $amount,
                        method:        PaymentMethod::Paypal,
                    );
                }
            });

            return redirect($this->getRedirectUrl($type, $id))
                ->with('success', 'Paiement PayPal confirmé !');

        } catch (\Throwable $e) {
            Log::error('PayPal capture error', ['error' => $e->getMessage()]);
            return redirect($this->getRedirectUrl($type, $id))
                ->with('error', 'Erreur lors de la capture PayPal : ' . $e->getMessage());
        }
    }

    /**
     * PayPal webhook (IPN / événements).
     */
    public function paypalWebhook(Request $request)
    {
        // Validation basique
        $body = $request->getContent();
        Log::info('PayPal webhook reçu', ['event_type' => $request->input('event_type')]);

        // Traitement selon le type d'événement
        $eventType = $request->input('event_type');

        if ($eventType === 'CHECKOUT.ORDER.APPROVED') {
            // Géré via paypalCapture (redirect)
        } elseif ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource  = $request->input('resource');
            $customId  = $resource['custom_id']  ?? '';
            $captureId = $resource['id']          ?? '';
            $amount    = (float) ($resource['amount']['value'] ?? 0);

            if (str_starts_with($customId, 'order_')) {
                $orderId = (int) substr($customId, 6);
                DB::transaction(fn() => $this->handleOrderPayment($orderId, $captureId, $amount, PaymentMethod::Paypal));
            } elseif (str_starts_with($customId, 'appointment_')) {
                $apptId = (int) substr($customId, 12);
                DB::transaction(fn() => $this->handleAppointmentDeposit($apptId, $captureId, $amount, PaymentMethod::Paypal));
            }
        }

        return response('OK', 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // ── PAGES VUE ─────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Page de paiement (Stripe Elements ou PayPal selon choix).
     */
    public function show(Request $request, string $type, int $id): Response|RedirectResponse
    {
        abort_if(! in_array($type, ['order', 'appointment']), 404);

        if ($type === 'order') {
            $model = Order::with('items', 'client')->findOrFail($id);
            $amount      = $model->total;
            $description = "Commande {$model->order_number}";
            $reference   = $model->order_number;
            $cancelRoute = route('cart.index');
        } else {
            $model = Appointment::with('service', 'client')->findOrFail($id);
            $amount      = $model->deposit_amount ?: $model->price;
            $description = "RDV — {$model->service->name}";
            $reference   = $model->reference;
            $cancelRoute = route('booking.services');
        }

        // Déjà payé ?
        if ($type === 'order' && $model->status !== OrderStatus::Pending) {
            return redirect(route('checkout.confirmation', $id));
        }
        if ($type === 'appointment' && $model->deposit_paid ?? false) {
            return redirect(route('home'))->with('info', 'Ce rendez-vous est déjà réglé.');
        }

        return Inertia::render('Public/Payment', [
            'type'        => $type,
            'id'          => $id,
            'amount'      => $amount,
            'description' => $description,
            'reference'   => $reference,
            'cancelRoute' => $cancelRoute,
            'stripeKey'   => config('services.stripe.key'),
            'paypalClientId' => config(
                'paypal.' . config('paypal.mode') . '.client_id'
            ),
        ]);
    }

    /**
     * Page de succès générique.
     */
    public function success(Request $request, string $type, int $id): Response
    {
        $data = [];

        if ($type === 'order') {
            $model  = Order::with('items', 'client')->findOrFail($id);
            $data   = [
                'reference'  => $model->order_number,
                'total'      => $model->total,
                'email'      => $model->client->email,
                'type'       => 'order',
            ];
        } else {
            $model = Appointment::with('service', 'client')->findOrFail($id);
            $data  = [
                'reference'  => $model->reference,
                'total'      => $model->deposit_amount ?: $model->price,
                'email'      => $model->client->email,
                'type'       => 'appointment',
                'service'    => $model->service->name,
                'date'       => $model->date,
                'start_time' => $model->start_time,
            ];
        }

        return Inertia::render('Public/PaymentSuccess', ['data' => $data]);
    }

    /**
     * Page d'annulation.
     */
    public function cancel(string $type, int $id): Response
    {
        return Inertia::render('Public/PaymentCancel', [
            'type' => $type,
            'id'   => $id,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ── HELPERS PRIVÉS ────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Marquer une commande comme payée + créer le Payment + mettre à jour la facture.
     */
    // private function handleOrderPayment(
    //     int           $orderId,
    //     string        $transactionId,
    //     float         $amount,
    //     PaymentMethod $method
    // ): void {
    //     $order = Order::find($orderId);
    //     if (! $order) return;

    //     // Idempotence — ne pas traiter deux fois
    //     if (Payment::where('transaction_id', $transactionId)->exists()) return;

    //     // Mettre à jour la commande
    //     $order->update(['status' => OrderStatus::Processing]);

    //     // Trouver ou créer la facture
    //     $invoice = Invoice::where('order_id', $orderId)->first();
    //     if ($invoice) {
    //         $invoice->update([
    //             'status'      => InvoiceStatus::Paid,
    //             'amount_paid' => $amount,
    //             'amount_due'  => 0,
    //             'paid_at'     => now(),
    //         ]);
    //     }

    //     // Créer le paiement
    //     Payment::create([
    //         'invoice_id'     => $invoice?->id,
    //         'client_id'      => $order->client_id,
    //         'amount'         => $amount,
    //         'method'         => $method,
    //         'transaction_id' => $transactionId,
    //         'status'         => 'completed',
    //         'paid_at'        => now(),
    //         'notes'          => "Paiement commande {$order->order_number}",
    //     ]);

    //     // Notification admin
    //     $this->notifyAdmin(
    //         type:    'order_paid',
    //         title:   'Commande payée',
    //         message: "{$order->order_number} — " . number_format($amount, 2, ',', ' ') . '€',
    //         link:    route('admin.commandes.show', $order),
    //         icon:    'PhShoppingBag',
    //         severity:'success',
    //     );

    //     // Email confirmation client
    //     try {
    //         $html = view('emails.order-confirmation', [
    //             'order'  => $order->load('items'),
    //             'client' => $order->client,
    //             'total'  => $amount,
    //         ])->render();

    //         $this->brevo->send(
    //             toEmail:     $order->client->email,
    //             toName:      $order->client->full_name,
    //             subject:     "Paiement confirmé — {$order->order_number}",
    //             htmlContent: $html,
    //         );
    //     } catch (\Throwable) {}
    // }
    private function handleOrderPayment(
        int           $orderId,
        string        $transactionId,
        float         $amount,
        PaymentMethod $method
    ): void {
        $order = Order::with('items.product', 'client')->find($orderId);
        if (! $order) return;
    
        // Idempotence — ne pas traiter deux fois le même paiement
        if (Payment::where('transaction_id', $transactionId)->exists()) return;
    
        // Mettre à jour le statut commande
        $order->update(['status' => OrderStatus::Processing]);
    
        // Mettre à jour la facture
        $invoice = Invoice::where('order_id', $orderId)->first();
        if ($invoice) {
            $invoice->update([
                'status'      => InvoiceStatus::Paid,
                'amount_paid' => $amount,
                'amount_due'  => 0,
                'paid_at'     => now(),
            ]);
        }
    
        // Créer le paiement
        Payment::create([
            'invoice_id'     => $invoice?->id,
            'client_id'      => $order->client_id,
            'amount'         => $amount,
            'method'         => $method,
            'transaction_id' => $transactionId,
            'status'         => 'completed',
            'paid_at'        => now(),
            'notes'          => "Paiement commande {$order->order_number}",
        ]);
    
        // ── Décrémenter le stock APRÈS paiement confirmé ──────────────────
        foreach ($order->items as $item) {
            if ($item->product && $item->product->track_stock) {
                $item->product->decrement('stock',       $item->quantity);
                $item->product->increment('sales_count', $item->quantity);
            }
        }
    
        // ── Utiliser le coupon ────────────────────────────────────────────
        if ($order->coupon_id) {
            \App\Models\Coupon::find($order->coupon_id)?->increment('uses_count');
        }
    
        // ── Vider le panier du client ─────────────────────────────────────
        $cart = \App\Models\Cart::where('user_id', $order->client?->user_id)->first();
        if ($cart) {
            $cart->items()->delete();
            $cart->update([
                'coupon_id'       => null,
                'coupon_code'     => null,
                'discount_amount' => 0,
            ]);
        }
    
        // ── Notifier l'admin ──────────────────────────────────────────────
        $this->notifyAdmin(
            type:     'order_paid',
            title:    'Commande payée',
            message:  "{$order->order_number} — " . number_format($amount, 2, ',', ' ') . '€',
            link:     route('admin.commandes.show', $order),
            icon:     'PhShoppingBag',
            severity: 'success',
        );
    
        // ── Email de confirmation ─────────────────────────────────────────
        try {
            $html = view('emails.order-confirmation', [
                'order'  => $order->load('items'),
                'client' => $order->client,
                'total'  => $amount,
            ])->render();
    
            $this->brevo->send(
                toEmail:     $order->client->email,
                toName:      $order->client->full_name,
                subject:     "Paiement confirmé — {$order->order_number}",
                htmlContent: $html,
            );
        } catch (\Throwable) {}
    }

    /**
     * Marquer un acompte RDV comme payé.
     */
    private function handleAppointmentDeposit(
        int           $appointmentId,
        string        $transactionId,
        float         $amount,
        PaymentMethod $method
    ): void {
        $appointment = Appointment::with('client', 'service')->find($appointmentId);
        if (! $appointment) return;

        if (Payment::where('transaction_id', $transactionId)->exists()) return;

        $appointment->update([
            'deposit_paid'   => true,
            'deposit_paid_at'=> now(),
            'status'         => AppointmentStatus::Confirmed,
        ]);

        // Créer le paiement
        $invoice = Invoice::where('appointment_id', $appointmentId)->first();
        Payment::create([
            'invoice_id'     => $invoice?->id,
            'client_id'      => $appointment->client_id,
            'amount'         => $amount,
            'method'         => $method,
            'transaction_id' => $transactionId,
            'status'         => 'completed',
            'paid_at'        => now(),
            'notes'          => "Acompte RDV {$appointment->reference}",
        ]);

        // Notif admin
        $this->notifyAdmin(
            type:    'appointment_deposit',
            title:   'Acompte reçu',
            message: "{$appointment->reference} — {$appointment->client->full_name} — " . number_format($amount, 2, ',', ' ') . '€',
            link:    route('admin.rendez-vous.show', $appointment),
            icon:    'PhCalendarCheck',
            severity:'success',
        );

        // Email de confirmation au client
        try {
            $html = view('emails.appointment-confirmation', [
                'appointment' => $appointment,
                'client'      => $appointment->client,
                'service'     => $appointment->service,
                'deposit_paid'=> true,
            ])->render();

            $this->brevo->send(
                toEmail:     $appointment->client->email,
                toName:      $appointment->client->full_name,
                subject:     "Acompte confirmé — RDV {$appointment->reference}",
                htmlContent: $html,
            );
        } catch (\Throwable) {}
    }

    private function notifyAdmin(
        string $type, string $title, string $message,
        string $link, string $icon, string $severity
    ): void {
        $admin = \App\Models\User::admin()->first();
        if ($admin) {
            \App\Models\AppNotification::create([
                'user_id'  => $admin->id,
                'type'     => $type,
                'title'    => $title,
                'message'  => $message,
                'link'     => $link,
                'severity' => $severity,
                'icon'     => $icon,
            ]);
        }
    }

    private function getRedirectUrl(string $type, int $id): string
    {
        return $type === 'order'
            ? route('payment.success', ['type' => 'order', 'id' => $id])
            : route('payment.success', ['type' => 'appointment', 'id' => $id]);
    }
}