<?php

namespace App\service;

use Illuminate\Support\Facades\Http;

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
     *
     * @param string $reference
     * @return array|null
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
}
