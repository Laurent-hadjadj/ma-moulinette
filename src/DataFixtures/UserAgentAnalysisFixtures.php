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

use App\Entity\UserAgentAnalysis;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-06-09 : fixtures UserAgentAnalysis pour tests intégration.
 * Contrat UserAgentAnalysisRepositoryTest :
 *  - 1 analyse desktop / Windows / Chrome (non-bot)
 *  - 1 analyse smartphone / Android / Chrome (non-bot)
 */
class UserAgentAnalysisFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-06-09 10:00:00');

        // Analyse 1 : desktop Windows Chrome
        $a1 = new UserAgentAnalysis();
        $a1->setEventType('ACCUEIL');
        $a1->setUrl('/accueil');
        $a1->setSessionId('fixture-session-analysis-01');
        $a1->setUserId(42);
        $a1->setVisitorId('550e8400-e29b-41d4-a716-446655440001');
        $a1->setDeviceType('desktop');
        $a1->setOsName('Windows');
        $a1->setOsVersion('11');
        $a1->setBrowserName('Chrome');
        $a1->setBrowserVersion('125.0');
        $a1->setIsBot(false);
        $a1->setDetectorVersion('6.5.0');
        $a1->setCreatedAt($now);
        $manager->persist($a1);

        // Analyse 2 : smartphone Android Chrome
        $a2 = new UserAgentAnalysis();
        $a2->setEventType('LOGOUT');
        $a2->setUrl('/logout');
        $a2->setSessionId('fixture-session-analysis-02');
        $a2->setUserId(7);
        $a2->setVisitorId('550e8400-e29b-41d4-a716-446655440002');
        $a2->setDeviceType('smartphone');
        $a2->setOsName('Android');
        $a2->setOsVersion('14');
        $a2->setBrowserName('Chrome Mobile');
        $a2->setBrowserVersion('124.0');
        $a2->setIsBot(false);
        $a2->setDetectorVersion('6.5.0');
        $a2->setCreatedAt($now->modify('+5 minutes'));
        $manager->persist($a2);

        $manager->flush();
    }
}
