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

namespace App\Tests\Integration\Entity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\ActivityHistoriqueFixtures;
use App\Entity\ActivityHistorique;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description ActivityHistoriqueKernelTest]
 */
class ActivityHistoriqueKernelTest extends KernelTestCase
{

    private static int $year = 2024;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.activity_historique_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.activity_historique');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new ActivityHistoriqueFixtures()], true);
    }

    public function testActivityHistoriqueFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $activityHistoriqueRepository = $entityManager->getRepository(ActivityHistorique::class);
        $response = $activityHistoriqueRepository->findOneBy(['year' => self::$year]);

        $this->assertCount(1, [$response], 'ANNÉE: Aucune réponse trouvée');
    }

}
