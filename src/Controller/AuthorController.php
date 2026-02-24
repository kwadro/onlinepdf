<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\RecipeRepository;
use App\Repository\UserRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthorController extends AbstractController
{
    public function __construct(
        private Breadcrumbs $breadcrumbs,
        private RecipeRepository $recipeRepository
    ) {
    }

    #[Route('/{_locale}/author-recipe/{id}', name: 'author_list')]
    public function list(
        Request $request,
        string $id,
        UserRepository $userRepository
    ): Response {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

        if (!$site || !$localeObject) {
            return $this->render('author/list.html.twig', [
                'recipes' => [],
                'recipeAuthor' => [],
                'breadcrumbs' => [],
            ]);
        }

        $recipeUser = $userRepository->findOneById($id);
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByAuthor($recipeUser);

        $recipes = $this->recipeRepository->findByAuthorId(
            $recipeUser->getId(),
            $site->getId(),
            $localeObject->getId()
        );
        return $this->render('author/list.html.twig', [
            'recipes' => $recipes,
            'recipeAuthor' => $recipeUser,
            'breadcrumbs' => $breadCrumbs,
        ]);
    }

    #[Route('/{_locale}/recipe/{urlKey}', name: 'catalog_show')]
    public function show(Request $request, string $urlKey): Response
    {
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');

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

