<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailSenderFilterGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailSenderFilterGroup> */
class EmailSenderFilterGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailSenderFilterGroup::class);
    }

    /** @return list<EmailSenderFilterGroup> */
    public function findForEmailMenu(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.emailsenderfiltergroups', 'f')
            ->addSelect('f')
            ->andWhere('g.filtergroupactive = :active')
            ->setParameter('active', 'Yes')
            ->orderBy('g.filtergroupname', 'ASC')
            ->addOrderBy('f.filtername', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
