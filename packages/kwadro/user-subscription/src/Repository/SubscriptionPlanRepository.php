<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Kwadro\UserSubscription\Entity\SubscriptionPlan;

/** @extends ServiceEntityRepository<SubscriptionPlan> */
class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    public function findOneByCode(string $code): ?SubscriptionPlan
    {
        return $this->findOneBy(['code' => $code]);
    }

    /** @return list<SubscriptionPlan> */
    public function findActivePlans(): array
    {
        return $this->findBy(['active' => true], ['price' => 'ASC']);
    }
}
