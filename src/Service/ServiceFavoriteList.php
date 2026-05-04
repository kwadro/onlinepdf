<?php

namespace App\Service;

use App\Entity\FavoriteList;
use Doctrine\ORM\EntityManagerInterface;

readonly class ServiceFavoriteList
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function addRecipe($userId, $recipeId, $siteId, $localeId): bool
    {
        $existing = $this->entityManager->getRepository(FavoriteList::class)
            ->findOneBy([
                'user_id' => $userId,
                'recipe_id' => $recipeId,
                'site_id' => $siteId,
                'locale_id' => $localeId,
            ]);

        if ($existing) {
            return false;
        }

        $favorite = new FavoriteList();
        $favorite->setUserId($userId);
        $favorite->setRecipeId($recipeId);
        $favorite->setSiteId($siteId);
        $favorite->setLocaleId($localeId);

        $this->entityManager->persist($favorite);
        $this->entityManager->flush();
        return true;
    }

    public function removeRecipe($userId, $recipeId, $siteId, $localeId): bool
    {
        try {
            $existing = $this->entityManager->getRepository(FavoriteList::class)
                ->findOneBy([
                    'user_id' => $userId,
                    'recipe_id' => $recipeId,
                    'site_id' => $siteId,
                    'locale_id' => $localeId,
                ]);

            if ($existing) {
                $this->entityManager->remove($existing);
                $this->entityManager->flush();
            }
            return true;
        } catch (\Exception $exception) {
        }
        return false;
    }
}
