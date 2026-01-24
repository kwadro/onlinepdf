<?php
namespace App\Repository;
use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
       public function findOneByUrlKey($urlKey, $site, $locale): ?Recipe
       {
           $query = $this->createQueryBuilder('s')
                ->innerJoin('s.translations', 't')
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
                ->innerJoin('s.translations', 't')
                ->addSelect('t')
                ->where('s.site = :site')
                ->andWhere('t.locale = :locale')
                ->innerJoin('s.recipecategorys', 'c')
                ->andWhere('c.id = :recipeCategoryId')
                ->andWhere('t.is_active = :is_active')
                ->setParameter('site', $site)
                ->setParameter('locale', $locale)
                ->setParameter('recipeCategoryId', $categoryId)
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