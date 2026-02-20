<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LocaleRepository;
use App\Repository\PopularsearchRepository;
use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class AccountController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
        private Breadcrumbs $breadcrumbs,
        private LocaleRepository $localeRepo,
        private SiteRepository $siteRepo
    ) {
    }

    #[Route('/{_locale}/account/recently-viewed', name: 'account_recently_viewed')]
    public function getRecentlyViewedSetting(
        Request $request,
        RecipeRepository $recipeRepository,
        UserRepository $userRepository
    ): Response
    {
        if($user = $this->getUser()){

            $recentlyRecipes = $user->getRecentlyViewedRecipes();
            return $this->render('security/account/recently-viewed.html.twig', [
                'isLogin' => $user,
                'recentlyRecipes' => $recentlyRecipes,
            ]);
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/{_locale}/account/setting', name: 'account_setting')]
    public function getAccountSetting(Request $request): Response
    {
        if($this->getUser()) {
            return $this->render('security/account/setting.html.twig', [
                'isLogin' => (bool)$this->getUser()
            ]);
        }

        return $this->redirectToRoute('app_login');

    }

    #[Route('/{_locale}/account/my-recipes', name: 'account_my_recipes')]
    public function getMyRecipesSetting(Request $request): Response
    {
        if($this->getUser()) {
            return $this->render('security/account/my-recipes.html.twig', [
                'isLogin' => (bool)$this->getUser()
            ]);
        }

        return $this->redirectToRoute('app_login');
    }
}
