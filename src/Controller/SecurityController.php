<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\CustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    const GOOGLE_OAUTH_BASE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    const GOOGLE_GET_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const GOOGLE_GET_PROFILE_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

    }

    #[Route('/{_locale}/login/google', name: 'login_google')]
    public function googleLogin(): Response
    {
        $params = [
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'redirect_uri' => $this->generateUrl('login_google_callback', [], 0),
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ];


        $url = self::GOOGLE_OAUTH_BASE_URL.'?' . http_build_query($params);
        return $this->redirect($url);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Route('/login/google/callback', name: 'login_google_callback')]
    public function googleCallback(
        Request $request,
        EntityManagerInterface $em,
        UserAuthenticatorInterface $userAuthenticator,
        CustomAuthenticator $authenticator
    ): RedirectResponse|Response|null {
        $code = $request->query->get('code');

        if (!$code) {
            return $this->redirectToRoute('app_login');
        }

        $response = $this->httpClient->request('POST', self::GOOGLE_GET_TOKEN_URL, [
            'body' => [
                'code' => $code,
                'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
                'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                'redirect_uri' => $this->generateUrl('login_google_callback', [], 0),
                'grant_type' => 'authorization_code',
            ]
        ]);

        $data = $response->toArray();
        $accessToken = $data['access_token'];

        // 2. Отримуємо профіль
        $profile = $this->httpClient->request('GET', self::GOOGLE_GET_PROFILE_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ])->toArray();

        $email = $profile['email'] ?? null;

        if (!$email) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword('');
            $em->persist($user);
            $em->flush();
        }
        return $userAuthenticator->authenticateUser(
            $user,
            $authenticator,
            $request
        );
    }
    #[Route(path: '/{_locale}/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'translation_domain' => 'admin',
            'csrf_token_intention' => 'authenticate',
            'target_path' => $this->generateUrl('admin'),
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
