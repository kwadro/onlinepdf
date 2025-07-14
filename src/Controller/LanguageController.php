<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\LocaleSwitcher;

#[AsController]
class LanguageController extends AbstractController
{

    private LocaleSwitcher $localeSwitcher;
    private RequestStack $requestStack;

    public function __construct(LocaleSwitcher $localeSwitcher, RequestStack $requestStack)
    {
        $this->localeSwitcher = $localeSwitcher;
        $this->requestStack = $requestStack;
    }

    #[Route('/switch-language/{_locale}', name: 'switch_language')]
    public function switchLanguage(string $_locale, Request $request): Response
    {
        $this->localeSwitcher->setLocale($_locale);
        $request->setLocale($_locale);
        $referer = $request->headers->get('referer');
        $temp = explode('/', $referer);
        $temp[3]= $_locale;
        $newReferer = implode('/', $temp);


        //return new Response('Locale switched to ' . $_locale);
        return new RedirectResponse($newReferer ?: $this->generateUrl('homepage'));
    }
}
