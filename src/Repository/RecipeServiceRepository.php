<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function loadItemsByIds(array $recentlyRecipesIds, $site, $locale)
    {
        $query = $this->createQueryBuilder('s')
            ->innerJoin('s.recipetranslations', 't')
            ->addSelect('t')
            ->where('s.site = :site')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.is_active = :is_active')
            ->andWhere('t.publish = :publish')
            ->andWhere('s.id IN (:recentlyRecipesIds)')
            ->setParameter('site', $site)
            ->setParameter('locale', $locale)
            ->setParameter('recentlyRecipesIds', $recentlyRecipesIds)
            ->setParameter('is_active', 'Yes')
            ->setParameter('publish', 'Yes')
            ->orderBy('s.position', 'ASC')
            ->setMaxResults(10)
            ->getQuery();
        $query->setHint(Query::HINT_REFRESH, true);
        return $query->getResult();
    }
    public function findByRecipeId($recipeId, $site, $locale)
    {
        $query = $this->createQueryBuilder('s')
            ->innerJoin('s.recipetranslations', 't')
            ->addSelect('t')
            ->where('s.site = :site')
            ->andWhere('s.id = :entity_id')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.is_active = :is_active')
            ->setParameter('site', $site)
            ->setParameter('entity_id', $recipeId)
            ->setParameter('locale', $locale)
            ->setParameter('is_active', 'Yes')
            ->orderBy('s.position', 'ASC')
            ->getQuery();
        $query->setHint(Query::HINT_REFRESH, true);
        return $query->getResult();
    }
    public function findByRecipeSlug($slug, $site, $locale)
    {
        $query = $this->createQueryBuilder('s')
            ->innerJoin('s.recipetranslations', 't')
            ->addSelect('t')
            ->where('s.site = :site')
            ->andWhere('t.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.is_active = :is_active')
            ->setParameter('site', $site)
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale)
            ->setParameter('is_active', 'Yes')
            ->orderBy('s.position', 'ASC')
            ->getQuery();
        $query->setHint(Query::HINT_REFRESH, true);
        return $query->getResult();
    }
    public function findByAuthorId($authorId, $site, $locale): array
    {
        $query = $this->createQueryBuilder('s')
            ->innerJoin('s.recipetranslations', 't')
            ->addSelect('t')
            ->where('s.site = :site')
            ->andWhere('t.locale = :locale')
            ->innerJoin('t.user', 'c')
            ->andWhere('c.id = :authorId')
            ->andWhere('t.is_active = :is_active')
            ->setParameter('site', $site)
            ->setParameter('locale', $locale)
            ->setParameter('authorId', $authorId)
            ->setParameter('is_active', 'Yes')
            ->orderBy('s.position', 'ASC')
            ->setMaxResults(10)
            ->getQuery();
        return $query->getResult();
    }
}
