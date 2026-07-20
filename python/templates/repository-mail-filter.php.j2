<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailFilter;
use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EmailFilter> */
class EmailFilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailFilter::class);
    }

    /** @return list<EmailFilter> */
    public function findActiveForSite(?Site $site): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.filteractive = :active')
            ->setParameter('active', 'Yes')
            ->orderBy('f.id', 'ASC');

        if ($site !== null) {
            $qb
                ->andWhere('f.site = :site')
                ->setParameter('site', $site);
        }

        return $qb->getQuery()->getResult();
    }
}
