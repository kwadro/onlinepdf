<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Enum;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
