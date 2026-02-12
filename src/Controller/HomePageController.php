<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LocaleRepository;
use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\SiteRepository;
use App\Service\Breadcrumbs;
use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class HomePageController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private Breadcrumbs $breadcrumbs,
        private LocaleRepository $localeRepo,
        private SiteRepository $siteRepo
    )
    {
    }
    #[Route('/{_locale}/', name: 'homepage')]
    public function index(
        Request $request,
        RecipeRepository $recipeRepository,
        RecipeCategoryRepository $recipeCategoryRepository,
        RecipeAuthorRepository $recipeAuthorRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $domain = $request->getHost();
        $requestLocale = $request->getLocale();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);

        if (!$site || !$localeObject) {
            return $this->render('catalog/list.html.twig', [
                'recipes' => [],
                'breadcrumbs' => [],
            ]);
        }
        $recipeCategories = $recipeCategoryRepository->findAllBySiteAndLocale($site->getId(), $localeObject->getId());

        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByCatalog();
        $recipes = $recipeRepository->findByCategoryId(null, $site->getId(), $localeObject->getId());
        $recipeAuthors = $recipeAuthorRepository->findAllBySiteAndLocale($site->getId(), $localeObject->getId());
        $filterAjaxUrl = $this->generateUrl('filter_ajax_data');
        $searchAjaxUrl = $this->generateUrl('search_ajax_data');
        $token = $csrfTokenManager->getToken('filter_form')->getValue();
        $tokenSearch = $csrfTokenManager->getToken('search_form')->getValue();

        return $this->render('homepage/index.html.twig', [
            'recipes' => $recipes,
            'breadcrumbs' => $breadCrumbs,
            'recipeAuthors' => $recipeAuthors,
            'searchAjaxUrl' => $searchAjaxUrl,
            'csrf_token' => $token,
            'csrf_token_search' => $tokenSearch,
        ]);
    }
//    #[Route('/', name: 'default', locale: 'en')]
//    public function default(Request $request): Response
//    {
//        $setting = $this->provider->getSettings(
//            $request->getHost(),
//            $request->getLocale()
//        );
//        return $this->render('homepage/index.html.twig',['setting'=>$setting]);
//    }
}
