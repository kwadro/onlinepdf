<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LocaleRepository;
use App\Repository\PopularsearchRepository;
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

    /**
     * @param Request $request
     * @param RecipeRepository $recipeRepository
     * @param RecipeCategoryRepository $recipeCategoryRepository
     * @param RecipeAuthorRepository $recipeAuthorRepository
     * @param PopularsearchRepository $popularSearchRepository
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @return Response
     */
    #[Route('/{_locale}/', name: 'homepage')]
    public function index(
        Request $request,
        RecipeRepository $recipeRepository,
        PopularsearchRepository $popularSearchRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $domain = $request->getHost();
        $requestLocale = $request->getLocale();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);

        if (!$site || !$localeObject) {
            return $this->render('homepage/index.html.twig', [
                'recipes' => [],
                'breadcrumbs' => [],
            ]);
        }

        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByCatalog();
        $recipes = $recipeRepository->findByCategoryId(null, $site->getId(), $localeObject->getId());


        $searchAjaxUrl = $this->generateUrl('search_ajax_data');

        $tokenSearch = $csrfTokenManager->getToken('search_form')->getValue();
        $popularSearchWords = $popularSearchRepository->findAllBySiteAndLocale($site->getId(), $localeObject->getId());

        return $this->render('homepage/index.html.twig', [
            'recipes' => $recipes,
            'popularSearchWords' => $popularSearchWords,
            'breadcrumbs' => $breadCrumbs,
            'searchAjaxUrl' => $searchAjaxUrl,
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
