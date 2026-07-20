<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EmailMessage;
use App\Repository\EmailMessageRepository;
use App\Service\Mail\EmailReplySender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class EmailMessageReplyController extends AbstractController
{
    public function __construct(
        private readonly EmailMessageRepository $emailMessageRepository,
        private readonly EmailReplySender $emailReplySender,
    ) {
    }

    #[Route(
        '/admin/{_locale}/email-message/{id}/reply',
        name: 'admin_email_message_reply',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function reply(int $id, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('email_reply', (string) $request->request->get('_token'))) {
            return new JsonResponse(['message' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $emailMessage = $this->emailMessageRepository->find($id);
        if (!$emailMessage instanceof EmailMessage) {
            return new JsonResponse(['message' => 'Email not found.'], Response::HTTP_NOT_FOUND);
        }

        $to = trim((string) $request->request->get('to', ''));
        $subject = trim((string) $request->request->get('subject', ''));
        $body = trim((string) $request->request->get('body', ''));

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'Invalid recipient address.'], Response::HTTP_BAD_REQUEST);
        }

        if ($subject === '') {
            return new JsonResponse(['message' => 'Subject is required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($body === '') {
            return new JsonResponse(['message' => 'Message body is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->emailReplySender->send($emailMessage, $to, $subject, $body);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (TransportExceptionInterface) {
            return new JsonResponse(['message' => 'Unable to send email. Check mailer configuration.'], Response::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse(['message' => 'Email sent successfully.']);
    }
}
