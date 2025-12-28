<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class HomeController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
        private SiteSettingsProvider $provider
    )
    {
    }

    #[Route('/{_locale}/', name: 'homepage')]
    public function index(Request $request): Response
    {
        $setting = $this->provider->getSettings(
            $request->getHost(),
            $request->getLocale()
        );


        return $this->render('home/index.html.twig',['setting'=>$setting]);
    }

    #[Route('/', name: 'default', locale: 'en')]
    public function default(): Response
    {

        return $this->render('home/index.html.twig');
    }
}
