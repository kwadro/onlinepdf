<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\RecipeCategory;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CatalogController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private Breadcrumbs $breadcrumbs,
    ) {
    }
    #[Route('/{_locale}/catalog/{slug}', name: 'catalog_list')]
    public function list(string $slug,RecipeRepository $recipeRepository,RecipeCategoryRepository $recipeCategoryRepository): Response
    {
        $recipeCategory = $recipeCategoryRepository->findOneByUrlKey($slug);
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByCategory($recipeCategory);

        $recipes = $recipeRepository->findByCategoryId($recipeCategory->getId());
        return $this->render('category/list.html.twig', [
            'recipes' => $recipes,
            'recipeCategory' => $recipeCategory,
            'breadCrumbs' => $breadCrumbs,
        ]);
    }
    #[Route('/{_locale}/recipe/{urlKey}', name: 'catalog_show')]
    public function show(string $urlKey, RecipeRepository $recipeRepository): Response
    {
        $recipe = $recipeRepository->findOneByUrlKey($urlKey);
        if (!$recipe) {
            throw $this->createNotFoundException();
        }
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByRecipe($recipe);
        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'breadCrumbs' => $breadCrumbs,
        ]);
    }
}

