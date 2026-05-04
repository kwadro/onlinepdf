<?php

namespace App\Controller;

use App\Repository\RecipeServiceRepository;
use App\Repository\FavoriteListRepository;

use App\Service\ServiceFavoriteList;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FavoriteListController extends AbstractController
{
    #[Route('/{_locale}/favoriteList', name: 'account_wishlist_page')]
    public function favoriteList(
        Request $request,
        FavoriteListRepository $wishlistRepository,
        RecipeServiceRepository $recipeServiceRepository
    ) {
        if ($user = $this->getUser()) {
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');

            if (!$site || !$localeObject) {
                return $this->render('homepage/index.html.twig', [
                    'recipes' => [],
                    'breadcrumbs' => [],
                ]);
            }

            $favoriteRecipeIds = $wishlistRepository->loadFavoriteRecipeIds(
                $user->getId(),
                $site->getId(),
                $localeObject->getId()
            );

            $favoriteRecipes = $recipeServiceRepository->loadItemsByIds(
                $favoriteRecipeIds,
                $site->getId(),
                $localeObject->getId()
            );
            $map = array_flip($favoriteRecipeIds);
            usort($favoriteRecipes, function ($a, $b) use ($map) {
                return $map[$a->getId()] <=> $map[$b->getId()];
            });

            return $this->render('recipe/favoritelist.html.twig', [
                'favoriteIds' => $favoriteRecipeIds,
                'recipes' => $favoriteRecipes,
                'newIds' => [],
                'user' => $user,
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    /**
     * @throws ORMException
     */
    #[Route('/{_locale}/addItem', name: 'account_wishlist_add')]
    public function addFavoriteItem(
        Request $request,
        ServiceFavoriteList $favoriteListService
    ) {
        if ( ($user= $this->getUser()) && $request->isXmlHttpRequest()) {
            $site = $request->attributes->get('site');
            $siteId = $site?->getId();
            $localeObject = $request->attributes->get('localeObject');
            $localeId = $localeObject?->getId();
            $params = $request->request->all();
            $favoriteListService->addRecipe(
                $user->getId(),
                $params['id'],
                $siteId,
                $localeId
            );
            return $this->json([
                'success' => true,
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Could you register before adding to the favorite list?'
            ]);
        }
    }

    #[Route('/{_locale}/removeItem', name: 'account_wishlist_remove')]
    public function removeFavoriteItem(
        Request $request,
        ServiceFavoriteList $favoriteListService
    ) {
        if ( ($user= $this->getUser()) && $request->isXmlHttpRequest()) {
            $site = $request->attributes->get('site');
            $siteId = $site?->getId();
            $localeObject = $request->attributes->get('localeObject');
            $localeId = $localeObject?->getId();
            $params = $request->request->all();
            $favoriteListService->removeRecipe(
                $user->getId(),
                $params['id'],
                $siteId,
                $localeId
            );
            return $this->json([
                'success' => true,
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Could you register before adding to the favorite list?'
            ]);
        }
    }
}
