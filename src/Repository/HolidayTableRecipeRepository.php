<?php
namespace App\Repository;
use App\Entity\HolidayTableRecipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HolidayTableRecipe>
*/
class HolidayTableRecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HolidayTableRecipe::class);
    }
}
