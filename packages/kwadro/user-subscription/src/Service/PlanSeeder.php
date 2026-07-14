<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kwadro\UserSubscription\Entity\SubscriptionPlan;
use Kwadro\UserSubscription\Enum\BillingInterval;
use Kwadro\UserSubscription\Repository\SubscriptionPlanRepository;

final class PlanSeeder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionPlanRepository $planRepository,
        /** @var array<string, array<string, mixed>> */
        private array $defaultPlans,
    ) {
    }

    public function seed(bool $updateExisting = false): int
    {
        $count = 0;

        foreach ($this->defaultPlans as $code => $config) {
            $plan = $this->planRepository->findOneByCode($code);
            if ($plan === null) {
                $plan = (new SubscriptionPlan())->setCode($code);
                $this->entityManager->persist($plan);
                ++$count;
            } elseif (!$updateExisting) {
                continue;
            }

            $plan
                ->setName((string) $config['name'])
                ->setPrice((int) $config['price'])
                ->setCurrency((string) ($config['currency'] ?? 'UAH'))
                ->setInterval(BillingInterval::from((string) ($config['interval'] ?? 'monthly')))
                ->setActive((bool) ($config['active'] ?? true))
                ->setFeatures(array_values($config['features'] ?? []));
        }

        $this->entityManager->flush();

        return $count;
    }
}
