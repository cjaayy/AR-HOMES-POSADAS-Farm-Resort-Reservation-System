<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    private string $secretKey;
    private string $publicKey;
    private string $webhookSecret;
    private string $apiUrl;

    public function __construct()
    {
        $this->secretKey = env('PAYMONGO_SECRET_KEY', '');
        $this->publicKey = env('PAYMONGO_PUBLIC_KEY', '');
        $this->webhookSecret = env('PAYMONGO_WEBHOOK_SECRET', '');
        $this->apiUrl = 'https://api.paymongo.com/v1';
    }

    public function getAuthHeader(): string
    {
        return 'Basic ' . base64_encode($this->secretKey . ':');
    }

    public function makeRequest(string $endpoint, string $method = 'GET', ?array $data = null): array
    {
        $url = $this->apiUrl . $endpoint;

        try {
            $request = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

            $response = match (strtoupper($method)) {
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url),
                default => $request->get($url),
            };

            $result = $response->json();
            $httpCode = $response->status();

            return [
                'success' => $response->successful(),
                'http_code' => $httpCode,
                'data' => $result,
                'curl_error' => null
            ];
        } catch (Exception $e) {
            Log::error('PayMongo Request Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'http_code' => 500,
                'data' => null,
                'curl_error' => $e->getMessage()
            ];
        }
    }

    public function createPaymentLink(int $amountInCentavos, string $description, string $remarks): array
    {
        $payload = [
            'data' => [
                'attributes' => [
                    'amount' => $amountInCentavos,
                    'description' => $description,
                    'remarks' => $remarks
                ]
            ]
        ];

        return $this->makeRequest('/links', 'POST', $payload);
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        if (empty($this->webhookSecret) || empty($signatureHeader)) {
            return false;
        }

        $parts = explode(',', $signatureHeader);
        $timestamp = '';
        $testSignature = '';
        $liveSignature = '';

        foreach ($parts as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            if ($key === 't') $timestamp = $value;
            if ($key === 'te') $testSignature = $value;
            if ($key === 'li') $liveSignature = $value;
        }

        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $this->webhookSecret);
        return hash_equals($expectedSignature, $testSignature) || hash_equals($expectedSignature, $liveSignature);
    }
}
