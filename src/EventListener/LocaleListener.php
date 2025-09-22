<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private string $defaultLocale = 'uk'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 10]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $this->requestStack->getSession();
        if (!$event->isMainRequest()) {
            return;
        }
        $path = $request->getPathInfo();

        if (str_starts_with($path, '/admin')) {
            $strArr = explode('/', trim($path, '/'));
            $queryLocale = $strArr[1] ?? null;
        } else {
            $strArr = explode('/', trim($path, '/'));
            $strArr = array_filter($strArr);
            $queryLocale = ($strArr) ? current($strArr) : null;
        }
        $locale = ($queryLocale)
            ?? $request->getSession()->get('_locale')
            ?? $this->defaultLocale;
        if (!in_array($locale, ['en', 'uk'])) {
            return;
        }
        $request->setLocale($locale);
        $request->getSession()?->set('_locale', $locale);
        $session->set('_locale', $locale);
        $this->translator->setLocale($locale);
    }
}
