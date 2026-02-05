<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\LocaleRepository;
use App\Repository\MegaMenuSettingRepository;
use App\Repository\SiteRepository;
use App\Service\Breadcrumbs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StaticPageController extends AbstractController
{
    public function __construct(
        private Breadcrumbs $breadcrumbs,
        private LocaleRepository $localeRepo,
        private SiteRepository $siteRepo
    ){

    }
    #[Route('/{_locale}/static/{slug}', name: 'static_page')]
    public function index(Request $request, string $slug, MegaMenuSettingRepository $megaMenuSettingRepository): Response
    {
        $domain = $request->getHost();
        $requestLocale = $request->getLocale();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);
        $menuItem = $megaMenuSettingRepository->findBySiteAndLocaleAndSlug($slug,$site->getId(), $localeObject->getId());
        $breadCrumbs = $this->breadcrumbs->loadBreadCrumbsByMenuItem($menuItem);
        return $this->render($slug.'/index.html.twig',['breadcrumbs'=>$breadCrumbs]);
    }
}
