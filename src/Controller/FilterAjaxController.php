<?php

namespace App\Controller;

use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsController]
class FilterAjaxController extends AbstractController
{
    #[Route('/{_locale}/filter-category', name: 'filter_ajax_data')]
    public function filter(
        Request $request,
        RecipeRepository $recipeRepository,
        RecipeCategoryRepository $recipeCategoryRepository,
        UserRepository $userRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        if ($request->isXmlHttpRequest()) {
            $site = $request->attributes->get('site');
            $localeObject = $request->attributes->get('localeObject');
            $params = $request->request->all();
            $token = $params['_token'];
            unset($params['_token']);

            $csrfTokenId = 'filter_form';
            if (!$csrfTokenManager->isTokenValid(new CsrfToken($csrfTokenId, $token))) {
                return $this->json([
                    'success' => false,
                    'errors' => ['_token' => ['CSRF token is invalid.']]
                ], 403);
            }
            $categoryIds = $params['category_ids'] ?? [];
            $authorIds = $params['author_ids'] ?? [];
            $categoryIdsArr = $authorIdsArr = [];
            if (!empty($categoryIds)) {
                $categoryIdsArr = explode(',', $categoryIds);
                $filteredCategories = $recipeCategoryRepository->findBy(['id' => $categoryIdsArr]);
            } else {
                $filteredCategories = [];
                $categoryIds = null;
            }

            if (!empty($authorIds)) {
                $authorIdsArr = explode(',', $authorIds);
                $filteredAuthors = $userRepository->findBy(['id' => $authorIdsArr]);
            } else {
                $filteredAuthors = [];
                $authorIds = null;
            }

            $filterHtml = $this->render('catalog/filter.html.twig', [
                'categories' => $filteredCategories,
                'authors' => $filteredAuthors,
            ])->getContent();

            $recipes = $recipeRepository->findByCategoryAndAuthor(
                $categoryIdsArr,
                $authorIdsArr,
                $site->getId(),
                $localeObject->getId()
            );

            $gridHtml = $this->render('catalog/products-grid.html.twig', [
                'recipes' => $recipes,
            ])->getContent();

            return $this->json([
                'success' => true,
                'gridHtml' => $gridHtml,
                'filterHtml' => $filterHtml
            ]);
        }
        return $this->redirectToRoute('home');
    }
}
