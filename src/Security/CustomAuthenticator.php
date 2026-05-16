<?php

namespace App\Security;

use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
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
    public const GOOGLE_OAUTH_BASE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    public const GOOGLE_GET_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    public const GOOGLE_GET_PROFILE_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';
    const FACEBOOK_OAUTH_BASE_URL = 'https://www.facebook.com/v19.0/dialog/oauth';
    public const FACEBOOK_GET_TOKEN_URL = 'https://graph.facebook.com/v19.0/oauth/access_token';
    public const FACEBOOK_GET_PROFILE_URL = 'https://graph.facebook.com/me';
    const EXTERNAL_TYPE_FACEBOOK = 'facebook';
    const EXTERNAL_TYPE_GOOGLE = 'google';
    const EMAIL_REQUIRED_MESSAGE_KEY = 'EMAIL_REQUIRED';

    private UrlGeneratorInterface $urlGenerator;
    private HttpClientInterface $http;
    private RouterInterface $router;
    private UserManager $userManager;
    private LoggerInterface $logger;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        HttpClientInterface $http,
        RouterInterface $router,
        UserManager $userManager,
        LoggerInterface $logger
    ) {
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->http = $http;
        $this->userManager = $userManager;
        $this->logger = $logger;
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
        if ($request->attributes->get('_route') === 'login_facebook_callback' && $request->query->has('code')) {
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
        if ($request->attributes->get('_route') === 'login_facebook_callback') {
            $code = $request->query->get('code');
            $tokenResponse = $this->http->request(
                'GET',
                self::FACEBOOK_GET_TOKEN_URL,
                [
                    'query' => [
                        'client_id' => $_ENV['FACEBOOK_APP_ID'],
                        'client_secret' => $_ENV['FACEBOOK_APP_SECRET'],
                        'redirect_uri' => $this->urlGenerator->generate(
                            'login_facebook_callback',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        'code' => $code,
                    ]
                ]
            );
            $tokenData = $tokenResponse->toArray();

            if (empty($tokenData['access_token'])) {
                throw new Exception('Facebook token error');
            }

            // get user data
            $response = $this->http->request(
                'GET',
                self::FACEBOOK_GET_PROFILE_URL,
                [
                    'query' => [
                        'fields' => 'id,name,email',
                        'access_token' => $tokenData['access_token'],
                    ]
                ]
            );
            $facebookUser = $response->toArray();
            $externalId = $facebookUser['id'] ?? null;
            $user = $this->userManager->getUserByExternalData(
                $externalId,
                self::EXTERNAL_TYPE_FACEBOOK
            );
            if ($user) {
                $email = $user->getEmail();
            } else {
                $email = $facebookUser['email'] ?? null;
            }
            $name = $facebookUser['name'] ?? null;
            $this->logger->info('Facebook user data: ' . json_encode($facebookUser));
            $picture = sprintf(
                'https://graph.facebook.com/%s/picture?redirect=false&type=large&access_token=%s',
                $externalId,
                $tokenData['access_token']
            );
            $data = json_decode(file_get_contents($picture), true);
            if (!empty($data['data']['is_silhouette'])) {
                $picture = null;
            } else {
                $picture = $data['data']['url'];
            }
            if (!$email) {
                $request->getSession()->set(
                    'facebook_user_data',
                    [
                        'id' => $externalId,
                        'name' => $name,
                        'avatarUrl' => $picture,
                    ]
                );
                throw new CustomUserMessageAuthenticationException(
                    self::EMAIL_REQUIRED_MESSAGE_KEY
                );
            }
            if (!$externalId) {
                throw new Exception('Facebook ID missing');
            }

            return new Passport(
                new UserBadge($email, function ($userIdentifier) use ($name, $picture, $externalId) {
                    list($user, $isNewUser) = $this->userManager->updateOrCreateUser(
                        $userIdentifier,
                        $name,
                        $picture,
                        $externalId,
                        self::EXTERNAL_TYPE_FACEBOOK
                    );
                    if ($isNewUser) {
                        $this->userManager->sendEmailVerificationNotification($user);
                    }
                    return $user;
                }),
                new CustomCredentials(function ($credentials, $user) {
                    return true;
                }, 'facebook_oauth')
            );
        }
        if ($request->attributes->get('_route') === 'login_google_callback') {
            $code = $request->query->get('code');

            $tokenResponse = $this->http->request('POST', self::GOOGLE_GET_TOKEN_URL, [
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

            $profile = $this->http->request('GET', self::GOOGLE_GET_PROFILE_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ])->toArray();

            $email = $profile['email'] ?? null;
            $verified = $profile['verified_email'] ?? false;

            if (!$email || !$verified) {
                throw new Exception('Google email not confirmed!');
            }

            $name = $profile['name'] ?? null;
            $picture = $profile['picture'] ?? null;
            $externalId = $profile['id'] ?? null;

            return new Passport(
                new UserBadge($email, function ($userIdentifier) use ($name, $picture, $externalId) {
                    list($user, $isNewUser) = $this->userManager->updateOrCreateUser(
                        $userIdentifier,
                        $name,
                        $picture,
                        $externalId,
                        self::EXTERNAL_TYPE_GOOGLE
                    );
                    if ($isNewUser) {
                        $this->userManager->sendEmailVerificationNotification($user);
                    }
                    return $user;
                }),
                new CustomCredentials(function ($credentials, $user) {
                    return true;
                }, 'google_oauth')
            );
        }
        throw new LogicException('Unknown authentication method');
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): RedirectResponse {
        return new RedirectResponse($this->router->generate('homepage'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception->getMessageKey() === self::EMAIL_REQUIRED_MESSAGE_KEY) {
            return new RedirectResponse($this->router->generate('facebook_email'));
        }

        $email = $request->request->get('_username', '');
        $request->getSession()->set(
            SecurityRequestAttributes::AUTHENTICATION_ERROR,
            $exception
        );
        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );
        return new RedirectResponse($this->router->generate('app_login'));
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
