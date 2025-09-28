<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfilerController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    #[Route('/api/me', name: 'api_profiler', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function me(
        #[CurrentUser] ?User $user
    ): Response {
        if (!$user) {
            $message = [
                'status' => 'error',
                'text' => $this->translator->trans('register.not_found_message', [], 'messages')
            ];
            return $this->json(['message' => $message], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
//            'roles' => $user->getRoles(),
        ]);
    }
}
