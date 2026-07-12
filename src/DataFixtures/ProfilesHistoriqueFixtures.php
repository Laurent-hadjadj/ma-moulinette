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

use App\Entity\ProfilesHistorique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation ProfilesHistoriqueFixtures.
 * Contrat : 1 ligne avec language=java pour satisfaire
 * ProfilesHistoriqueKernelTest::testInformationProjetFindOneBy (findOneBy language=java : 1
 * resultat), et au moins une ligne avec date_courte=2022-04-14 / action=ACTIVATED pour les
 * tests ProfilesHistoriqueRepositoryTest (selectProfilesHistoriqueAction /
 * selectProfilesHistoriqueLangageDateCourte). Tous les champs non-nullable (date_courte,
 * language, date, action, auteur, rule, description, detail [BLOB], date_enregistrement)
 * sont renseignes.
 */
class ProfilesHistoriqueFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tz = new \DateTimeZone('Europe/Paris');
        $dateCourte = new \DateTimeImmutable('2022-04-14 00:00:00', $tz);
        $date = new \DateTimeImmutable('2022-08-30 18:42:41', $tz);
        $now = new \DateTimeImmutable('2026-01-01 00:00:00', $tz);

        $row = (new ProfilesHistorique())
            ->setDateCourte($dateCourte)
            ->setLanguage('java')
            ->setDate($date)
            ->setAction('ACTIVATED')
            ->setAuteur('HADJADJ Laurent')
            ->setRule('java:S5679')
            ->setDescription('OpenSAML2 should be configured to prevent authentication bypass')
            ->setDetail('{"severity":"MAJOR"}')
            ->setDateEnregistrement($now);

        $manager->persist($row);

        $manager->flush();
    }
}
