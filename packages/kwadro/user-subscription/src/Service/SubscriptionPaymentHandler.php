<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kwadro\UserSubscription\Entity\Subscription;
use Kwadro\UserSubscription\Payment\LiqPaySigner;
use Kwadro\UserSubscription\Payment\MonobankSignatureVerifier;
use Kwadro\UserSubscription\Repository\SubscriptionRepository;
use Psr\Log\LoggerInterface;

final class SubscriptionPaymentHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionManager $subscriptionManager,
        private LoggerInterface $logger,
        private ?string $liqPayPrivateKey = null,
        private ?MonobankSignatureVerifier $monobankSignatureVerifier = null,
    ) {
    }

    public function handlePrivatCallback(string $data, string $signature): bool
    {
        if ($this->liqPayPrivateKey === null || $this->liqPayPrivateKey === '') {
            $this->logger->error('LiqPay callback received but Privat provider is not configured.');

            return false;
        }

        $signer = new LiqPaySigner($this->liqPayPrivateKey);
        if (!$signer->verify($data, $signature)) {
            $this->logger->warning('Invalid LiqPay callback signature.');

            return false;
        }

        $payload = $signer->decode($data);
        $orderId = (string) ($payload['order_id'] ?? '');
        $status = (string) ($payload['status'] ?? '');

        $subscription = $this->findSubscriptionByReference($orderId);
        if ($subscription === null) {
            return false;
        }

        if (in_array($status, ['success', 'sandbox'], true)) {
            $this->subscriptionManager->activate($subscription);

            return true;
        }

        if (in_array($status, ['failure', 'error', 'reversed'], true)) {
            $this->subscriptionManager->failPendingPayment($subscription);

            return true;
        }

        return true;
    }

    public function handleMonobankWebhook(string $body, string $signature, bool $verifySignature = true): bool
    {
        if ($verifySignature && $this->monobankSignatureVerifier !== null) {
            if (!$this->monobankSignatureVerifier->verify($body, $signature)) {
                $this->logger->warning('Invalid Monobank webhook signature.');

                return false;
            }
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($body, true) ?? [];

        $reference = (string) ($payload['reference'] ?? $payload['merchantPaymInfo']['reference'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $invoiceId = (string) ($payload['invoiceId'] ?? '');

        $subscription = $this->findSubscriptionByReference($reference)
            ?? ($invoiceId !== '' ? $this->subscriptionRepository->findOneBy(['externalId' => $invoiceId]) : null);

        if (!$subscription instanceof Subscription) {
            $this->logger->warning('Monobank webhook for unknown subscription.', [
                'reference' => $reference,
                'invoiceId' => $invoiceId,
            ]);

            return false;
        }

        if ($status === 'success') {
            $this->subscriptionManager->activate($subscription);

            return true;
        }

        if (in_array($status, ['failure', 'expired', 'reversed'], true)) {
            $this->subscriptionManager->failPendingPayment($subscription);

            return true;
        }

        return true;
    }

    private function findSubscriptionByReference(string $reference): ?Subscription
    {
        $subscriptionId = $this->parseSubscriptionReference($reference);
        if ($subscriptionId === null) {
            $this->logger->warning('Payment callback with unknown reference.', ['reference' => $reference]);

            return null;
        }

        $subscription = $this->subscriptionRepository->find($subscriptionId);

        return $subscription instanceof Subscription ? $subscription : null;
    }

    private function parseSubscriptionReference(string $reference): ?int
    {
        if (!preg_match('/^sub-(\d+)$/', $reference, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
