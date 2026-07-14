<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

use Kwadro\UserSubscription\Entity\Subscription;

final class PaymentGatewayRegistry
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    /** @param iterable<PaymentGatewayInterface> $gateways */
    public function __construct(iterable $gateways)
    {
        foreach ($gateways as $gateway) {
            $this->gateways[$gateway->getCode()] = $gateway;
        }
    }

    public function get(string $code): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$code])) {
            throw new \InvalidArgumentException(sprintf('Payment provider "%s" is not configured.', $code));
        }

        return $this->gateways[$code];
    }

    public function has(string $code): bool
    {
        return isset($this->gateways[$code]);
    }

    /** @return list<PaymentGatewayInterface> */
    public function all(): array
    {
        return array_values($this->gateways);
    }

    public function resolveForSubscription(Subscription $subscription, ?string $providerCode): PaymentGatewayInterface
    {
        $plan = $subscription->getPlan();
        if ($plan !== null && $plan->getPrice() === 0) {
            return $this->get('null');
        }

        if ($providerCode === null || $providerCode === '') {
            throw new \InvalidArgumentException('payment_method is required for paid plans.');
        }

        $gateway = $this->get($providerCode);
        if (!$gateway->supportsPlan($subscription)) {
            throw new \InvalidArgumentException(sprintf('Payment provider "%s" does not support this plan.', $providerCode));
        }

        return $gateway;
    }
}
