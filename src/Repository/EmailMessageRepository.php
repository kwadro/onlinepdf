<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailMailbox;
use App\Entity\EmailMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailMessage> */
class EmailMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailMessage::class);
    }

    public function existsByMailboxAndUid(EmailMailbox $mailbox, int $uid): bool
    {
        return $this->count([
            'mailbox' => $mailbox,
            'imap_uid' => $uid,
        ]) > 0;
    }
}
