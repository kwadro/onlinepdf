<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Security\EmailVerifier;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private TranslatorInterface $translator
    ) {
    }
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            $message = [
                'status'=>'error',
                'text'=>$this->translator->trans('register.require_message', [], 'messages')
            ];
            return $this->json(['message' => $message],400);
        }

        // check if a user already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            $message = [
                'status'=>'error',
                'text'=>$this->translator->trans('register.user_exist_message', [], 'messages')
            ];
            return $this->json(['message' => $message],400);
        }

        // create new user
        $user = new User();

        $user->setEmail($data['email']);
        $hashedPassword = $userPasswordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);

        $entityManager->persist($user);
        $entityManager->flush();

        try {
            $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, 30 * 24 * 60 * 60);
            $refreshTokenManager->save($refreshToken);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            $message = ['status'=>'error','text'=>$e->getMessage()];
            return $this->json(['message' => $message],500);
        }
        $message = ['status'=>'success','text'=>'User registered successfully'];
        return $this->json([
            'message' => $message,
            'access_token' => $jwtManager->create($user),
            'refresh_token' => $refreshToken->getRefreshToken(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ]
        ], 201);
    }
}
