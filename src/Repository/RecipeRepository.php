<?php
namespace App\Repository;
use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
*/
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }
    public function findBySearchQuery($query, $site, $locale): array
    {
        $query = $this->createQueryBuilder('s')
            ->innerJoin('s.recipetranslations', 't')
            ->addSelect('t')
            ->where('s.site = :site')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.name LIKE :query')
            ->setParameter('site', $site)
            ->setParameter('locale', $locale)
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery();
            $query->setHint(Query::HINT_REFRESH, true);

            return $query->getResult();
    }

    public function findByCategoryAndAuthor($categoryIds, $authorIds, $site, $locale): array
    {
        $categoryIdsStr = $categoryIds ? implode(',', $categoryIds) : null;
        $query = $this->createQueryBuilder('s')
            ->innerJoin('s.recipetranslations', 't')
            ->addSelect('t')
            ->where('s.site = :site')
            ->andWhere('t.locale = :locale');
        if ($categoryIds !== null) {
            $query->innerJoin('s.recipecategorys', 'c')
                ->andWhere('c.id IN (:recipeCategoryIds)')
                ->setParameter('recipeCategoryIds', $categoryIdsStr);
        }
        if ($authorIds !== null) {
            $query->innerJoin('t.recipeauthor', 'w')
                ->andWhere('w.id IN (:recipeAuthorIds)')
                ->setParameter('recipeAuthorIds', $authorIds);
        }

        $query->andWhere('t.is_active = :is_active')
            ->setParameter('site', $site)
            ->setParameter('locale', $locale)
            ->setParameter('is_active', 'Yes')
            ->orderBy('s.position', 'ASC')
            ->setMaxResults(10)
            ->getQuery();
        $query->setHint(Query::HINT_REFRESH, true);
        $query = $query->getQuery();

        return $query->getResult();
    }

       public function findOneByUrlKey($urlKey, $site, $locale): ?Recipe
       {
           $query = $this->createQueryBuilder('s')
                ->innerJoin('s.recipetranslations', 't')
                ->addSelect('t')
                ->where('s.site = :site')
                ->andWhere('t.locale = :locale')
                ->andWhere('t.slug = :slug')
                ->andWhere('t.is_active = :is_active')
                ->setParameter('site', $site)
                ->setParameter('locale', $locale)
                ->setParameter('slug', $urlKey)
                ->setParameter('is_active', 'Yes')
                ->orderBy('s.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery();
           return $query->getOneOrNullResult();
       }
       public function findByCategoryId($categoryId, $site, $locale): array
       {
            $query = $this->createQueryBuilder('s')
                ->innerJoin('s.recipetranslations', 't')
                ->addSelect('t')
                ->where('s.site = :site')
                ->andWhere('t.locale = :locale');
                if ($categoryId) {
                    $query->innerJoin('s.recipecategorys', 'c')
                        ->andWhere('c.id = :recipeCategoryId')
                        ->setParameter('recipeCategoryId', $categoryId);
                }
                $query->andWhere('t.is_active = :is_active')
                ->setParameter('site', $site)
                ->setParameter('locale', $locale)
                ->setParameter('is_active', 'Yes')
                ->orderBy('s.position', 'ASC')
                ->setMaxResults(10)
                ->getQuery();
                $query = $query->getQuery();
            return $query->getResult();
       }
       public function findByAuthorId($authorId, $site, $locale): array
              {
                   $query = $this->createQueryBuilder('s')
                       ->innerJoin('s.recipetranslations', 't')
                       ->addSelect('t')
                       ->where('s.site = :site')
                       ->andWhere('t.locale = :locale')
                       ->innerJoin('t.recipeauthor', 'c')
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
    //    /**
    //     * @return Recipe[] Returns an array of Recipe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }
    //    public function findOneBySomeField($value): ?Recipe
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }