<?php
namespace App\Repository;
use App\Entity\MegaMenuSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MegaMenuSetting>
*/
class MegaMenuSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MegaMenuSetting::class);
    }
          public function findBySiteAndLocale($site, $locale)
             {
                 $query = $this->createQueryBuilder('s')
                     ->innerJoin('s.site', 'site')
                     ->innerJoin('s.translations', 't')
                     ->addSelect('t')
                     ->where('site = :site')
                     ->andWhere('t.locale = :locale')
                     ->andWhere('t.status = :status')
                     ->setParameter('site', $site)
                     ->setParameter('locale', $locale)
                     ->setParameter('status', 'Yes')
                     ->addOrderBy('CASE WHEN t.position IS NULL THEN 1 ELSE 0 END', 'ASC')
                     ->addOrderBy('t.position', 'ASC')
                     ->setMaxResults(10)
                     ->getQuery();

                 return $query->getResult();
             }
}
    //    /**
    //     * @return MegaMenuSetting[] Returns an array of MegaMenuSetting objects
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
    //    public function findOneBySomeField($value): ?MegaMenuSetting
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }