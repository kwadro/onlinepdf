<?php
namespace App\Repository;
use App\Entity\FacebookSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FacebookSetting>
*/
class FacebookSettingServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FacebookSetting::class);
    }
       public function  findOneByEntityId($id, $site, $locale){
           $query = $this->createQueryBuilder('s')
               ->andWhere('s.locale = :locale')
               ->andWhere('s.site = :site')
               ->andWhere('s.id = :id')
               ->setParameter('site', $site)
               ->setParameter('locale', $locale)
               ->setParameter('id', $id)
               ->setMaxResults(1)
               ->getQuery();
           $query->setHint(Query::HINT_REFRESH, true);
           return $query->getOneOrNullResult();
       }
}
