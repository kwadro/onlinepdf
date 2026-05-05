<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\FavoriteListRepository;
use App\Repository\PopularsearchRepository;
use App\Repository\RecipeAuthorRepository;
use App\Repository\RecipeCategoryRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeServiceRepository;
use App\Repository\RecipeViewRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsController]
class HomePageController extends AbstractController
{
    /**
     * @param Request $request
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @return Response
     */
    #[Route('/{_locale}/', name: 'homepage')]
    public function index(
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {

        return $this->render('homepage/index.html.twig', [
            'typePage' => 'homepage'
        ]);
    }

    #[Route('/', name: 'default', locale: 'en')]
    public function default(Request $request): Response
    {
        return $this->redirectToRoute('homepage');
    }
}
