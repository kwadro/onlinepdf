<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Controller\Api;

use Kwadro\UserSubscription\Enum\PaymentProvider;
use Kwadro\UserSubscription\Model\SubscribableUserInterface;
use Kwadro\UserSubscription\Payment\PaymentGatewayRegistry;
use Kwadro\UserSubscription\Repository\SubscriptionPlanRepository;
use Kwadro\UserSubscription\Service\SubscriptionChecker;
use Kwadro\UserSubscription\Service\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/subscriptions')]
final class SubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionPlanRepository $planRepository,
        private SubscriptionManager $subscriptionManager,
        private SubscriptionChecker $subscriptionChecker,
        private PaymentGatewayRegistry $paymentGatewayRegistry,
    ) {
    }

    #[Route('/plans', name: 'kwadro_subscription_plans', methods: ['GET'])]
    public function plans(): JsonResponse
    {
        $plans = array_map(
            static fn ($plan) => [
                'code' => $plan->getCode(),
                'name' => $plan->getName(),
                'price' => $plan->getPrice(),
                'currency' => $plan->getCurrency(),
                'interval' => $plan->getInterval()->value,
                'features' => $plan->getFeatures(),
            ],
            $this->planRepository->findActivePlans(),
        );

        return $this->json(['plans' => $plans]);
    }

    #[Route('/payment-methods', name: 'kwadro_subscription_payment_methods', methods: ['GET'])]
    public function paymentMethods(): JsonResponse
    {
        $methods = array_map(
            fn ($gateway) => [
                'code' => $gateway->getCode(),
                'name' => match ($gateway->getCode()) {
                    PaymentProvider::Privat->value => PaymentProvider::Privat->label(),
                    PaymentProvider::Monobank->value => PaymentProvider::Monobank->label(),
                    default => $gateway->getCode(),
                },
            ],
            array_filter(
                $this->paymentGatewayRegistry->all(),
                static fn ($gateway) => $gateway->getCode() !== 'null',
            ),
        );

        return $this->json(['payment_methods' => array_values($methods)]);
    }

    #[Route('/me', name: 'kwadro_subscription_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(): JsonResponse
    {
        $user = $this->requireSubscribableUser();
        $subscription = $this->subscriptionChecker->getActiveSubscription($user);

        if ($subscription === null) {
            return $this->json(['subscription' => null]);
        }

        $plan = $subscription->getPlan();

        return $this->json([
            'subscription' => [
                'id' => $subscription->getId(),
                'status' => $subscription->getStatus()->value,
                'started_at' => $subscription->getStartedAt()->format(DATE_ATOM),
                'expires_at' => $subscription->getExpiresAt()?->format(DATE_ATOM),
                'cancelled_at' => $subscription->getCancelledAt()?->format(DATE_ATOM),
                'plan' => $plan ? [
                    'code' => $plan->getCode(),
                    'name' => $plan->getName(),
                    'features' => $plan->getFeatures(),
                ] : null,
            ],
        ]);
    }

    #[Route('/subscribe', name: 'kwadro_subscription_subscribe', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function subscribe(Request $request): JsonResponse
    {
        $user = $this->requireSubscribableUser();
        $payload = json_decode($request->getContent(), true) ?? [];
        $planCode = $payload['plan'] ?? $payload['plan_code'] ?? null;
        $paymentMethod = $payload['payment_method'] ?? $payload['payment_provider'] ?? null;

        if (!is_string($planCode) || $planCode === '') {
            return $this->json(['error' => 'plan is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $subscription = $this->subscriptionManager->subscribe(
                $user,
                $planCode,
                is_string($paymentMethod) ? $paymentMethod : null,
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\LogicException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        $checkout = $this->subscriptionManager->getCheckoutPayload($subscription);

        return $this->json([
            'subscription' => [
                'id' => $subscription->getId(),
                'status' => $subscription->getStatus()->value,
                'plan' => $subscription->getPlan()?->getCode(),
                'payment_method' => $subscription->getMetadata()['payment_provider'] ?? null,
                'checkout_url' => $checkout['checkout_url'] ?? null,
                'checkout_data' => $checkout['checkout_data'] ?? null,
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/cancel', name: 'kwadro_subscription_cancel', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function cancel(): JsonResponse
    {
        $user = $this->requireSubscribableUser();
        $subscription = $this->subscriptionChecker->getActiveSubscription($user);

        if ($subscription === null) {
            return $this->json(['error' => 'No active subscription found.'], Response::HTTP_NOT_FOUND);
        }

        $this->subscriptionManager->cancel($subscription);

        return $this->json(['status' => 'cancelled']);
    }

    private function requireSubscribableUser(): SubscribableUserInterface
    {
        $user = $this->getUser();
        if (!$user instanceof SubscribableUserInterface) {
            throw $this->createAccessDeniedException('Authenticated user must implement SubscribableUserInterface.');
        }

        return $user;
    }
}
