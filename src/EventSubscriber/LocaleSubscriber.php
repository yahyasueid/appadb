<?php
// src/EventSubscriber/LocaleSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'ar')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Si ?_locale=ar dans l'URL → sauvegarder en session
        if ($locale = $request->query->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        } else {
            // Sinon lire depuis la session
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }
}