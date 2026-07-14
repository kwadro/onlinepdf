<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\EmailMailboxSetting;
use App\Entity\EmailMessage;
use App\Entity\EmailSenderFilter;
use App\Repository\EmailMailboxSettingRepository;
use App\Repository\EmailMessageRepository;
use App\Repository\EmailSenderFilterRepository;
use Doctrine\ORM\EntityManagerInterface;
use IMAP\Connection;
use Psr\Log\LoggerInterface;

final class EmailFetcher
{
    public function __construct(
        private EmailMailboxSettingRepository $mailboxRepository,
        private EmailSenderFilterRepository $senderFilterRepository,
        private EmailMessageRepository $emailMessageRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function isSupported(): bool
    {
        return function_exists('imap_open');
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function fetchAll(?int $mailboxId = null): array
    {
        if (!$this->isSupported()) {
            throw new \RuntimeException('PHP IMAP extension is required. Install php-imap on the server.');
        }

        $mailboxes = $mailboxId !== null
            ? array_filter([$this->mailboxRepository->find($mailboxId)])
            : $this->mailboxRepository->findActiveMailboxes();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($mailboxes as $mailbox) {
            if (!$mailbox instanceof EmailMailboxSetting) {
                continue;
            }

            try {
                $result = $this->fetchMailbox($mailbox);
                $imported += $result['imported'];
                $skipped += $result['skipped'];
            } catch (\Throwable $exception) {
                $message = sprintf('Mailbox #%d (%s): %s', (int) $mailbox->getId(), $mailbox->getBoxname(), $exception->getMessage());
                $errors[] = $message;
                $this->logger->error($message, ['exception' => $exception]);
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * @return array{imported: int, skipped: int}
     */
    private function fetchMailbox(EmailMailboxSetting $mailbox): array
    {
        $connection = $this->openConnection($mailbox);
        $imported = 0;
        $skipped = 0;

        try {
            $filters = $this->senderFilterRepository->findActiveForSite($mailbox->getSite());
            if ($filters === []) {
                return ['imported' => 0, 'skipped' => 0];
            }

            $minLastUid = $this->resolveMinLastUid($filters);
            $searchCriteria = $minLastUid > 0 ? sprintf('UID %d:*', $minLastUid + 1) : 'UNSEEN';
            $messageNumbers = imap_search($connection, $searchCriteria, SE_UID) ?: [];

            foreach ($messageNumbers as $uid) {
                $uid = (int) $uid;

                if ($this->emailMessageRepository->existsByMailboxAndUid($mailbox, $uid)) {
                    ++$skipped;
                    continue;
                }

                $messageNumber = imap_msgno($connection, $uid);
                if ($messageNumber === 0) {
                    ++$skipped;
                    continue;
                }

                $header = imap_headerinfo($connection, $messageNumber);
                if ($header === false) {
                    ++$skipped;
                    continue;
                }

                $fromMailbox = isset($header->from[0]) ? (string) ($header->from[0]->mailbox ?? '') : '';
                $fromHost = isset($header->from[0]) ? (string) ($header->from[0]->host ?? '') : '';
                $fromAddress = $fromMailbox !== '' ? strtolower($fromMailbox . '@' . $fromHost) : '';
                if ($fromAddress === '') {
                    ++$skipped;
                    continue;
                }

                $matchedFilter = $this->matchSenderFilter($filters, $fromAddress);
                if ($matchedFilter === null) {
                    ++$skipped;
                    continue;
                }

                $filterLastUid = $matchedFilter->getFilterlastUid() ?? 0;
                if ($uid <= $filterLastUid) {
                    ++$skipped;
                    continue;
                }

                $structure = imap_fetchstructure($connection, $uid, FT_UID);
                $bodies = $this->extractBodies($connection, $uid, $structure);
                $fromName = isset($header->from[0]->personal)
                    ? $this->decodeMimeHeader((string) $header->from[0]->personal)
                    : null;

                $message = (new EmailMessage())
                    ->setSite($mailbox->getSite())
                    ->setMailbox($mailbox)
                    ->setSenderFilter($matchedFilter)
                    ->setImapUid($uid)
                    ->setMessageId(isset($header->message_id) ? trim((string) $header->message_id) : null)
                    ->setFromAddress($fromAddress)
                    ->setFromName($fromName)
                    ->setRecipient($this->extractPrimaryRecipient($header))
                    ->setSubject(isset($header->subject) ? $this->decodeMimeHeader((string) $header->subject) : null)
                    ->setBodyHtml($bodies['html'])
                    ->setReceivedAt($this->resolveReceivedAt($header))
                    ->setIsSeen('No');

                $this->entityManager->persist($message);
                $matchedFilter->setFilterlastUid(max($filterLastUid, $uid));
                ++$imported;
            }

            $mailbox->setLastCheckedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        } finally {
            imap_close($connection);
        }

        return compact('imported', 'skipped');
    }

    /**
     * @param list<EmailSenderFilter> $filters
     */
    private function resolveMinLastUid(array $filters): int
    {
        $minLastUid = null;

        foreach ($filters as $filter) {
            $lastUid = $filter->getFilterlastUid() ?? 0;
            $minLastUid = $minLastUid === null ? $lastUid : min($minLastUid, $lastUid);
        }

        return $minLastUid ?? 0;
    }

    /**
     * @param resource|false $connection
     */
    private function openConnection(EmailMailboxSetting $mailbox)
    {
        $encryption = strtolower(trim((string) $mailbox->getBoxencryption()));
        $flags = match ($encryption) {
            'ssl' => '/imap/ssl/novalidate-cert',
            'tls' => '/imap/tls/novalidate-cert',
            'none' => '/imap/notls',
            default => '/imap/ssl/novalidate-cert',
        };

        $host = $this->resolveImapHost($mailbox);
        $port = $mailbox->getBoxport() ?? 993;
        $folder = $this->normalizeMailboxFolder((string) $mailbox->getBoxmailbox());

        $mailboxPath = sprintf('{%s:%d%s}%s', $host, $port, $flags, $folder);

        $connection = @imap_open(
            $mailboxPath,
            (string) $mailbox->getBoxusername(),
            (string) $mailbox->getBoxpassword(),
        );

        if ($connection === false) {
            throw new \RuntimeException(sprintf(
                'Can\'t open mailbox %s: %s',
                $mailboxPath,
                imap_last_error() ?: 'unknown error',
            ));
        }

        return $connection;
    }

    private function resolveImapHost(EmailMailboxSetting $mailbox): string
    {
        $host = trim((string) $mailbox->getBoxhost());
        $host = preg_replace('#^(imap|ssl|tls)://#i', '', $host) ?? $host;
        $host = ltrim($host, ':/');

        if ($host === '' || !str_contains($host, '.')) {
            $username = strtolower((string) $mailbox->getBoxusername());
            if (str_ends_with($username, '@gmail.com') || str_ends_with($username, '@googlemail.com')) {
                return 'imap.gmail.com';
            }
        }

        $host = strtolower($host);

        return match ($host) {
            'gmail.com', 'googlemail.com' => 'imap.gmail.com',
            default => $host !== '' ? $host : throw new \InvalidArgumentException(
                sprintf('IMAP host is not configured for mailbox "%s".', (string) $mailbox->getBoxname()),
            ),
        };
    }

    private function normalizeMailboxFolder(string $folder): string
    {
        $folder = trim($folder);

        return $folder === '' ? 'INBOX' : $folder;
    }

    /**
     * @param list<EmailSenderFilter> $filters
     */
    private function matchSenderFilter(array $filters, string $fromAddress): ?EmailSenderFilter
    {
        foreach ($filters as $filter) {
            $needle = strtolower(trim((string) $filter->getFiltersender()));
            if ($needle === '') {
                continue;
            }

            if ($this->addressMatches($needle, $fromAddress, (string) $filter->getMatchMode())) {
                return $filter;
            }
        }

        return null;
    }

    private function addressMatches(string $needle, string $address, string $matchMode): bool
    {
        return match (strtolower($matchMode)) {
            'contains' => str_contains($address, $needle),
            default => $address === $needle,
        };
    }

    private function extractPrimaryRecipient(object $header): ?string
    {
        if (!isset($header->to[0])) {
            return null;
        }

        $mailboxPart = (string) ($header->to[0]->mailbox ?? '');
        $hostPart = (string) ($header->to[0]->host ?? '');
        if ($mailboxPart === '' || $hostPart === '') {
            return null;
        }

        return strtolower($mailboxPart . '@' . $hostPart);
    }

    /**
     * @return array{html: ?string}
     */
    private function extractBodies(Connection $connection, int $uid, object|false $structure): array
    {
        $html = null;

        if ($structure === false) {
            return ['html' => null];
        }

        if (!isset($structure->parts)) {
            $body = imap_body($connection, $uid, FT_UID);
            if (!is_string($body)) {
                return ['html' => null];
            }

            $decoded = $this->decodeBody($body, (int) ($structure->encoding ?? 0));
            if (($structure->subtype ?? '') === 'HTML') {
                return ['html' => $decoded];
            }

            return ['html' => null];
        }

        foreach ($structure->parts as $index => $part) {
            $partNumber = (string) ($index + 1);
            $body = imap_fetchbody($connection, $uid, $partNumber, FT_UID);
            if (!is_string($body)) {
                continue;
            }

            $decoded = $this->decodeBody($body, (int) ($part->encoding ?? 0));
            $subtype = strtoupper((string) ($part->subtype ?? ''));

            if ($subtype === 'HTML' && $html === null) {
                $html = $decoded;
            }
        }

        return ['html' => $html];
    }

    private function decodeBody(string $body, int $encoding): string
    {
        $decoded = match ($encoding) {
            ENCBASE64 => base64_decode($body, true),
            ENCQUOTEDPRINTABLE => quoted_printable_decode($body),
            default => $body,
        };

        return is_string($decoded) ? trim($decoded) : trim($body);
    }

    private function decodeMimeHeader(string $value): string
    {
        $decoded = imap_mime_header_decode($value);
        if (!is_array($decoded)) {
            return $value;
        }

        $parts = array_map(static fn ($part) => $part->text ?? '', $decoded);

        return trim(implode('', $parts));
    }

    private function resolveReceivedAt(object $header): \DateTimeImmutable
    {
        if (isset($header->date)) {
            try {
                return new \DateTimeImmutable((string) $header->date);
            } catch (\Exception) {
            }
        }

        return new \DateTimeImmutable();
    }
}
