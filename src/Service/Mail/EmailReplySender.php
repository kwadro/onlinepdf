<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\EmailMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class EmailReplySender
{
    public function __construct(
        private readonly MailboxSmtpTransportFactory $mailboxSmtpTransportFactory,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \InvalidArgumentException
     */
    public function send(EmailMessage $originalMessage, string $to, string $subject, string $htmlBody): void
    {
        $mailbox = $originalMessage->getMailbox();
        if ($mailbox === null) {
            throw new \InvalidArgumentException('This message has no mailbox configured for SMTP sending.');
        }

        if (strtolower(trim((string) $mailbox->getBoxactive())) === 'no') {
            throw new \InvalidArgumentException('The mailbox used by this message is inactive.');
        }

        $fromAddress = $this->resolveFromAddress($originalMessage);
        if ($fromAddress === null) {
            throw new \InvalidArgumentException('Unable to resolve sender address for reply.');
        }

        $email = (new Email())
            ->from($fromAddress)
            ->to($to)
            ->subject($subject)
            ->html($htmlBody);

        if ($originalMessage->getMessageId()) {
            $email->getHeaders()->addTextHeader('In-Reply-To', $originalMessage->getMessageId());
            $email->getHeaders()->addTextHeader('References', $originalMessage->getMessageId());
        }

        $mailer = $this->mailboxSmtpTransportFactory->createMailer($mailbox);
        $mailer->send($email);
    }

    private function resolveFromAddress(EmailMessage $message): ?Address
    {
        $recipient = trim((string) $message->getRecipient());
        if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return new Address($recipient);
        }

        $mailboxUsername = trim((string) $message->getMailbox()?->getBoxusername());
        if ($mailboxUsername !== '' && filter_var($mailboxUsername, FILTER_VALIDATE_EMAIL)) {
            return new Address($mailboxUsername);
        }

        return null;
    }
}
