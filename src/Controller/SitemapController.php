<?php

namespace App\Controller;

use App\Repository\RecipeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

readonly class SitemapController
{
    public function __construct(
        private RecipeRepository $recipeRepository
    ) {
    }

    #[Route('/sitemap.xml', name: 'sitemap')]
    public function index(
        Request $request
    ): Response
    {
        $urls = [
            '',
            'contact',
            'about',
        ];

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><urlset/>'
        );
        $xml->addAttribute(
            'xmlns',
            'http://www.sitemaps.org/schemas/sitemap/0.9'
        );
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');
        $recipes = $this->recipeRepository->findByCategoryId(null, $site->getId(), $localeObject->getId());

        foreach ($recipes as $recipe) {
            if($recipe->getRecipetranslations()[0]->getConfirmation() === 'Yes'){
                $urls[] = 'recipe/' . $recipe->getRecipetranslations()[0]->getSlug();
            }
        }

        foreach ($urls as $url) {
            $urlTag = $xml->addChild('url');
            $urlTag->addChild(
                'loc',
                $request->getUriForPath('/uk/') . $url
            );

            $urlTag->addChild(
                'changefreq',
                'daily'
            );

            $urlTag->addChild(
                'priority',
                '0.8'
            );
        }

        return new Response(
            $xml->asXML(),
            200,
            [
                'Content-Type' => 'application/xml',
            ]
        );
    }
}
