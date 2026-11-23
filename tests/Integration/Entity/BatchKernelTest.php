<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Tests\Integration\Entity;

use Doctrine\ORM\EntityManagerInterface;
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

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
        $entityManager = $container->get(EntityManagerInterface::class);

        $batchRepository = $entityManager->getRepository(Batch::class);
        $response = $batchRepository->findOneBy(['responsable' => self::$responsable]);

        $this->assertNotNull($response, 'RESPONSABLE: Aucune réponse trouvée');
    }

    public function testBatchCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $batchRepository = $entityManager->getRepository(Batch::class);
        $response = $batchRepository->findBy(['responsable' => self::$responsable]);

        $this->assertCount(3, $response, 'RESPONSABLE: Aucune réponse trouvée');
    }
}
