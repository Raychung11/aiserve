<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillplzService
{
    private string $apiKey;
    private string $collectionId;
    private bool $sandbox;
    private string $xSignature;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey       = config('billplz.api_key', '');
        $this->collectionId = config('billplz.collection_id', '');
        $this->sandbox      = config('billplz.sandbox', true);
        $this->xSignature   = config('billplz.x_signature', '');
        $this->baseUrl      = $this->sandbox
            ? 'https://www.billplz-sandbox.com/api/v3'
            : 'https://www.billplz.com/api/v3';
    }

    /**
     * Create a Billplz bill (FPX payment).
     */
    public function createBill(array $data): array
    {
        $payload = [
            'collection_id'    => $data['collection_id'] ?? $this->collectionId,
            'email'            => $data['email'],
            'mobile'           => $data['phone'] ?? '',
            'name'             => $data['name'],
            'amount'           => (int) round($data['amount'] * 100), // in cents
            'description'      => $data['description'],
            'callback_url'     => $data['callback_url'] ?? route('billplz.callback'),
            'redirect_url'     => $data['redirect_url'] ?? route('billplz.redirect'),
            'reference_1_label'=> $data['ref_label'] ?? 'Reference',
            'reference_1'      => $data['reference'] ?? '',
        ];

        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->post("{$this->baseUrl}/bills", $payload);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('Billplz createBill failed', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'payload' => $payload,
            ]);

            return ['success' => false, 'error' => $response->json('error.message', 'Payment gateway error')];
        } catch (\Exception $e) {
            Log::error('Billplz exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get bill details from Billplz.
     */
    public function getBill(string $billId): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->get("{$this->baseUrl}/bills/{$billId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => 'Bill not found'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify Billplz webhook X-Signature.
     */
    public function verifyWebhook(array $data): bool
    {
        if (empty($this->xSignature)) {
            return false;
        }

        $source = collect($data)
            ->sortKeys()
            ->map(fn($v, $k) => "{$k}{$v}")
            ->implode('|');

        $computed = hash_hmac('sha256', $source, $this->xSignature);

        return hash_equals($computed, $data['x_signature'] ?? '');
    }

    /**
     * Create a subscription bill for a tenant.
     */
    public function createSubscriptionBill(
        string $tenantEmail,
        string $tenantName,
        string $tenantPhone,
        string $plan,
        float $amount,
        string $tenantId
    ): array {
        return $this->createBill([
            'email'       => $tenantEmail,
            'phone'       => $tenantPhone,
            'name'        => $tenantName,
            'amount'      => $amount,
            'description' => "STRHub AI — {$plan} Plan Subscription",
            'ref_label'   => 'Tenant ID',
            'reference'   => "TENANT-{$tenantId}",
            'redirect_url'=> route('subscription.callback', ['plan' => $plan]),
        ]);
    }
}
