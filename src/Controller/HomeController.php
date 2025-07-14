<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class HomeController extends AbstractController
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[Route('/{_locale}/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/', name: 'default',locale: 'en')]
    public function default(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
