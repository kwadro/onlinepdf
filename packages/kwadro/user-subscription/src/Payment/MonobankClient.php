<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MonobankClient
{
    private const BASE_URL = 'https://api.monobank.ua';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $token,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /** @param array<string, mixed> $payload */
    public function createInvoice(array $payload): array
    {
        $response = $this->httpClient->request('POST', self::BASE_URL . '/api/merchant/invoice/create', [
            'headers' => [
                'X-Token' => $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return $response->toArray(false);
    }

    public function cancelInvoice(string $invoiceId): array
    {
        $response = $this->httpClient->request('POST', self::BASE_URL . '/api/merchant/invoice/cancel', [
            'headers' => [
                'X-Token' => $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'invoiceId' => $invoiceId,
            ],
        ]);

        return $response->toArray(false);
    }

    public function getPublicKey(): string
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . '/api/merchant/pubkey', [
            'headers' => [
                'X-Token' => $this->token,
            ],
        ]);

        $data = $response->toArray(false);

        return (string) ($data['key'] ?? '');
    }
}
