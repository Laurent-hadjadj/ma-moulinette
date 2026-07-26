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

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\BatchTraitementFixtures;
use App\Entity\BatchTraitement;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;


/**
 * [Description BatchTraitementKernelTest]
*/
class BatchTraitementKernelTest extends KernelTestCase
{
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static bool $success = true;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.batch_traitement_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.batch_traitement');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new BatchTraitementFixtures()], true);
    }

    public function testBatchTraitementFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $batchTraitementRepository = $entityManager->getRepository(BatchTraitement::class);
        $response = $batchTraitementRepository->findOneBy(['modeCollecte' => self::$modeCollecte]);

        $this->assertNotNull($response, 'MODE_COLLECTE: Aucune réponse trouvée');
    }

    public function testBatchTraitementCountOne(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $batchTraitementRepository = $entityManager->getRepository(BatchTraitement::class);
        $response = $batchTraitementRepository->findBy(['modeCollecte' => self::$modeCollecte]);

        $this->assertCount(3, $response, 'MODE_COLLECTE: Aucune réponse trouvée');
    }

    public function testBatchTraitementCountAll(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $batchTraitementRepository = $entityManager->getRepository(BatchTraitement::class);
        $response = $batchTraitementRepository->findBy(['success' => self::$success]);

        $this->assertCount(5, $response, 'SUCCESS: Aucune réponse trouvée');
    }
}
