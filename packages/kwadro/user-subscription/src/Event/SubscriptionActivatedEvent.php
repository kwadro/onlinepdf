<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Event;

use Kwadro\UserSubscription\Entity\Subscription;
use Symfony\Contracts\EventDispatcher\Event;

final class SubscriptionActivatedEvent extends Event
{
    public function __construct(
        private readonly Subscription $subscription,
    ) {
    }

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }
}
