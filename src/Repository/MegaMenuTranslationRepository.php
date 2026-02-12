<?php
namespace App\Repository;
use App\Entity\MegaMenuTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MegaMenuTranslation>
*/
class MegaMenuTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MegaMenuTranslation::class);
    }
}