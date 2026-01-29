<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LocaleRepository;
use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\SiteRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthorController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private Breadcrumbs $breadcrumbs,
        private LocaleRepository $localeRepo,
        private SiteRepository $siteRepo,
        private RecipeRepository $recipeRepository
    ) {
    }
    #[Route('/{_locale}/author-recipe/{id}', name: 'author_list')]
    public function list(Request $request, string $id, RecipeAuthorRepository $recipeAuthorRepository): Response
    {
        $domain = $request->getHost();
        $requestLocale = $request->getLocale();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);

        if (!$site || !$localeObject) {
            return $this->render('author/list.html.twig', [
                'recipes' => [],
                'recipeAuthor' => [],
                'breadCrumbs' => [],
            ]);
        }

        $recipeAuthor = $recipeAuthorRepository->findOneById($id);
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByAuthor($recipeAuthor);

        $recipes = $this->recipeRepository->findByAuthorId($recipeAuthor->getId(), $site->getId(), $localeObject->getId());
        return $this->render('author/list.html.twig', [
            'recipes' => $recipes,
            'recipeAuthor' => $recipeAuthor,
            'breadCrumbs' => $breadCrumbs,
        ]);
    }
    #[Route('/{_locale}/recipe/{urlKey}', name: 'catalog_show')]
    public function show(Request $request, string $urlKey): Response
    {
        $domain = $request->getHost();
        $requestLocale = $request->getLocale();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);
        $recipe = $this->recipeRepository->findOneByUrlKey($urlKey, $site->getId(), $localeObject->getId());
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

