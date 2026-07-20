<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailMailbox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailMailbox> */
class EmailMailboxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailMailbox::class);
    }

    /** @return list<EmailMailbox> */
    public function findActiveMailboxes(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.boxactive = :active')
            ->setParameter('active', 'Yes')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
