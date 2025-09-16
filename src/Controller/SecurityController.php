<?php

namespace App\Controller;

use App\Security\CustomAuthenticator;
use App\Security\EmailVerifier;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private EmailVerifier $emailVerifier;

    public function __construct(
        HttpClientInterface $httpClient,
        EmailVerifier $emailVerifier
    ) {
        $this->httpClient = $httpClient;
        $this->emailVerifier = $emailVerifier;
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
