<?php

namespace App\Controller;

use App\Repository\LocaleRepository;
use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsController]
class SearchAjaxController extends AbstractController
{
    public function __construct(
        private readonly LocaleRepository $localeRepo,
        private readonly SiteRepository $siteRepo
    ) {
    }

    #[Route('/{_locale}/search-category', name: 'search_ajax_data')]
    public function filter(
        Request $request,
        RecipeRepository $recipeRepository,
        RecipeCategoryRepository $recipeCategoryRepository,
        RecipeAuthorRepository $recipeAuthorRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        if ($request->isXmlHttpRequest()) {
            $domain = $request->getHost();
            $requestLocale = $request->getLocale();
            $site = $this->siteRepo->findOneBy(['domain' => $domain]);
            $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);
            $params = $request->request->all();

            $token = $params['_token']??'';
            unset($params['_token']);
            $csrfTokenId = 'search_form';
            if (!$csrfTokenManager->isTokenValid(new CsrfToken($csrfTokenId, $token))) {
                return $this->json([
                    'success' => false,
                    'errors' => ['_token' => ['CSRF token is invalid.']]
                ], 403);
            }
            $query = $params['q'];
            $recipes = $recipeRepository->findBySearchQuery(
                $query,
                $site->getId(),
                $localeObject->getId()
            );

            $gridHtml = $this->render('catalog/products-grid.html.twig', [
                'recipes' => $recipes,
            ])->getContent();

            return $this->json([
                'success' => true,
                'gridHtml' => $gridHtml,
                'filterHtml' => ''
            ]);
        }
        return $this->redirectToRoute('home');
    }
}
