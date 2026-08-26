<?php

namespace App\Repository;

use App\Entity\HolidayTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<HolidayTable>
 */
class HolidayTableServiceRepository extends HolidayTableRepository
{
    /**
     * @return list<HolidayTable>
     */
    public function findForUserAccount(int $userId, int $siteId, int $localeId): array
    {
        return $this->createQueryBuilder('holiday_table')
            ->leftJoin('holiday_table.holidaytablerecipes', 'holiday_table_recipe')
            ->addSelect('holiday_table_recipe')
            ->andWhere('holiday_table.user = :user')
            ->andWhere('holiday_table.site = :site')
            ->andWhere('holiday_table.locale = :locale')
            ->setParameter('user', $userId)
            ->setParameter('site', $siteId)
            ->setParameter('locale', $localeId)
            ->orderBy('holiday_table.event_date', 'DESC')
            ->addOrderBy('holiday_table.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUserAccount(int $id, int $userId, int $siteId, int $localeId): ?HolidayTable
    {
        return $this->createQueryBuilder('holiday_table')
            ->leftJoin('holiday_table.holidaytablerecipes', 'holiday_table_recipe')
            ->addSelect('holiday_table_recipe')
            ->andWhere('holiday_table.id = :id')
            ->andWhere('holiday_table.user = :user')
            ->andWhere('holiday_table.site = :site')
            ->andWhere('holiday_table.locale = :locale')
            ->setParameter('id', $id)
            ->setParameter('user', $userId)
            ->setParameter('site', $siteId)
            ->setParameter('locale', $localeId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
