<?php

namespace App\Repository;

use App\Entity\RecipeView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class RecipeViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecipeView::class);
    }

    public function loadRecentlyViewedRecipeIds($userId): array
    {
        $query = $this->createQueryBuilder('s')
            ->select('s.recipe_id')
            ->where('s.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.updated_at', 'DESC')
            ->setMaxResults(10)
            ->getQuery();
        $query->setHint(Query::HINT_REFRESH, true);
        $result = $query->getResult();
        return array_unique(array_column($result, 'recipe_id'));
    }
}
