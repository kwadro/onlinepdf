<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FavoriteListRepository;
use App\Repository\PopularsearchRepository;
use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeServiceRepository;
use App\Repository\RecipeViewRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsController]
class HomePageController extends AbstractController
{
    public function __construct(
        private Breadcrumbs $breadcrumbs
    ) {
    }

    #[Route('/{_locale}/search_page/{keyword}', name: 'search_page')]
    public function searchRecipes(
        Request $request,
        string $keyword,
        RecipeViewRepository $recipeViewRepository,
        RecipeServiceRepository $recipeServiceRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        FavoriteListRepository $favoriteListRepository
    ): Response {

        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->render('homepage/index.html.twig', [
                'recipes' => [],
                'breadcrumbs' => [],
            ]);
        }
        $recipes = $recipeServiceRepository->findBySearchQuery(
            $keyword,
            $site->getId(),
            $localeObject->getId()
        );
        $popularRecipes = $recipeServiceRepository->findPopularValues($site->getId(), $localeObject->getId());
        $recentlyRecipes = [];
        if($user = $this->getUser()) {
            $recentlyRecipesIds = $recipeViewRepository->loadRecentlyViewedRecipeIds($user->getId());
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $recentlyRecipes = $recipeServiceRepository->loadItemsByIds(
                $recentlyRecipesIds,
                $site->getId(),
                $localeObject->getId()
            );
        }
        $favoriteRecipeIds = [];
        if($user = $this->getUser()) {
            $favoriteRecipeIds = $favoriteListRepository->loadFavoriteRecipeIds(
                $user->getId(),
                $site->getId(),
                $localeObject->getId()
            );
        }
        $searchAjaxUrl = $this->generateUrl('search_ajax_data');
        $tokenSearch = $csrfTokenManager->getToken('search_form')->getValue();
        return $this->render('homepage/search-result-page.html.twig', [
            'recipes' => $recipes,
            'popularRecipes' => $popularRecipes,
            'recentlyRecipes' => $recentlyRecipes,
            'favoriteIds'=>$favoriteRecipeIds,
            'searchAjaxUrl' => $searchAjaxUrl,
            'keyword' => $keyword,
            'csrf_token_search' => $tokenSearch
        ]);
    }

    /**
     * @param Request $request
     * @param RecipeViewRepository $recipeViewRepository
     * @param RecipeServiceRepository $recipeServiceRepository
     * @param FavoriteListRepository $favoriteListRepository
     * @param PopularsearchRepository $popularSearchRepository
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @return Response
     */
    #[Route('/{_locale}/', name: 'homepage')]
    public function index(
        Request $request,
        RecipeViewRepository $recipeViewRepository,
        RecipeserviceRepository $recipeServiceRepository,
        FavoriteListRepository $favoriteListRepository,
        PopularsearchRepository $popularSearchRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->render('homepage/index.html.twig', [
                'recipes' => [],
                'breadcrumbs' => [],
            ]);
        }

        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByCatalog();
        $recipes = $recipeServiceRepository->findByCategoryId(null, $site->getId(), $localeObject->getId());
        $popularRecipes = $recipeServiceRepository->findPopularValues($site->getId(), $localeObject->getId());

        $recentlyRecipes = [];
        $recentlyRecipesIds = [];
        if($user = $this->getUser()) {
            $recentlyRecipesIds = $recipeViewRepository->loadRecentlyViewedRecipeIds($user->getId());
            $recentlyRecipes = $recipeServiceRepository->loadItemsByIds(
                $recentlyRecipesIds,
                $site->getId(),
                $localeObject->getId()
            );
            $map = array_flip($recentlyRecipesIds);
            usort($recentlyRecipes, function ($a, $b) use ($map) {
                return $map[$a->getId()] <=> $map[$b->getId()];
            });

        }

        $searchAjaxUrl = $this->generateUrl('search_ajax_data');
        $tokenSearch = $csrfTokenManager->getToken('search_form')->getValue();
        $popularSearchWords = $popularSearchRepository->findAllBySiteAndLocale($site->getId(), $localeObject->getId());
        $favoriteRecipeIds = [];
        if($user = $this->getUser()) {
            $favoriteRecipeIds = $favoriteListRepository->loadFavoriteRecipeIds(
                $user->getId(),
                $site->getId(),
                $localeObject->getId()
            );
        }
        return $this->render('homepage/index.html.twig', [
            'recipes' => $recipes,
            'popularRecipes' => $popularRecipes,
            'recentlyRecipesIds' => $recentlyRecipesIds,
            'recentlyRecipes' => $recentlyRecipes,
            'favoriteIds'=>$favoriteRecipeIds,
            'popularSearchWords' => $popularSearchWords,
            'breadcrumbs' => $breadCrumbs,
            'searchAjaxUrl' => $searchAjaxUrl,
            'csrf_token_search' => $tokenSearch,
            'typePage' => 'homepage'
        ]);
    }

    #[Route('/', name: 'default', locale: 'uk')]
    public function default(Request $request): Response
    {
        return $this->redirectToRoute('homepage');
    }
}
