<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationRedirectResolver
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $defaultLocale = 'uk',
    ) {
    }

    public function resolveUrl(UserInterface $user, ?Request $request = null): string
    {
        if ($this->isSuperAdmin($user)) {
            return $this->urlGenerator->generate('admin', [
                '_locale' => $this->resolveLocale($request),
            ]);
        }

        return $this->urlGenerator->generate('homepage');
    }

    public function isSuperAdmin(UserInterface $user): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    private function resolveLocale(?Request $request): string
    {
        $locale = $request?->attributes->get('_locale')
            ?? $request?->getLocale()
            ?? $this->defaultLocale;

        return in_array($locale, ['uk', 'en'], true) ? $locale : $this->defaultLocale;
    }
}
