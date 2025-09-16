<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class CustomAuthenticator extends AbstractAuthenticator
{
    private UrlGeneratorInterface $urlGenerator;
    private HttpClientInterface $http;
    private EntityManagerInterface $em;
    private RouterInterface $router;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        HttpClientInterface $http,
        EntityManagerInterface $em,
        RouterInterface $router
    ) {
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->http = $http;
        $this->em = $em;
    }


    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        if ($request->attributes->get('_route') === 'app_login' && $request->isMethod('POST')) {
            return true;
        }

        if ($request->attributes->get('_route') === 'login_google_callback' && $request->query->has('code')) {
            return true;
        }

        return false;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function authenticate(Request $request): Passport
    {
        if ($request->attributes->get('_route') === 'app_login') {
            $email = $request->request->get('_username', '');
            $password = $request->request->get('_password', '');
            return new Passport(
                new UserBadge($email),
                new PasswordCredentials($password)
            );
        }
        if ($request->attributes->get('_route') === 'login_google_callback') {
            $code = $request->query->get('code');

            $tokenResponse = $this->http->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code' => $code,
                    'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
                    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                    'redirect_uri' => $this->urlGenerator->generate(
                        'login_google_callback',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    'grant_type' => 'authorization_code',
                ]
            ])->toArray();

            $accessToken = $tokenResponse['access_token'];

            $profile = $this->http->request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ])->toArray();

            $email = $profile['email'] ?? null;
            $verified = $profile['verified_email'] ?? false;

            if (!$email || !$verified) {
                throw new \Exception('Google email not confirmed!');
            }

            $name = '';
            $picture = '';
            return new Passport(
                new UserBadge($email, function ($userIdentifier) use ($name, $picture) {
                    $user = $this->em->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

                    if (!$user) {
                        $user = new User();
                        $user->setEmail($userIdentifier);
                        $user->setPassword(bin2hex(random_bytes(16))); // випадковий пароль
                    }
                    if ($name) {
                        $names = explode(' ', $name);
                        $user->setFirstName($names[0]);
                        $user->setLastName($names[1] ?? '');
                    }
                    if ($picture) {
                        //$user->setAvatarUrl($picture);
                    }
                    $this->em->persist($user);
                    $this->em->flush();
                    return $user;
                }),
                new CustomCredentials(function($credentials, $user) {
                    return true;
                }, 'google_oauth')
            );
        }
        throw new LogicException('Unknown authentication method');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('admin'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    // public function start(Request $request, ?AuthenticationException $authException = null): Response
    // {
    //     /*
    //      * If you would like this class to control what happens when an anonymous user accesses a
    //      * protected page (e.g. redirect to /login), uncomment this method and make this class
    //      * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //      *
    //      * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //      */
    // }
}
