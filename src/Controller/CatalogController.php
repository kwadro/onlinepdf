<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\UserRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CatalogController extends AbstractController
{
    public function __construct(
        private Breadcrumbs $breadcrumbs
    ) {
    }

    #[Route('/{_locale}/catalog', name: 'catalog_list')]
    public function list(
        Request $request,
        RecipeRepository $recipeRepository,
        RecipeCategoryRepository $recipeCategoryRepository,
        UserRepository $userRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->render('catalog/list.html.twig', [
                'recipes' => [],
                'breadcrumbs' => [],
            ]);
        }
        $recipeCategories = $recipeCategoryRepository->findAllBySiteAndLocale($site->getId(), $localeObject->getId());

        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByCatalog();
        $recipes = $recipeRepository->findByCategoryId(null, $site->getId(), $localeObject->getId());
        $recipeAuthors = $userRepository->findAllBySiteAndLocale($site->getId(), $localeObject->getId());
        $filterAjaxUrl = $this->generateUrl('filter_ajax_data');
        $searchAjaxUrl = $this->generateUrl('search_ajax_data');
        $token = $csrfTokenManager->getToken('filter_form')->getValue();
        $tokenSearch = $csrfTokenManager->getToken('search_form')->getValue();

        return $this->render('catalog/list.html.twig', [
            'recipes' => $recipes,
            'breadcrumbs' => $breadCrumbs,
            'recipeCategories' => $recipeCategories,
            'recipeAuthors' => $recipeAuthors,
            'filterAjaxUrl' => $filterAjaxUrl,
            'searchAjaxUrl' => $searchAjaxUrl,
            'csrf_token' => $token,
            'csrf_token_search' => $tokenSearch,
        ]);
    }
}

