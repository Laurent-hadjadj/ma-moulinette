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

use App\Entity\Historique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation HistoriqueFixtures.
 * Contrat : 2 lignes Historique partageant la meme maven_key
 * (fr.ma-moulinette:ma-moulinette) avec deux couples (version, date_version)
 * distincts, conformément a la clé composite (maven_key, version, date_version).
 * - Une ligne porte la version « 1.2.0-RELEASE » exigée par testGetProjetFavori
 *   et testUpdateHistoriqueReference (HistoriqueRepositoryTest).
 * - Aucune ligne ne porte (1.5.0-RELEASE, 2024-08-18 15:54:26) : testInsertHistoriqueAjoutProjet
 *   doit réussir au premier appel puis renvoyer 23505 au second.
 * Tous les champs non-nullable (nom_projet, analyse_key, initial, date_enregistrement)
 * sont renseignes avec des valeurs par défaut de cohérences.
 */

class HistoriqueFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const PROJECT_NAME = 'ma-moulinette';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('Europe/Paris'));

        $reference = (new Historique())
            ->setMavenKey(self::MAVEN_KEY)
            ->setVersion('1.2.0-RELEASE')
            ->setDateVersion('2024-07-12 16:34:46')
            ->setNomProjet(self::PROJECT_NAME)
            ->setAnalyseKey('AZCa01abcdefghijKLmnop')
            ->setInitial(true)
            ->setDateEnregistrement($now);
        $manager->persist($reference);

        $intermediate = (new Historique())
            ->setMavenKey(self::MAVEN_KEY)
            ->setVersion('1.3.0-RELEASE')
            ->setDateVersion('2024-09-15 10:00:00')
            ->setNomProjet(self::PROJECT_NAME)
            ->setAnalyseKey('AZCb02abcdefghijKLmnop')
            ->setInitial(false)
            ->setDateEnregistrement($now);
        $manager->persist($intermediate);

        $manager->flush();
    }
}
