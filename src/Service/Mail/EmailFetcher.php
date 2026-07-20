<?php

declare(strict_types=1);

namespace App\Service\Mail;

use App\Entity\EmailFilter;
use App\Entity\EmailMailbox;
use App\Entity\EmailMailboxFolder;
use App\Entity\EmailMessage;
use App\Repository\EmailFilterGroupRepository;
use App\Repository\EmailFilterRepository;
use App\Repository\EmailMailboxFolderRepository;
use App\Repository\EmailMailboxRepository;
use App\Repository\EmailMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use IMAP\Connection;
use Psr\Log\LoggerInterface;

final class EmailFetcher
{
    public function __construct(
        private EmailMailboxRepository $mailboxRepository,
        private EmailFilterRepository $filterRepository,
        private EmailFilterGroupRepository $filterGroupRepository,
        private EmailMessageRepository $emailMessageRepository,
        private EntityManagerInterface $entityManager,
        private EmailMailboxFolderRepository $emailMailboxFolderRepository,
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
            if (!$mailbox instanceof EmailMailbox) {
                continue;
            }

            try {
                $result = $this->fetchMailbox($mailbox);
                $imported += $result['imported'];
                $skipped += $result['skipped'];
            } catch (\Throwable $exception) {
                $message = sprintf(
                    'Mailbox #%d (%s): %s',
                    (int)$mailbox->getId(),
                    $mailbox->getBoxname(),
                    $exception->getMessage()
                );
                $errors[] = $message;
                $this->logger->error($message, ['exception' => $exception]);
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * @return array{imported: int, skipped: int}
     */
    public function fetchMailbox(EmailMailbox $mailbox, $currentFolder = 'INBOX'): array
    {
        $availableFolders = [];
        $connection = $this->openConnection($mailbox, $currentFolder);
        $folders = $this->emailMailboxFolderRepository->findBy(['emailmailbox' => $mailbox->getId()]);
        // save folders only one time
        if (!$folders) {
            $host = $this->resolveImapHost($mailbox);
            $port = $mailbox->getBoxport() ?? 993;
            $flags = $this->getFlagByMailBox($mailbox);
            $patch = sprintf('{%s:%d%s}', $host, $port, $flags);
            $tempMailboxes = imap_getmailboxes(
                $connection,
                $patch,
                "*"
            );

            foreach ($tempMailboxes as $tempMailbox) {
                $folderName = imap_utf7_decode($tempMailbox->name);
                $folderName = str_replace($patch, '', $folderName);

                $folder = (new EmailMailboxFolder())
                    ->setEmailmailbox($mailbox)
                    ->setFolderName($folderName)
                    ->setFolderActive('Yes');
                $this->entityManager->persist($folder);
                $availableFolders[] = $folderName;
            }
            $this->entityManager->flush();
        } else {
            foreach ($folders as $folder) {
                $availableFolders[] = $folder->getFoldername();
            }
        }

        $imported = 0;
        $skipped = 0;
        if (!in_array($currentFolder, $availableFolders)) {
            return ['imported' => 0, 'skipped' => 0];
        }

        try {
            $filterGroups = $this->filterGroupRepository->findBy(['mailbox' => $mailbox->getId()]);
            if ($filterGroups === []) {
                return ['imported' => 0, 'skipped' => 0];
            }
            $filters = [];
            foreach ($filterGroups as $filterGroup) {
                foreach ($filterGroup->getEmailfilters() as $item) {
                    $filters[] = $item;
                }
            }


            if ($filters === []) {
                return ['imported' => 0, 'skipped' => 0];
            }

            $messageNumbers = imap_search($connection, 'ALL', SE_UID) ?: [];

            if ($currentFolder === 'INBOX') {
                $minLastUid = $this->resolveMinLastUid($filters);
                if ($minLastUid > 0) {
                    $messageNumbers = array_filter($messageNumbers, function ($messageNumber) use ($minLastUid) {
                        return $messageNumber > $minLastUid;
                    });
                }
            }

            foreach ($messageNumbers as $uid) {
//                echo $uid . PHP_EOL;
                $uid = (int)$uid;
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

                $recipient = $this->extractPrimaryRecipient($header);
                $fromMailbox = isset($header->from[0]) ? (string)($header->from[0]->mailbox ?? '') : '';
                $fromHost = isset($header->from[0]) ? (string)($header->from[0]->host ?? '') : '';
                $fromAddress = $fromMailbox !== '' ? strtolower($fromMailbox . '@' . $fromHost) : '';
                if ($fromAddress === '') {
                    ++$skipped;
                    continue;
                }
                $mailboxType = null;
                $matchedFilter = null;
                $filterLastUid = 0;
                if ($currentFolder === 'INBOX') {
                    $mailboxType = 'inbox';
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
                }
                if ($currentFolder === '[Gmail]/Sent Mail') {
                    $mailboxType = 'sent';
                    $matchedFilter = $this->matchSenderFilter($filters, $recipient);
                    if ($matchedFilter === null) {
                        ++$skipped;
                        continue;
                    }
                }

                $headers = imap_fetchheader($connection, $uid, FT_UID);
                $messageId = null;
                $res = preg_match('/^Message-ID:\s*(.+)$/mi', $headers, $messageIds);
                if ($res === 1) {
                    $messageId = $messageIds[1];
                }
                $res =preg_match('/^In-Reply-To:\s*(.+)$/mi', $headers, $inRepliesTo);
                $parentMessageId = $inReplyTo = null;
                if ($res === 1) {
                    $inReplyTo = trim($inRepliesTo[1]);

                    $parentMessage = $this->emailMessageRepository->findOneBy(
                        [
                            'mailbox' => $mailbox->getId(),
                            'message_id' => $inReplyTo
                        ]
                    );
                    $parentMessageId = (string)$parentMessage?->getId();
                }
                $res = preg_match('/^References:\s*(.+)$/mi', $headers, $referencesArr);
                $references = null;
                if ($res === 1) {
                    $references = $referencesArr[1];
                }

                $structure = imap_fetchstructure($connection, $uid, FT_UID);
                $bodies = $this->extractBodies($connection, $uid, $structure);
                $fromName = isset($header->from[0]->personal)
                    ? $this->decodeMimeHeader((string)$header->from[0]->personal)
                    : null;
                $mailboxFolderObject = $this->emailMailboxFolderRepository
                    ->findOneBy(['foldername' => $currentFolder, 'emailmailbox' => $mailbox->getId()]);


                $message = (new EmailMessage())
                    ->setSite($mailbox->getSite())
                    ->setMailbox($mailbox)
                    ->setMailboxtype($mailboxType)
                    ->setMailboxfolder($mailboxFolderObject)
                    ->setEmailfilter($matchedFilter)
                    ->setImapUid($uid)
                    ->setInReplyTo($inReplyTo)
                    ->setMailreferences($references)
                    ->setParentMessageId($parentMessageId)
                    ->setMessageId($messageId ? trim((string)$messageId) : null)
                    ->setFromAddress($fromAddress)
                    ->setFromName($fromName)
                    ->setRecipient($recipient)
                    ->setSubject(isset($header->subject) ? $this->decodeMimeHeader((string)$header->subject) : null)
                    ->setBodyHtml($bodies['html'])
                    ->setReceivedAt($this->resolveReceivedAt($header))
                    ->setIsSeen('No');

                $this->entityManager->persist($message);
                $this->entityManager->flush();
                if ($currentFolder === 'INBOX') {
                    $matchedFilter->setFilterlastUid(max($filterLastUid, $uid));
                }

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
     * @param list<EmailFilter> $filters
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

    private function getFlagByMailBox(EmailMailbox $mailbox): string
    {
        $encryption = strtolower(trim((string)$mailbox->getBoxencryption()));
        return match ($encryption) {
            'ssl' => '/imap/ssl/novalidate-cert',
            'tls' => '/imap/tls/novalidate-cert',
            'none' => '/imap/notls',
            default => '/imap/ssl/novalidate-cert',
        };
    }

    /**
     * @param EmailMailbox $mailbox
     * @param $folder
     * @return Connection
     */
    private function openConnection(EmailMailbox $mailbox, $folder)
    {
        $flags = $this->getFlagByMailBox($mailbox);

        $host = $this->resolveImapHost($mailbox);
        $port = $mailbox->getBoxport() ?? 993;
        // ??
        $folder = $this->normalizeMailboxFolder((string)$folder);
        $mailboxPath = sprintf('{%s:%d%s}%s', $host, $port, $flags, $folder);
        $connection = @imap_open(
            $mailboxPath,
            (string)$mailbox->getBoxusername(),
            (string)$mailbox->getBoxpassword(),
        );

        if ($connection === false) {
            throw new \RuntimeException(
                sprintf(
                    'Can\'t open mailbox %s: %s',
                    $mailboxPath,
                    imap_last_error() ?: 'unknown error',
                )
            );
        }

        return $connection;
    }

    private function resolveImapHost(EmailMailbox $mailbox): string
    {
        $host = trim((string)$mailbox->getBoxhost());
        $host = preg_replace('#^(imap|ssl|tls)://#i', '', $host) ?? $host;
        $host = ltrim($host, ':/');

        if ($host === '' || !str_contains($host, '.')) {
            $username = strtolower((string)$mailbox->getBoxusername());
            if (str_ends_with($username, '@gmail.com') || str_ends_with($username, '@googlemail.com')) {
                return 'imap.gmail.com';
            }
        }

        $host = strtolower($host);

        return match ($host) {
            'gmail.com', 'googlemail.com' => 'imap.gmail.com',
            default => $host !== '' ? $host : throw new \InvalidArgumentException(
                sprintf('IMAP host is not configured for mailbox "%s".', (string)$mailbox->getBoxname()),
            ),
        };
    }

    private function normalizeMailboxFolder(string $folder): string
    {
        $folder = trim($folder);

        return $folder === '' ? 'INBOX' : $folder;
    }

    /**
     * @param list<EmailFilter> $filters
     */
    private function matchSenderFilter(array $filters, string $fromAddress): ?EmailFilter
    {
        foreach ($filters as $filter) {
            $needle = strtolower(trim((string)$filter->getFilteremail()));
            if ($needle === '') {
                continue;
            }

            if ($this->addressMatches($needle, $fromAddress, (string)$filter->getMatchMode())) {
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

        $mailboxPart = (string)($header->to[0]->mailbox ?? '');
        $hostPart = (string)($header->to[0]->host ?? '');
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

            $decoded = $this->decodeBody($body, (int)($structure->encoding ?? 0));
            if (($structure->subtype ?? '') === 'HTML') {
                return ['html' => $decoded];
            }

            return ['html' => null];
        }

        foreach ($structure->parts as $index => $part) {
            $partNumber = (string)($index + 1);
            $body = imap_fetchbody($connection, $uid, $partNumber, FT_UID);
            if (!is_string($body)) {
                continue;
            }

            $decoded = $this->decodeBody($body, (int)($part->encoding ?? 0));
            $subtype = strtoupper((string)($part->subtype ?? ''));

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

        $parts = array_map(static fn($part) => $part->text ?? '', $decoded);

        return trim(implode('', $parts));
    }

    private function resolveReceivedAt(object $header): \DateTimeImmutable
    {
        if (isset($header->date)) {
            try {
                return new \DateTimeImmutable((string)$header->date);
            } catch (\Exception) {
            }
        }

        return new \DateTimeImmutable();
    }
}
