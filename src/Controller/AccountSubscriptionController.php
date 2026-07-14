<?php

declare(strict_types=1);

namespace App\Controller;

use Kwadro\UserSubscription\Entity\Subscription;
use Kwadro\UserSubscription\Enum\PaymentProvider;
use Kwadro\UserSubscription\Enum\SubscriptionStatus;
use Kwadro\UserSubscription\Model\SubscribableUserInterface;
use Kwadro\UserSubscription\Payment\PaymentGatewayRegistry;
use Kwadro\UserSubscription\Repository\SubscriptionPlanRepository;
use Kwadro\UserSubscription\Repository\SubscriptionRepository;
use Kwadro\UserSubscription\Service\SubscriptionChecker;
use Kwadro\UserSubscription\Service\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class AccountSubscriptionController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/{_locale}/account/plan', name: 'account_plan', methods: ['GET'])]
    public function plan(
        SubscriptionChecker $subscriptionChecker,
        SubscriptionManager $subscriptionManager,
        SubscriptionPlanRepository $planRepository,
        SubscriptionRepository $subscriptionRepository,
        PaymentGatewayRegistry $paymentGatewayRegistry,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof SubscribableUserInterface) {
            return $this->redirectToRoute('app_login');
        }

        $subscriptionManager->ensureFreePlan($user);

        $activeSubscription = $subscriptionChecker->getActiveSubscription($user);
        $pendingSubscription = $subscriptionRepository->findLatestPendingForUser($user);

        $paymentMethods = [];
        foreach ($paymentGatewayRegistry->all() as $gateway) {
            if ($gateway->getCode() === 'null') {
                continue;
            }

            $paymentMethods[] = [
                'code' => $gateway->getCode(),
                'name' => match ($gateway->getCode()) {
                    PaymentProvider::Privat->value => PaymentProvider::Privat->label(),
                    PaymentProvider::Monobank->value => PaymentProvider::Monobank->label(),
                    default => $gateway->getCode(),
                },
            ];
        }

        $allPlans = $planRepository->findActivePlans();
        $paidPlans = array_values(array_filter(
            $allPlans,
            static fn ($plan) => $plan->getPrice() > 0,
        ));
        $selectablePlans = $paidPlans;
        $showPlanForm = true;

        if ($pendingSubscription !== null) {
            $showPlanForm = false;
        } elseif ($activeSubscription !== null) {
            $currentPrice = $activeSubscription->getPlan()?->getPrice() ?? 0;
            $selectablePlans = array_values(array_filter(
                $paidPlans,
                static fn ($plan) => $plan->getPrice() > $currentPrice,
            ));
        }

        return $this->render('security/account/plan.html.twig', [
            'activeSubscription' => $activeSubscription,
            'pendingSubscription' => $pendingSubscription,
            'plans' => $allPlans,
            'selectablePlans' => $selectablePlans,
            'showPlanForm' => $showPlanForm && $selectablePlans !== [],
            'paymentMethods' => $paymentMethods,
        ]);
    }

    #[Route('/{_locale}/account/plan/subscribe', name: 'account_plan_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        SubscriptionManager $subscriptionManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof SubscribableUserInterface) {
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('account_plan_subscribe', $token))) {
            $this->addFlash('error', $this->translator->trans('subscription.csrf_error'));

            return $this->redirectToRoute('account_plan');
        }

        $planCode = (string) $request->request->get('plan', '');
        $paymentMethod = (string) $request->request->get('payment_method', '');

        if ($planCode === '') {
            $this->addFlash('error', $this->translator->trans('subscription.plan_required'));

            return $this->redirectToRoute('account_plan');
        }

        try {
            $subscription = $subscriptionManager->subscribe(
                $user,
                $planCode,
                $paymentMethod !== '' ? $paymentMethod : null,
            );
        } catch (\InvalidArgumentException|\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('account_plan');
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('account_plan');
        }

        if ($subscription->getStatus() === SubscriptionStatus::Active) {
            $this->addFlash('success', $this->translator->trans('subscription.activated'));

            return $this->redirectToRoute('account_plan');
        }

        if ($subscriptionManager->hasCheckout($subscription)) {
            return $this->redirectToRoute('account_plan_checkout', [
                'id' => $subscription->getId(),
            ]);
        }

        $this->addFlash('error', $this->translator->trans('subscription.payment_unavailable'));

        return $this->redirectToRoute('account_plan');
    }

    #[Route('/{_locale}/account/plan/checkout/{id}', name: 'account_plan_checkout', methods: ['GET'])]
    public function checkout(
        int $id,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionManager $subscriptionManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof SubscribableUserInterface) {
            return $this->redirectToRoute('app_login');
        }

        $subscription = $subscriptionRepository->find($id);
        if (!$this->isPendingSubscriptionOwnedByUser($subscription, $user)) {
            throw $this->createNotFoundException();
        }

        $checkout = $subscriptionManager->getCheckoutPayload($subscription);

        if (isset($checkout['checkout_url'], $checkout['checkout_data'])) {
            return $this->render('security/account/payment-redirect.html.twig', [
                'checkoutUrl' => $checkout['checkout_url'],
                'checkoutData' => $checkout['checkout_data'],
            ]);
        }

        if (isset($checkout['checkout_url'])) {
            return $this->redirect($checkout['checkout_url']);
        }

        $this->addFlash('error', $this->translator->trans('subscription.payment_unavailable'));

        return $this->redirectToRoute('account_plan');
    }

    #[Route('/{_locale}/account/plan/payment/success', name: 'account_plan_payment_success', methods: ['GET'])]
    public function paymentSuccess(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', $this->translator->trans('subscription.payment_success'));

        return $this->redirectToRoute('account_plan');
    }

    #[Route('/{_locale}/account/plan/pending/cancel', name: 'account_plan_pending_cancel', methods: ['POST'])]
    public function cancelPending(
        Request $request,
        SubscriptionManager $subscriptionManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof SubscribableUserInterface) {
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('account_plan_pending_cancel', $token))) {
            $this->addFlash('error', $this->translator->trans('subscription.csrf_error'));

            return $this->redirectToRoute('account_plan');
        }

        if ($subscriptionManager->cancelPending($user)) {
            $this->addFlash('success', $this->translator->trans('subscription.pending_cancelled'));
        }

        return $this->redirectToRoute('account_plan');
    }

    #[Route('/{_locale}/account/plan/cancel', name: 'account_plan_cancel', methods: ['POST'])]
    public function cancel(
        Request $request,
        SubscriptionChecker $subscriptionChecker,
        SubscriptionManager $subscriptionManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof SubscribableUserInterface) {
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('account_plan_cancel', $token))) {
            $this->addFlash('error', $this->translator->trans('subscription.csrf_error'));

            return $this->redirectToRoute('account_plan');
        }

        $subscription = $subscriptionChecker->getActiveSubscription($user);
        if ($subscription === null) {
            $this->addFlash('error', $this->translator->trans('subscription.no_active'));

            return $this->redirectToRoute('account_plan');
        }

        $subscriptionManager->cancel($subscription);
        $this->addFlash('success', $this->translator->trans('subscription.cancelled'));

        return $this->redirectToRoute('account_plan');
    }

    private function isPendingSubscriptionOwnedByUser(?Subscription $subscription, SubscribableUserInterface $user): bool
    {
        return $subscription !== null
            && $subscription->getStatus() === SubscriptionStatus::Pending
            && $subscription->getUser() === $user;
    }
}
