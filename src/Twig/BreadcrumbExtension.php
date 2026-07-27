<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\Breadcrumbs;
use App\Service\SiteSettingsProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class BreadcrumbExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SiteSettingsProvider $siteSettingsProvider,
        private readonly Breadcrumbs $breadcrumbs,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('default_breadcrumbs', [$this, 'getDefaultBreadcrumbs']),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'page_breadcrumbs' => $this->resolvePageBreadcrumbs(),
        ];
    }

    /**
     * @return list<array{link: bool|null, url: string|null, name: string, route?: string, routeParams?: array<string, mixed>}>
     */
    public function getDefaultBreadcrumbs(): array
    {
        return $this->resolvePageBreadcrumbs();
    }

    /**
     * @return list<array{link: bool|null, url: string|null, name: string, route?: string, routeParams?: array<string, mixed>}>
     */
    private function resolvePageBreadcrumbs(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return [];
        }

        $settings = $this->siteSettingsProvider->getSettings(
            $request->getHost(),
            $request->getLocale(),
        );

        return $this->breadcrumbs->resolveFromRequest($request, $settings['menu'] ?? []);
    }
}
