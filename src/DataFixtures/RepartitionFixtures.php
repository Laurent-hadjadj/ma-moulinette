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

use App\Entity\Repartition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation RepartitionFixtures.
 * Contrat : 1 ligne Repartition pour la maven_key fr.ma-moulinette:ma-moulinette
 * et le setup 1739816022572 (timestamp ms attendu par RepartitionKernelTest:
 * findOneBy(maven_key+setup) et findBy(setup) -> count 1).
 * Tous les compteurs héritent de leur valeur par défaut (0). Champs non-nullable
 * obligatoires : maven_key, name, setup, control, date_enregistrement.
 */

class RepartitionFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const NAME = 'ma-moulinette';
    private const SETUP = 1739816022572;
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('Europe/Paris'));

        $repartition = (new Repartition())
            ->setMavenKey(self::MAVEN_KEY)
            ->setName(self::NAME)
            ->setSetup(self::SETUP)
            ->setControl('initial')
            ->setModeCollecte(self::MODE_COLLECTE)
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);

        $manager->persist($repartition);

        $manager->flush();
    }
}
