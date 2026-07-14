<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Service;

use Kwadro\UserSubscription\Entity\Subscription;
use Kwadro\UserSubscription\Model\SubscribableUserInterface;
use Kwadro\UserSubscription\Repository\SubscriptionRepository;

final class SubscriptionChecker
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
    ) {
    }

    public function getActiveSubscription(SubscribableUserInterface $user): ?Subscription
    {
        return $this->subscriptionRepository->findActiveForUser($user);
    }

    public function hasActiveSubscription(SubscribableUserInterface $user): bool
    {
        return $this->getActiveSubscription($user) !== null;
    }

    public function hasFeature(SubscribableUserInterface $user, string $feature): bool
    {
        $subscription = $this->getActiveSubscription($user);
        if ($subscription === null) {
            return false;
        }

        $plan = $subscription->getPlan();
        if ($plan === null) {
            return false;
        }

        return $plan->hasFeature($feature);
    }

    public function getPlanCode(SubscribableUserInterface $user): ?string
    {
        $subscription = $this->getActiveSubscription($user);

        return $subscription?->getPlan()?->getCode();
    }
}
