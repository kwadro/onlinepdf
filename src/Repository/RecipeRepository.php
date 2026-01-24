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
       public function findOneByUrlKey($urlKey): ?Recipe
       {
           return $this->createQueryBuilder('t')
               ->andWhere('t.slug = :slug')
               ->andWhere('t.is_active = :is_active')
               ->setParameter('slug', $urlKey)
               ->setParameter('is_active', 'Yes')
               ->orderBy('t.id', 'ASC')
               ->setMaxResults(1)
               ->getQuery()
               ->getOneOrNullResult();
       }
       public function findByCategoryId($categoryId): array
       {
           return $this->createQueryBuilder('s')
               ->innerJoin('s.recipecategorys', 'c')
               ->andWhere('c.id = :recipeCategoryId')
               ->andWhere('s.is_active = :is_active')
               ->setParameter('recipeCategoryId', $categoryId)
               ->setParameter('is_active', 'Yes')
               ->orderBy('s.position', 'ASC')
               ->setMaxResults(10)
               ->getQuery()
               ->getResult();
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