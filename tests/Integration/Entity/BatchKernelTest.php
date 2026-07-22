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
use App\DataFixtures\BatchFixtures;
use App\Entity\Batch;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description BatchKernelTest]
 */
class BatchKernelTest extends KernelTestCase
{

    private static string $responsable = 'Laurent HADJADJ';

    /**
     * [Description for setUp]
     *
     * @return void
     *
     * Created at: 03/01/2025 09:28:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.batch_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.batch');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new BatchFixtures()], true);
    }

    public function testBatchFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $batchRepository = $entityManager->getRepository(Batch::class);
        $response = $batchRepository->findOneBy(['responsable' => self::$responsable]);

        $this->assertCount(1, [$response], 'RESPONSABLE: Aucune réponse trouvée');
    }

    public function testBatchCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $batchRepository = $entityManager->getRepository(Batch::class);
        $response = $batchRepository->findBy(['responsable' => self::$responsable]);

        $this->assertCount(3, $response, 'RESPONSABLE: Aucune réponse trouvée');
    }
}
