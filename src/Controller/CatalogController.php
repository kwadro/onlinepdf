<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\RecipeCategory;
use App\Repository\LocaleRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\SiteRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CatalogController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private Breadcrumbs $breadcrumbs,
        private LocaleRepository $localeRepo,
        private SiteRepository $siteRepo
    ) {
    }
    #[Route('/{_locale}/catalog/{slug}', name: 'catalog_list')]
    public function list(Request $request, string $slug, RecipeRepository $recipeRepository,RecipeCategoryRepository $recipeCategoryRepository): Response
    {
        $domain = $request->getHost();
        $requestLocale = $request->getLocale();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);

        if (!$site || !$localeObject) {
            return $this->render('category/list.html.twig', [
                'recipes' => [],
                'recipeCategory' => [],
                'breadcrumbs' => [],
            ]);
        }

        $recipeCategory = $recipeCategoryRepository->findOneByUrlKey($slug);
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByCategory($recipeCategory);
        $recipes = $recipeRepository->findByCategoryId($recipeCategory->getId(), $site->getId(), $localeObject->getId());

        return $this->render('category/list.html.twig', [
            'recipes' => $recipes,
            'recipeCategory' => $recipeCategory,
            'breadcrumbs' => $breadCrumbs

        ]);
    }
    #[Route('/{_locale}/recipe/{urlKey}', name: 'catalog_show')]
    public function show(Request $request, string $urlKey, RecipeRepository $recipeRepository): Response
    {
        $domain = $request->getHost();
        $requestLocale = $request->getLocale();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);
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

