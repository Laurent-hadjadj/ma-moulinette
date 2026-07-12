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

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Owasp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création OwaspFixtures.
 * Contrat : 3 lignes pour la même mavenKey (référentiel 2017) afin de satisfaire
 * OwaspKernelTest::testOwaspCount (assertCount 3 sur findBy mavenKey) et
 * OwaspKernelTest::testOwaspFindOneBy (findOneBy mavenKey non null). Les 60 compteurs
 * Ax/AxBlocker/AxCritical/AxMajor/AxMinor/AxInfo + effortTotal sont fixés à 0
 * (PositiveOrZero), versions distinctes pour garder 3 rows réels.
 */

class OwaspFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');
        $versions = ['1.0.0-RELEASE', '1.1.0-RELEASE', '1.2.0-RELEASE'];

        foreach ($versions as $version) {
            $owasp = $this->buildOwasp($version, $now);
            $manager->persist($owasp);
        }

        $manager->flush();
    }

    /**
     * Construit une entité Owasp avec tous les compteurs OWASP Top 10 (a1..a10
     * et leurs sous-niveaux Blocker/Critical/Major/Minor/Info) initialisés à zéro.
     */
    private function buildOwasp(string $version, \DateTimeImmutable $now): Owasp
    {
        $owasp = (new Owasp())
            ->setReferentialOwasp(2017)
            ->setMavenKey(self::MAVEN_KEY)
            ->setVersion($version)
            ->setDateVersion($now)
            ->setEffortTotal(0)
            ->setModeCollecte(self::MODE_COLLECTE)
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);

        for ($i = 1; $i <= 10; $i++) {
            $owasp->{"setA{$i}"}(0);
            $owasp->{"setA{$i}Blocker"}(0);
            $owasp->{"setA{$i}Critical"}(0);
            $owasp->{"setA{$i}Major"}(0);
            $owasp->{"setA{$i}Info"}(0);
            $owasp->{"setA{$i}Minor"}(0);
        }

        return $owasp;
    }
}
