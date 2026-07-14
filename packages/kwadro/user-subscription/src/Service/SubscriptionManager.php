<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kwadro\UserSubscription\Entity\Subscription;
use Kwadro\UserSubscription\Entity\SubscriptionPlan;
use Kwadro\UserSubscription\Enum\SubscriptionStatus;
use Kwadro\UserSubscription\Event\SubscriptionActivatedEvent;
use Kwadro\UserSubscription\Event\SubscriptionCancelledEvent;
use Kwadro\UserSubscription\Event\SubscriptionExpiredEvent;
use Kwadro\UserSubscription\Model\SubscribableUserInterface;
use Kwadro\UserSubscription\Payment\PaymentGatewayRegistry;
use Kwadro\UserSubscription\Repository\SubscriptionPlanRepository;
use Kwadro\UserSubscription\Repository\SubscriptionRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SubscriptionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionPlanRepository $planRepository,
        private PaymentGatewayRegistry $paymentGatewayRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private bool $autoActivate,
        private string $defaultPlanCode,
    ) {
    }

    public function subscribe(
        SubscribableUserInterface $user,
        string $planCode,
        ?string $paymentMethod = null,
    ): Subscription {
        $plan = $this->planRepository->findOneByCode($planCode);
        if ($plan === null || !$plan->isActive()) {
            throw new \InvalidArgumentException(sprintf('Subscription plan "%s" not found or inactive.', $planCode));
        }

        $pending = $this->subscriptionRepository->findLatestPendingForUser($user);
        if ($pending !== null) {
            throw new \LogicException('You already have a pending payment. Complete or cancel it first.');
        }

        if ($plan->getPrice() === 0) {
            throw new \LogicException('Free plan cannot be activated manually.');
        }

        if ($this->subscriptionRepository->userHasEverHadPlan($user, $this->defaultPlanCode)
            && $plan->getCode() === $this->defaultPlanCode) {
            throw new \LogicException('Free plan can only be activated once.');
        }

        $metadata = [];
        $active = $this->subscriptionRepository->findActiveForUser($user);
        if ($active !== null) {
            $currentPlan = $active->getPlan();

            if ($currentPlan === null || $plan->getPrice() <= $currentPlan->getPrice()) {
                throw new \LogicException('You can only upgrade to a higher plan.');
            }

            $metadata['replaces_subscription_id'] = $active->getId();
            $this->cancel($active, false);
        }

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStatus(SubscriptionStatus::Pending);
        if ($metadata !== []) {
            $subscription->setMetadata($metadata);
        }

        $user->addSubscription($subscription);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $checkout = $this->initiatePayment($subscription, $plan, $paymentMethod);

        if (isset($checkout['external_id'])) {
            $subscription->setExternalId($checkout['external_id']);
        }

        if ($plan->getPrice() === 0 || ($this->autoActivate && !isset($checkout['checkout_url']))) {
            $this->activate($subscription);
        }

        $this->entityManager->flush();

        return $subscription;
    }

    /** @return array<string, mixed> */
    public function getCheckoutPayload(Subscription $subscription): array
    {
        return $subscription->getMetadata()['checkout'] ?? [];
    }

    public function hasCheckout(Subscription $subscription): bool
    {
        $checkout = $this->getCheckoutPayload($subscription);

        return isset($checkout['checkout_url']);
    }

    public function activate(Subscription $subscription): void
    {
        if ($subscription->getStatus() === SubscriptionStatus::Active) {
            return;
        }

        $plan = $subscription->getPlan();
        if ($plan === null) {
            throw new \LogicException('Subscription has no plan assigned.');
        }

        $now = new \DateTimeImmutable();
        $subscription
            ->setStatus(SubscriptionStatus::Active)
            ->setStartedAt($now)
            ->setExpiresAt($plan->getInterval()->addTo($now))
            ->setCancelledAt(null);

        $this->entityManager->flush();

        $user = $subscription->getUser();
        if ($user instanceof SubscribableUserInterface) {
            $this->cancelOtherActiveSubscriptions($user, $subscription);
        }

        $this->eventDispatcher->dispatch(new SubscriptionActivatedEvent($subscription));
    }

    public function cancel(Subscription $subscription, bool $immediate = false): void
    {
        if ($subscription->getStatus() === SubscriptionStatus::Cancelled) {
            return;
        }

        $provider = (string) ($subscription->getMetadata()['payment_provider'] ?? '');
        if ($provider !== '' && $this->paymentGatewayRegistry->has($provider)) {
            $this->paymentGatewayRegistry->get($provider)->cancelExternalSubscription($subscription);
        }

        $subscription
            ->setStatus(SubscriptionStatus::Cancelled)
            ->setCancelledAt(new \DateTimeImmutable());

        if ($immediate) {
            $subscription->setExpiresAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
        $this->eventDispatcher->dispatch(new SubscriptionCancelledEvent($subscription));
    }

    public function cancelPending(SubscribableUserInterface $user): bool
    {
        $pending = $this->subscriptionRepository->findLatestPendingForUser($user);
        if ($pending === null) {
            return false;
        }

        $this->failPendingPayment($pending);

        return true;
    }

    public function failPendingPayment(Subscription $subscription): void
    {
        if ($subscription->getStatus() !== SubscriptionStatus::Pending) {
            return;
        }

        $this->cancel($subscription, true);
        $this->restoreReplacedSubscription($subscription);
    }

    public function restoreReplacedSubscription(Subscription $subscription): bool
    {
        $replacedId = $subscription->getMetadata()['replaces_subscription_id'] ?? null;
        if (!is_int($replacedId) && !is_string($replacedId)) {
            return false;
        }

        $replaced = $this->subscriptionRepository->find((int) $replacedId);
        if ($replaced === null) {
            return false;
        }

        $now = new \DateTimeImmutable();
        if ($replaced->getExpiresAt() !== null && $replaced->getExpiresAt() <= $now) {
            return false;
        }

        if ($replaced->getStatus() === SubscriptionStatus::Active && $replaced->isCurrentlyActive()) {
            return true;
        }

        if ($replaced->getStatus() !== SubscriptionStatus::Cancelled) {
            return false;
        }

        $replaced
            ->setStatus(SubscriptionStatus::Active)
            ->setCancelledAt(null);

        $this->entityManager->flush();
        $this->eventDispatcher->dispatch(new SubscriptionActivatedEvent($replaced));

        return true;
    }

    public function changePlan(Subscription $subscription, string $planCode): Subscription
    {
        $plan = $this->planRepository->findOneByCode($planCode);
        if ($plan === null || !$plan->isActive()) {
            throw new \InvalidArgumentException(sprintf('Subscription plan "%s" not found or inactive.', $planCode));
        }

        $subscription->setPlan($plan);

        if ($subscription->isCurrentlyActive()) {
            $now = new \DateTimeImmutable();
            $subscription
                ->setRenewedAt($now)
                ->setExpiresAt($plan->getInterval()->addTo($now));
        }

        $this->entityManager->flush();

        return $subscription;
    }

    public function renew(Subscription $subscription): void
    {
        $plan = $subscription->getPlan();
        if ($plan === null) {
            throw new \LogicException('Subscription has no plan assigned.');
        }

        $base = $subscription->getExpiresAt() ?? new \DateTimeImmutable();
        if ($base < new \DateTimeImmutable()) {
            $base = new \DateTimeImmutable();
        }

        $subscription
            ->setStatus(SubscriptionStatus::Active)
            ->setRenewedAt(new \DateTimeImmutable())
            ->setExpiresAt($plan->getInterval()->addTo($base))
            ->setCancelledAt(null);

        $this->entityManager->flush();
        $this->eventDispatcher->dispatch(new SubscriptionActivatedEvent($subscription));
    }

    public function expireDueSubscriptions(?\DateTimeImmutable $now = null): int
    {
        $expiredCount = 0;
        $subscriptions = $this->subscriptionRepository->findExpiredActive($now);

        foreach ($subscriptions as $subscription) {
            $subscription->setStatus(SubscriptionStatus::Expired);
            $this->entityManager->flush();
            $this->eventDispatcher->dispatch(new SubscriptionExpiredEvent($subscription));
            ++$expiredCount;
        }

        return $expiredCount;
    }

    public function assignDefaultPlan(SubscribableUserInterface $user, ?string $defaultPlanCode = null): ?Subscription
    {
        return $this->ensureFreePlan($user, $defaultPlanCode);
    }

    public function ensureFreePlan(SubscribableUserInterface $user, ?string $planCode = null): ?Subscription
    {
        $planCode ??= $this->defaultPlanCode;

        if ($this->subscriptionRepository->userHasEverHadPlan($user, $planCode)) {
            return null;
        }

        if ($this->subscriptionRepository->findActiveForUser($user) !== null) {
            return null;
        }

        if ($this->subscriptionRepository->findLatestPendingForUser($user) !== null) {
            return null;
        }

        $plan = $this->planRepository->findOneByCode($planCode);
        if ($plan === null || !$plan->isActive() || $plan->getPrice() !== 0) {
            return null;
        }

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStatus(SubscriptionStatus::Pending);

        $user->addSubscription($subscription);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->activate($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    private function cancelOtherActiveSubscriptions(
        SubscribableUserInterface $user,
        Subscription $activatedSubscription,
    ): void {
        foreach ($this->subscriptionRepository->findAllActiveForUser($user) as $subscription) {
            if ($subscription->getId() === $activatedSubscription->getId()) {
                continue;
            }

            $this->cancel($subscription, true);
        }
    }

    /** @return array<string, mixed> */
    private function initiatePayment(
        Subscription $subscription,
        SubscriptionPlan $plan,
        ?string $paymentMethod,
    ): array {
        if ($plan->getPrice() === 0) {
            return [];
        }

        $gateway = $this->paymentGatewayRegistry->resolveForSubscription($subscription, $paymentMethod);
        $checkout = $gateway->initiateCheckout($subscription);

        $subscription->setMetadata(array_merge($subscription->getMetadata(), [
            'payment_provider' => $gateway->getCode(),
            'checkout' => $checkout,
        ]));

        return $checkout;
    }
}
