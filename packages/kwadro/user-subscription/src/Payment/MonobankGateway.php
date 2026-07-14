<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

use Kwadro\UserSubscription\Entity\Subscription;
use Kwadro\UserSubscription\Enum\PaymentProvider;

final class MonobankGateway implements PaymentGatewayInterface
{
    private const UAH_CURRENCY_CODE = 980;

    public function __construct(
        private MonobankClient $client,
        private string $redirectUrl,
        private string $webhookUrl,
        private int $invoiceValiditySeconds = 86400,
    ) {
    }

    public function getCode(): string
    {
        return PaymentProvider::Monobank->value;
    }

    public function supportsPlan(Subscription $subscription): bool
    {
        $plan = $subscription->getPlan();

        return $plan !== null && $plan->getPrice() > 0;
    }

    public function initiateCheckout(Subscription $subscription): array
    {
        if ($this->client->getToken() === '') {
            throw new \RuntimeException('Monobank token is not configured.');
        }

        $plan = $subscription->getPlan();
        if ($plan === null) {
            throw new \LogicException('Subscription plan is required for payment.');
        }

        $reference = $this->buildReference($subscription);
        $response = $this->client->createInvoice([
            'amount' => $plan->getPrice(),
            'ccy' => self::UAH_CURRENCY_CODE,
            'merchantPaymInfo' => [
                'reference' => $reference,
                'destination' => sprintf('Subscription: %s', $plan->getName()),
            ],
            'redirectUrl' => $this->redirectUrl,
            'webHookUrl' => $this->webhookUrl,
            'validity' => $this->invoiceValiditySeconds,
        ]);

        if (!isset($response['pageUrl'], $response['invoiceId'])) {
            $message = $response['errText'] ?? $response['errorDescription'] ?? 'Monobank invoice creation failed.';

            throw new \RuntimeException((string) $message);
        }

        return [
            'checkout_url' => (string) $response['pageUrl'],
            'external_id' => (string) $response['invoiceId'],
        ];
    }

    public function cancelExternalSubscription(Subscription $subscription): void
    {
        $externalId = $subscription->getExternalId();
        if ($externalId === null || $externalId === '') {
            return;
        }

        $this->client->cancelInvoice($externalId);
    }

    public function buildReference(Subscription $subscription): string
    {
        return sprintf('sub-%d', $subscription->getId());
    }

    public function parseReference(string $reference): ?int
    {
        if (!preg_match('/^sub-(\d+)$/', $reference, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public function getClient(): MonobankClient
    {
        return $this->client;
    }
}
