<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfilerController extends AbstractController
{
    #[Route('/api/me', name: 'api_profiler', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
