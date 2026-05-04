<?php
namespace App\Repository;
use App\Entity\FavoriteList;
use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
/**
 * @extends ServiceEntityRepository<FavoriteList>
*/
class FavoriteListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriteList::class);
    }
    public function loadFavoriteRecipeIds($userId, $siteId, $localeId): array
    {

        $query = $this->createQueryBuilder('s')
            ->select('DISTINCT s.recipe_id')
            ->innerJoin(
                Recipe::class,
                'r',
                'WITH',
                'r.id = s.recipe_id'
            )
            ->addSelect('r')
            ->innerJoin('r.recipetranslations', 't')
            ->addSelect('t')
            ->where('s.user_id = :userId')
            ->andWhere('r.site = :site')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.is_active = :is_active')
            ->andWhere('t.publish = :publish')
            ->setParameter('site', $siteId)
            ->setParameter('locale', $localeId)
            ->setParameter('userId', $userId)
            ->setParameter('is_active', 'Yes')
            ->setParameter('publish', 'Yes')
            ->orderBy('s.updated_at', 'DESC')
            ->setMaxResults(10)
            ->getQuery();

        $query->setHint(Query::HINT_REFRESH, true);
        $result =  $query->getScalarResult();
        return array_column($result, 'recipe_id');
    }
}
