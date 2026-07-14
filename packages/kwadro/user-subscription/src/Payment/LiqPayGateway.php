<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

use Kwadro\UserSubscription\Entity\Subscription;
use Kwadro\UserSubscription\Enum\PaymentProvider;

final class LiqPayGateway implements PaymentGatewayInterface
{
    /**
     * Client-Server checkout endpoint (same for API v3 and v7).
     *
     * @see https://www.liqpay.ua/doc
     */
    private const CHECKOUT_URL = 'https://www.liqpay.ua/api/3/checkout';

    public function __construct(
        private string $publicKey,
        private string $privateKey,
        private string $resultUrl,
        private string $serverUrl,
        private bool $sandbox = false,
        private int $apiVersion = 7,
    ) {
    }

    public function getCode(): string
    {
        return PaymentProvider::Privat->value;
    }

    public function supportsPlan(Subscription $subscription): bool
    {
        $plan = $subscription->getPlan();

        return $plan !== null && $plan->getPrice() > 0;
    }

    public function initiateCheckout(Subscription $subscription): array
    {
        if ($this->publicKey === '' || $this->privateKey === '') {
            throw new \RuntimeException('LiqPay keys are not configured.');
        }

        $plan = $subscription->getPlan();
        if ($plan === null) {
            throw new \LogicException('Subscription plan is required for payment.');
        }

        $orderId = $this->buildOrderId($subscription);
        $signer = new LiqPaySigner($this->privateKey);

        $payload = [
            'version' => $this->apiVersion,
            'public_key' => $this->publicKey,
            'action' => 'pay',
            'amount' => round($plan->getPrice() / 100, 2),
            'currency' => $plan->getCurrency(),
            'description' => sprintf('Subscription: %s', $plan->getName()),
            'order_id' => $orderId,
            'result_url' => $this->resultUrl,
            'server_url' => $this->serverUrl,
        ];

        if ($this->sandbox) {
            $payload['sandbox'] = 1;
        }

        $signed = $signer->encodeAndSign($payload);

        return [
            'checkout_url' => self::CHECKOUT_URL,
            'external_id' => $orderId,
            'checkout_data' => $signed,
        ];
    }

    public function cancelExternalSubscription(Subscription $subscription): void
    {
        // One-time LiqPay payments do not require remote cancellation.
    }

    public function buildOrderId(Subscription $subscription): string
    {
        return sprintf('sub-%d', $subscription->getId());
    }

    public function parseOrderId(string $orderId): ?int
    {
        if (!preg_match('/^sub-(\d+)$/', $orderId, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public function getSigner(): LiqPaySigner
    {
        return new LiqPaySigner($this->privateKey);
    }
}
