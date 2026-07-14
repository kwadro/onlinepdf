<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Enum;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function addTo(\DateTimeImmutable $from): \DateTimeImmutable
    {
        return match ($this) {
            self::Monthly => $from->modify('+1 month'),
            self::Yearly => $from->modify('+1 year'),
        };
    }
}
