<?php

namespace App\Services;

// ══════════════════════════════════════════════════════════════════════
// PaypalService — SANS package externe
// Utilise uniquement Http (Guzzle) de Laravel — compatible PHP 8.3 + Laravel 13
// API PayPal REST v2 directement
// ══════════════════════════════════════════════════════════════════════

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaypalService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $mode = config('paypal.mode', 'sandbox');

        $this->baseUrl      = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->clientId     = config("paypal.{$mode}.client_id");
        $this->clientSecret = config("paypal.{$mode}.client_secret");
    }

    // ── Token OAuth2 ─────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        return Cache::remember('paypal_access_token', 3000, function () {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                throw new \RuntimeException('PayPal auth failed : ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    private function http()
    {
        return Http::withToken($this->getAccessToken())
                   ->acceptJson()
                   ->contentType('application/json');
    }

    // ── Créer une commande PayPal ─────────────────────────────────────

    public function createOrder(
        float  $amount,
        string $description,
        string $returnUrl,
        string $cancelUrl,
        string $customId = ''
    ): array {
        $response = $this->http()->post("{$this->baseUrl}/v2/checkout/orders", [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value'         => number_format($amount, 2, '.', ''),
                ],
                'description' => $description,
                'custom_id'   => $customId,
            ]],
            'application_context' => [
                'brand_name'          => config('app.name', 'Patricia Braids'),
                'locale'              => 'fr-FR',
                'landing_page'        => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
                'return_url'          => $returnUrl,
                'cancel_url'          => $cancelUrl,
            ],
        ]);

        if ($response->failed()) {
            Log::error('PayPal createOrder failed', ['body' => $response->body()]);
            throw new \RuntimeException('Impossible de créer la commande PayPal.');
        }

        return $response->json();
    }

    public function getApprovalUrl(array $order): ?string
    {
        foreach ($order['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }

    // ── Capturer le paiement ──────────────────────────────────────────

    public function captureOrder(string $orderId): array
    {
        $response = $this->http()->post(
            "{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture",
            []
        );

        if ($response->failed()) {
            Log::error('PayPal captureOrder failed', [
                'order_id' => $orderId,
                'body'     => $response->body(),
            ]);
            throw new \RuntimeException('Impossible de capturer le paiement PayPal.');
        }

        return $response->json();
    }

    public function getOrder(string $orderId): array
    {
        $response = $this->http()->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

        if ($response->failed()) {
            throw new \RuntimeException('Commande PayPal introuvable.');
        }

        return $response->json();
    }

    public function isOrderCompleted(array $order): bool
    {
        return ($order['status'] ?? '') === 'COMPLETED';
    }

    // ── Remboursement ─────────────────────────────────────────────────

    public function refund(string $captureId, ?float $amount = null): array
    {
        $body = [];
        if ($amount) {
            $body['amount'] = [
                'currency_code' => 'EUR',
                'value'         => number_format($amount, 2, '.', ''),
            ];
        }

        $response = $this->http()->post(
            "{$this->baseUrl}/v2/payments/captures/{$captureId}/refund",
            $body
        );

        if ($response->failed()) {
            throw new \RuntimeException('Remboursement PayPal échoué.');
        }

        return $response->json();
    }
}