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