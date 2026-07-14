<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailMailboxSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailMailboxSetting> */
class EmailMailboxSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailMailboxSetting::class);
    }

    /** @return list<EmailMailboxSetting> */
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
