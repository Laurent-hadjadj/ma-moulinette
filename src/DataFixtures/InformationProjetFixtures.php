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

use App\Entity\InformationProjet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création InformationProjetFixtures.
 * Contrat :
 *  - InformationProjetKernelTest : findOneBy mavenKey ma-moulinette + findBy même clé = 3.
 *  - InformationProjetRepositoryTest : selectInformationProjetIsValide /
 *    selectInformationProjetVersion / deleteInformationProjetMavenKey /
 *    insertInformationProjet — tous attendent simplement code 200 et erreur vide.
 * Trois lignes avec mavenKey identique mais analyseKey distincts ; date_analyse et
 * type_analyse sont les noms de colonnes après MODIF 2026-05-04.
 */

class InformationProjetFixtures extends Fixture
{
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');
        $maven = 'fr.ma-moulinette:ma-moulinette';

        $i1 = (new InformationProjet())
            ->setMavenKey($maven)
            ->setAnalyseKey('AYabc1234567890ABCDE1')
            ->setDateAnalyse(new \DateTimeImmutable('2025-12-01 09:15:00'))
            ->setProjectVersion('1.0.0-RELEASE')
            ->setTypeAnalyse('RELEASE')
            ->setVersionSonar(1)
            ->setVersionReleaseSonar(1)
            ->setVersionSnapshotSonar(0)
            ->setVersionAutreSonar(0)
            ->setModeCollecte(self::MODE_COLLECTE)
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);
        $manager->persist($i1);

        $i2 = (new InformationProjet())
            ->setMavenKey($maven)
            ->setAnalyseKey('AYabc1234567890ABCDE2')
            ->setDateAnalyse(new \DateTimeImmutable('2025-12-15 11:42:00'))
            ->setProjectVersion('1.1.0-SNAPSHOT')
            ->setTypeAnalyse('SNAPSHOT')
            ->setVersionSonar(2)
            ->setVersionReleaseSonar(1)
            ->setVersionSnapshotSonar(1)
            ->setVersionAutreSonar(0)
            ->setModeCollecte('TRAITEMENT AUTOMATIQUE')
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);
        $manager->persist($i2);

        $i3 = (new InformationProjet())
            ->setMavenKey($maven)
            ->setAnalyseKey('AYabc1234567890ABCDE3')
            ->setDateAnalyse(new \DateTimeImmutable('2026-01-05 14:00:00'))
            ->setProjectVersion('1.2.0-RELEASE')
            ->setTypeAnalyse('RELEASE')
            ->setVersionSonar(3)
            ->setVersionReleaseSonar(2)
            ->setVersionSnapshotSonar(1)
            ->setVersionAutreSonar(0)
            ->setModeCollecte('COLLECTE')
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);
        $manager->persist($i3);

        $manager->flush();
    }
}
