<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PrivacyPolicyController extends AbstractController
{
    #[Route('/{_locale}/privacy-policy', name: 'privacy_policy')]
    public function privacyPolicy(
        Request $request
    ){
        $site = $request->attributes->get('site');
        $localeObject = $request->attributes->get('localeObject');
        return $this->render('page/privacy-policy.html.twig');
    }
}
