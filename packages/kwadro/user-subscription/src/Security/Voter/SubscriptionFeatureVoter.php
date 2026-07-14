<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Security\Voter;

use Kwadro\UserSubscription\Model\SubscribableUserInterface;
use Kwadro\UserSubscription\Service\SubscriptionChecker;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, mixed> */
final class SubscriptionFeatureVoter extends Voter
{
    public const PREFIX = 'SUBSCRIPTION_FEATURE_';

    public function __construct(
        private SubscriptionChecker $subscriptionChecker,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, self::PREFIX);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof SubscribableUserInterface) {
            return false;
        }

        $feature = substr($attribute, strlen(self::PREFIX));

        return $this->subscriptionChecker->hasFeature($user, $feature);
    }
}
