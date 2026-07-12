<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Profiles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation ProfilesFixtures.
 * Contrat : 2 profils Quality SonarQube afin de satisfaire ProfilesKernelTest
 * (findOneBy languageName=css : 1 résultat ; findOneBy referentialDefault=true : 1 resultat)
 * et ProfilesRepositoryTest (countProfiles/selectProfiles avec « true|false » et « css|null »
 * doivent renvoyer code 200). Aucune cle 'AXyXMubJRtAGLwAs7Zcv' n'est seedee : l'insert
 * du test testInsertProfiles ne provoque pas de doublon.
 * Tous les champs non-nullable (key, name, languageName, activeRuleCount, rulesUpdatedAt,
 * referentialDefault, dateEnregistrement) sont renseignes.
 */
class ProfilesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $rulesUpdatedAt = new \DateTimeImmutable('2025-12-15 12:00:00', new \DateTimeZone('Europe/Paris'));
        $now = new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('Europe/Paris'));

        $cssProfile = (new Profiles())
            ->setKey('AYzz0001csskeyabcdef01')
            ->setName('Sonar way (CSS)')
            ->setLanguageName('css')
            ->setActiveRuleCount(31)
            ->setRulesUpdatedAt($rulesUpdatedAt)
            ->setReferentialDefault(true)
            ->setDateEnregistrement($now);
        $manager->persist($cssProfile);

        $javaProfile = (new Profiles())
            ->setKey('AYzz0002javakeyabcdef02')
            ->setName('Sonar way (Java)')
            ->setLanguageName('java')
            ->setActiveRuleCount(420)
            ->setRulesUpdatedAt($rulesUpdatedAt)
            ->setReferentialDefault(false)
            ->setDateEnregistrement($now);
        $manager->persist($javaProfile);

        $manager->flush();
    }
}
