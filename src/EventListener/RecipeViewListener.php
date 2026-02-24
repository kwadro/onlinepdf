<?php

namespace App\EventListener;

use App\Controller\CollectionController;
use App\Entity\RecipeView;
use App\Repository\LocaleRepository;
use App\Repository\RecipeRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

final class RecipeViewListener
{
    public function __construct(
        private RecipeRepository $recipeRepository,
        private LocaleRepository $localeRepo,
        private SiteRepository $siteRepo,
        private Security $security,
        private EntityManagerInterface $entityManager
    ){

    }
    #[AsEventListener]
    public function onControllerEvent(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }
        $request = $event->getRequest();
        $urlKey = $request->get('urlKey');
        $requestLocale = $request->getLocale();
        $domain = $request->getHost();
        $site = $this->siteRepo->findOneBy(['domain' => $domain]);
        $localeObject = $this->localeRepo->findOneBy(['code' => $requestLocale]);

        $request->attributes->set('site', $site);
        $request->attributes->set('localeObject', $localeObject);

        //save information about view page
        if ($controller[0] instanceof CollectionController
            && $controller[1] === 'show') {
            $recipe = $this->recipeRepository->findOneByUrlKey($urlKey, $site->getId(), $localeObject->getId());
            $user = $this->security->getUser();
            if($user){
                $recipeView = new RecipeView();
                $recipeView->setUserId($user->getId());
                $recipeView->setRecipeId($recipe->getId());
                $this->entityManager->persist($recipeView);
                $this->entityManager->flush();
            }
        }
    }
}
