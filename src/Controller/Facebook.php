<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Facebook extends AbstractController
{
    #[Route('/{_locale}/facebook/data-deletion', name: 'facebook_data_deletion')]
    public function deleteFacebookData(): Response
    {
        return $this->render('facebook/delete_facebook_data.html.twig');
    }
}
