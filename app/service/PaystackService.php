<?php

namespace App\service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private $publicKey;
    private $secretKey;
    private $baseUrl;

    public function __construct()
    {
        $this->publicKey = config('paystack.public_key');
        $this->secretKey = config('paystack.secret_key');
        $this->baseUrl = rtrim(config('paystack.base_url'), '/'); // remove trailing slash
    }

    /**
     * Verify a payment using the transaction reference.
     */
    public function verifyPayment(string $reference): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Cache-Control' => 'no-cache',
        ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Initialize a payment with Paystack.
     *
     * @param string $email
     * @param int $amount Amount in Naira
     * @param string|null $callbackUrl
     * @param array $metadata
     * @return array|null
     */
    public function initializePayment(string $email, int $amount, ?string $callbackUrl = null, array $metadata = []): ?array
    {
        $payload = [
            'email' => $email,
            'amount' => $amount * 100, // Paystack expects amount in kobo
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];

        Log::debug('Paystack initialize payload', $payload);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/initialize", $payload);

        Log::debug('Paystack initialize raw response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'base' => $this->baseUrl,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'status' => false,
            'message' => 'Request failed',
            'http_status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
