<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Payment;

use Kwadro\UserSubscription\Entity\Subscription;

final class NullPaymentGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'null';
    }

    public function supportsPlan(Subscription $subscription): bool
    {
        return true;
    }

    public function initiateCheckout(Subscription $subscription): array
    {
        return [];
    }

    public function cancelExternalSubscription(Subscription $subscription): void
    {
    }
}
