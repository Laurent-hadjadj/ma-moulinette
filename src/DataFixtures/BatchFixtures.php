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

use App\Entity\Batch;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création BatchFixturesF.
 * Contrat BatchKernelTest :
 *  - testBatchFindOneBy : findOneBy responsable = 'Laurent HADJADJ' renvoie au moins 1.
 *  - testBatchCount    : findBy responsable = 'Laurent HADJADJ' renvoie 3.
 * Contraintes BDD : `titre` et `portefeuille` sont uniques → valeurs distinctes par
 * ligne. Constructeur init traitementId (Ulid) + dateEnregistrement.
 */
class BatchFixtures extends Fixture
{
    private const RESPONSABLE = 'Laurent HADJADJ';
    private const RESPONSABLE_SHORT = 'lhadjadj';

    public function load(ObjectManager $manager): void
    {
        $batch1 = (new Batch())
            ->setActivated(true)
            ->setAutomatique(false)
            ->setTitre('BATCH-MOULINETTE-A')
            ->setDescription('Batch de collecte des projets ma-moulinette')
            ->setResponsable(self::RESPONSABLE)
            ->setResponsableShort(self::RESPONSABLE_SHORT)
            ->setPortefeuille('MES PROJETS')
            ->setNombreProjet(2)
            ->setExecution('OK');
        $manager->persist($batch1);

        $batch2 = (new Batch())
            ->setActivated(true)
            ->setAutomatique(true)
            ->setTitre('BATCH-LEGACY-B')
            ->setDescription('Batch de collecte des projets legacy')
            ->setResponsable(self::RESPONSABLE)
            ->setResponsableShort(self::RESPONSABLE_SHORT)
            ->setPortefeuille('PROJETS LEGACY')
            ->setNombreProjet(1)
            ->setExecution('KO');
        $manager->persist($batch2);

        $batch3 = (new Batch())
            ->setActivated(false)
            ->setAutomatique(false)
            ->setTitre('BATCH-RND-C')
            ->setDescription('Batch de collecte des projets R&D')
            ->setResponsable(self::RESPONSABLE)
            ->setResponsableShort(self::RESPONSABLE_SHORT)
            ->setPortefeuille('PROJETS R&D')
            ->setNombreProjet(0);
        $manager->persist($batch3);

        $manager->flush();
    }
}
