<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Controller\Api;

use Kwadro\UserSubscription\Payment\MonobankSignatureVerifier;
use Kwadro\UserSubscription\Service\SubscriptionPaymentHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook/payment')]
final class PaymentWebhookController extends AbstractController
{
    public function __construct(
        private SubscriptionPaymentHandler $paymentHandler,
        private MonobankSignatureVerifier $monobankSignatureVerifier,
        private bool $verifyMonobankSignature,
    ) {
    }

    #[Route('/privat', name: 'kwadro_payment_webhook_privat', methods: ['POST'])]
    public function privat(Request $request): Response
    {
        $data = (string) $request->request->get('data', '');
        $signature = (string) $request->request->get('signature', '');

        if ($data === '' || $signature === '') {
            return new Response('Missing payload', Response::HTTP_BAD_REQUEST);
        }

        $this->paymentHandler->handlePrivatCallback($data, $signature);

        return new Response('OK');
    }

    #[Route('/monobank', name: 'kwadro_payment_webhook_monobank', methods: ['POST'])]
    public function monobank(Request $request): Response
    {
        $body = $request->getContent();
        $signature = (string) $request->headers->get('X-Sign', '');

        if ($this->verifyMonobankSignature && !$this->monobankSignatureVerifier->verify($body, $signature)) {
            return new Response('Invalid signature', Response::HTTP_FORBIDDEN);
        }

        $this->paymentHandler->handleMonobankWebhook($body, $signature, false);

        return new Response('OK');
    }
}
