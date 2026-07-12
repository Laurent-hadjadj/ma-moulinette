<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UserAgentEvent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-06-09 : fixtures UserAgentEvent pour tests intégration.
 * Contrat UserAgentEventRepositoryTest :
 *  - 2 événements PENDING (visitor_id distincts)
 *  - 1 événement DONE (exclu de selectPendingEvents)
 */
class UserAgentEventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-06-09 10:00:00');

        // Événement 1 : PENDING anonyme (login page)
        $e1 = new UserAgentEvent();
        $e1->setEventType('LOGIN_PAGE_VIEW');
        $e1->setUrl('/login');
        $e1->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $e1->setSessionId(null);
        $e1->setUserId(null);
        $e1->setVisitorId('550e8400-e29b-41d4-a716-446655440001');
        $e1->setAuthState('ANONYMOUS');
        $e1->setProcessingStatus('PENDING');
        $e1->setIpHash(hash('sha256', '127.0.0.1fixture-salt'));
        $e1->setCreatedAt($now);
        $manager->persist($e1);

        // Événement 2 : PENDING authentifié (logout)
        $e2 = new UserAgentEvent();
        $e2->setEventType('LOGOUT');
        $e2->setUrl('/logout');
        $e2->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15');
        $e2->setSessionId('fixture-session-abc123');
        $e2->setUserId(42);
        $e2->setVisitorId('550e8400-e29b-41d4-a716-446655440002');
        $e2->setAuthState('AUTHENTICATED');
        $e2->setProcessingStatus('PENDING');
        $e2->setIpHash(hash('sha256', '10.0.0.2fixture-salt'));
        $e2->setCreatedAt($now->modify('+1 minute'));
        $manager->persist($e2);

        // Événement 3 : DONE (ne doit PAS apparaître dans selectPendingEvents)
        $e3 = new UserAgentEvent();
        $e3->setEventType('LOGIN_SUCCESS_REDIRECT');
        $e3->setUrl('/accueil');
        $e3->setUserAgent('Mozilla/5.0 (X11; Linux x86_64) Gecko/20100101 Firefox/125.0');
        $e3->setSessionId('fixture-session-done');
        $e3->setUserId(7);
        $e3->setVisitorId('550e8400-e29b-41d4-a716-446655440003');
        $e3->setAuthState('AUTHENTICATED');
        $e3->setProcessingStatus('DONE');
        $e3->setIpHash(hash('sha256', '192.168.1.1fixture-salt'));
        $e3->setCreatedAt($now->modify('+2 minutes'));
        $e3->setProcessedAt($now->modify('+3 minutes'));
        $manager->persist($e3);

        $manager->flush();
    }
}
