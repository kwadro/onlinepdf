<?php
namespace App\Repository;
use App\Entity\RecipeCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecipeCategory>
*/
class RecipeCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecipeCategory::class);
    }
    public function findOneByUrlKey($urlKey): ?RecipeCategory
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $urlKey)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultItem(): ?RecipeCategory
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.parent IS NULL')
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function  findAllBySiteAndLocale($site, $locale){
        return $this->createQueryBuilder('s')
            ->andWhere('s.parent = :parent')
            ->setParameter('parent', 1)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();
    }
}
    //    /**
    //     * @return RecipeCategory[] Returns an array of RecipeCategory objects
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
    //    public function findOneBySomeField($value): ?RecipeCategory
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
