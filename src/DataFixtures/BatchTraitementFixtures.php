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

use App\Entity\BatchTraitement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création BatchTraitementFixtures.
 * Contrat BatchTraitementKernelTest :
 *  - testBatchTraitementFindOneBy  : findOneBy modeCollecte = 'TRAITEMENT MANUEL' (>=1).
 *  - testBatchTraitementCountOne   : findBy modeCollecte = 'TRAITEMENT MANUEL' = 3.
 *  - testBatchTraitementCountAll   : findBy success = true = 5.
 * Total 5 lignes avec success=true ; les 3 « TRAITEMENT MANUEL » doivent être inclus
 * dans les 5 success=true. Constructeur (titre, portefeuille, responsable,
 * responsableShort, modeCollecte, nombreProjet) + init traitementId/dateEnregistrement.
 */
class BatchTraitementFixtures extends Fixture
{
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const RESPONSABLE = 'Laurent HADJADJ';
    private const RESPONSABLE_SHORT = 'lhadjadj';

public function load(ObjectManager $manager): void
    {
        $debut = new \DateTimeImmutable('2026-01-01 09:00:00');
        $fin   = new \DateTimeImmutable('2026-01-01 09:30:00');

        // 3 traitements en mode TRAITEMENT MANUEL et success=true
        $t1 = new BatchTraitement(
            'TRAITEMENT-M-1',
            'MES PROJETS',
            self::RESPONSABLE,
            self::RESPONSABLE_SHORT,
            self::MODE_COLLECTE,
            2
        );
        $t1->setActivated(true)
            ->setSuccess(true)
            ->setInProgress(false)
            ->setDebutTraitement($debut)
            ->setFinTraitement($fin);
        $manager->persist($t1);

        $t2 = new BatchTraitement(
            'TRAITEMENT-M-2',
            'PROJETS LEGACY',
            self::RESPONSABLE,
            self::RESPONSABLE_SHORT,
            self::MODE_COLLECTE,
            1
        );
        $t2->setActivated(true)
            ->setSuccess(true)
            ->setInProgress(false)
            ->setDebutTraitement($debut)
            ->setFinTraitement($fin);
        $manager->persist($t2);

        $t3 = new BatchTraitement(
            'TRAITEMENT-M-3',
            'PROJETS R&D',
            self::RESPONSABLE,
            self::RESPONSABLE_SHORT,
            self::MODE_COLLECTE,
            0
        );
        $t3->setActivated(true)
            ->setSuccess(true)
            ->setInProgress(false);
        $manager->persist($t3);

        // 2 traitements supplémentaires success=true en modes différents
        $t4 = new BatchTraitement(
            'TRAITEMENT-A-1',
            'MES PROJETS',
            self::RESPONSABLE,
            self::RESPONSABLE_SHORT,
            'TRAITEMENT AUTOMATIQUE',
            5
        );
        $t4->setActivated(true)
            ->setSuccess(true)
            ->setInProgress(false)
            ->setDebutTraitement($debut)
            ->setFinTraitement($fin);
        $manager->persist($t4);

        $t5 = new BatchTraitement(
            'COLLECTE-1',
            'PROJETS R&D',
            self::RESPONSABLE,
            self::RESPONSABLE_SHORT,
            'COLLECTE',
            3
        );
        $t5->setActivated(true)
            ->setSuccess(true)
            ->setInProgress(false)
            ->setDebutTraitement($debut)
            ->setFinTraitement($fin);
        $manager->persist($t5);

        $manager->flush();
    }
}
