<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    public function __construct(
        private Breadcrumbs $breadcrumbs
    ) {
    }
    #[Route('/{_locale}/collection/{slug}', name: 'collection_list')]
    public function list(Request $request, string $slug, RecipeRepository $recipeRepository,RecipeCategoryRepository $recipeCategoryRepository): Response
    {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->render('collection/list.html.twig', [
                'recipes' => [],
                'recipeCategory' => [],
                'breadcrumbs' => [],
            ]);
        }

        $recipeCategory = $recipeCategoryRepository->findOneByUrlKey($slug);
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByCategory($recipeCategory);
        $recipes = $recipeRepository->findByCategoryId($recipeCategory->getId(), $site->getId(), $localeObject->getId());

        return $this->render('collection/list.html.twig', [
            'recipes' => $recipes,
            'recipeCategory' => $recipeCategory,
            'breadcrumbs' => $breadCrumbs

        ]);
    }
    #[Route('/{_locale}/recipe/{urlKey}', name: 'catalog_show')]
    public function show(Request $request, string $urlKey, RecipeRepository $recipeRepository): Response
    {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');
        $recipe = $recipeRepository->findOneByUrlKey($urlKey, $site->getId(), $localeObject->getId());
        if (!$recipe) {
            throw $this->createNotFoundException();
        }
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByRecipe($recipe);

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'breadcrumbs' => $breadCrumbs,
        ]);
    }
}

