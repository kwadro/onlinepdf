<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\EmailMailboxSetting;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class MailboxSmtpTransportFactory
{
    public function createMailer(EmailMailboxSetting $mailbox): MailerInterface
    {
        return new Mailer($this->createTransport($mailbox));
    }

    public function createTransport(EmailMailboxSetting $mailbox): TransportInterface
    {
        return Transport::fromDsn($this->buildDsn($mailbox));
    }

    private function buildDsn(EmailMailboxSetting $mailbox): string
    {
        $username = trim((string) $mailbox->getBoxusername());
        $password = (string) $mailbox->getBoxpassword();

        if ($username === '' || $password === '') {
            throw new \InvalidArgumentException(sprintf(
                'SMTP credentials are not configured for mailbox "%s".',
                (string) $mailbox->getBoxname(),
            ));
        }

        $host = $this->resolveSmtpHost($mailbox);
        $port = $this->resolveSmtpPort($mailbox, $host);
        $encryption = strtolower(trim((string) $mailbox->getBoxencryption()));

        $scheme = ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp';
        $dsn = sprintf(
            '%s://%s:%s@%s:%d',
            $scheme,
            rawurlencode($username),
            rawurlencode($password),
            $host,
            $port,
        );

        $options = [];
        if ($scheme === 'smtp' && in_array($port, [25, 2525], true)) {
            $options[] = 'auto_tls=0';
        }

        if ($options !== []) {
            $dsn .= '?'.implode('&', $options);
        }

        return $dsn;
    }

    private function resolveSmtpHost(EmailMailboxSetting $mailbox): string
    {
        $host = trim((string) $mailbox->getBoxhost());
        $host = preg_replace('#^(imap|smtp|ssl|tls)://#i', '', $host) ?? $host;
        $host = ltrim($host, ':/');
        $host = strtolower($host);

        if (str_starts_with($host, 'imap.')) {
            $host = 'smtp.'.substr($host, 5);
        }

        if ($host === '' || !str_contains($host, '.')) {
            $username = strtolower((string) $mailbox->getBoxusername());
            if (str_ends_with($username, '@gmail.com') || str_ends_with($username, '@googlemail.com')) {
                return 'smtp.gmail.com';
            }
        }

        return match ($host) {
            '', 'gmail.com', 'googlemail.com' => 'smtp.gmail.com',
            default => $host,
        };
    }

    private function resolveSmtpPort(EmailMailboxSetting $mailbox, string $smtpHost): int
    {
        $imapPort = (int) ($mailbox->getBoxport() ?? 0);
        if (in_array($imapPort, [25, 465, 587, 2525], true)) {
            return $imapPort;
        }

        if (str_contains($smtpHost, 'adm.tools')) {
            return 2525;
        }

        $encryption = strtolower(trim((string) $mailbox->getBoxencryption()));

        return match ($encryption) {
            'ssl' => 465,
            'tls' => 587,
            default => 587,
        };
    }
}
