<?php
namespace App\Repository;
use App\Entity\RecipeAuthor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecipeAuthor>
*/
class RecipeAuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecipeAuthor::class);
    }
           public function  findAllBySiteAndLocale($site, $locale){
               return $this->createQueryBuilder('s')
                   ->setMaxResults(6)
                   ->getQuery()
                   ->getResult();
           }
       public function findOneBySiteAndLocale($site, $locale)
       {
       return $this->createQueryBuilder('s')
           ->setMaxResults(1)
           ->getQuery()
           ->getOneOrNullResult();
       }
}
    //    /**
    //     * @return RecipeAuthor[] Returns an array of RecipeAuthor objects
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
    //    public function findOneBySomeField($value): ?RecipeAuthor
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }