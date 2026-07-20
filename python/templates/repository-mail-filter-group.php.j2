<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailFilterGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailFilterGroup> */
class EmailFilterGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailFilterGroup::class);
    }

    /** @return list<EmailFilterGroup> */
    public function findForEmailMenu(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.emailfilters', 'f')
            ->addSelect('f')
            ->andWhere('g.filtergroupactive = :active')
            ->setParameter('active', 'Yes')
            ->orderBy('g.filtergroupname', 'ASC')
            ->addOrderBy('f.filtername', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
