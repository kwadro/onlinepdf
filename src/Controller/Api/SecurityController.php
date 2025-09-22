<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Security\CustomAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private EmailVerifier $emailVerifier;
    private TranslatorInterface $translator;

    public function __construct(
        HttpClientInterface $httpClient,
        EmailVerifier $emailVerifier,
        TranslatorInterface $translator
    ) {
        $this->httpClient = $httpClient;
        $this->emailVerifier = $emailVerifier;
        $this->translator = $translator;
    }


    #[Route('/api/login/google', name: 'api_login_google', methods: ['POST'])]
    public function googleLogin(): Response
    {
        $params = [
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'redirect_uri' => $this->generateUrl('login_google_callback', [], 0),
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'offline',
            'prompt' => 'select_account',
            'state' => 'api_request',
        ];


        $url = CustomAuthenticator::GOOGLE_OAUTH_BASE_URL . '?' . http_build_query($params);
        return $this->redirect($url);
    }

    /**
     * @return RedirectResponse|Response|null
     */
    #[Route('/login/google/callback', name: 'login_google_callback')]
    public function googleCallback(): RedirectResponse|Response|null
    {
        throw new LogicException(
            'This method can be blank - it will be intercepted by customAuthenticator.'
        );
    }

    #[Route(path: '/api/login', name: 'api_app_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => $this->translator->trans('register.require_message', [], 'messages')], 400);
        }
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user) {
            return $this->json(['error' => $this->translator->trans('register.not_found_message', [], 'messages')],
                404);
        }
        if (!$userPasswordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => $this->translator->trans('register.invalid_credential_message', [], 'messages')], 401);
        }
        $accessToken = $jwtManager->create($user);
        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, 2592000);
        $refreshTokenManager->save($refreshToken);
        return $this->json([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'user' => [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
            ]
        ]);

    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }
}
