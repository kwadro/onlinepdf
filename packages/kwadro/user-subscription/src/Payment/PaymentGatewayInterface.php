<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

use Kwadro\UserSubscription\Entity\Subscription;

interface PaymentGatewayInterface
{
    public function getCode(): string;

    public function supportsPlan(Subscription $subscription): bool;

    /**
     * @return array{
     *     checkout_url?: string,
     *     external_id?: string,
     *     checkout_data?: array<string, string>
     * }
     */
    public function initiateCheckout(Subscription $subscription): array;

    public function cancelExternalSubscription(Subscription $subscription): void;
}
