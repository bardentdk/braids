<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Créer un PaymentIntent pour la boutique ou un acompte RDV.
     *
     * @param int    $amountCents  Montant en centimes (ex: 4990 = 49,90€)
     * @param string $description  Description lisible
     * @param array  $metadata     Données arbitraires (order_id, appointment_id…)
     */
    public function createPaymentIntent(
        int    $amountCents,
        string $description,
        array  $metadata = []
    ): PaymentIntent {
        return PaymentIntent::create([
            'amount'                    => $amountCents,
            'currency'                  => 'eur',
            'description'               => $description,
            'metadata'                  => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);
    }

    /**
     * Confirmer qu'un PaymentIntent est bien payé (depuis webhook).
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Rembourser un paiement (total ou partiel).
     */
    public function refund(string $paymentIntentId, ?int $amountCents = null): Refund
    {
        $params = ['payment_intent' => $paymentIntentId];
        if ($amountCents) $params['amount'] = $amountCents;

        return Refund::create($params);
    }

    /**
     * Valider la signature d'un webhook Stripe.
     */
    public function validateWebhook(string $payload, string $signature): ?\Stripe\Event
    {
        try {
            return Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature invalide : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convertir euros en centimes.
     */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}