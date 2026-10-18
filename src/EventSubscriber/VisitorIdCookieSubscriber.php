<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * [Description VisitorIdCookieSubscriber]
 *
 * Pose le cookie `visitor_id` lorsque UserAgentTrackerService::track() en a
 * généré un nouveau (absent côté client). Sans ce subscriber, aucun code du
 * projet n'écrit jamais le Set-Cookie correspondant : un nouvel identifiant
 * est régénéré à chaque requête, sans continuité (voir analyse RGPD,
 * mkDocs/docs/ma-moulinette/conformite/rgpd.md, section 2).
 */
class VisitorIdCookieSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'visitor_id';

    // 13 mois : plafond recommandé par la CNIL pour un cookie de mesure d'audience.
    private const LIFETIME_DAYS = 395;

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $visitorId = $request->attributes->get(self::COOKIE_NAME);

        if (!is_string($visitorId) || $visitorId === '') {
            return;
        }

        $event->getResponse()->headers->setCookie(
            Cookie::create(self::COOKIE_NAME)
                ->withValue($visitorId)
                ->withExpires(new \DateTimeImmutable('+' . self::LIFETIME_DAYS . ' days'))
                ->withPath('/')
                ->withSecure($request->isSecure())
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
    }
}
