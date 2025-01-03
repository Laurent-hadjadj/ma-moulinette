<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Kernel;

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
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $result = true;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.batch_traitement_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new BatchTraitementFixtures()]);
    }

    public function testBatchTraitementFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $batchTraitementRepository = $entityManager->getRepository(BatchTraitement::class);
        $response = $batchTraitementRepository->findOneBy(['modeCollecte' => static::$modeCollecte]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée.');
        $this->assertCount(1, [$response], 'MODE_COLLECTE: Aucune réponse trouvée');
    }

    public function testBatchTraitementCountOne(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $batchTraitementRepository = $entityManager->getRepository(BatchTraitement::class);
        $response = $batchTraitementRepository->findBy(['modeCollecte' => static::$modeCollecte]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(3, $response, 'MODE_COLLECTE: Aucune réponse trouvée');
    }

    public function testBatchTraitementCountAll(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $batchTraitementRepository = $entityManager->getRepository(BatchTraitement::class);
        $response = $batchTraitementRepository->findBy(['result' => static::$result]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée.');
        $this->assertCount(5, $response, 'RESULT: Aucune réponse trouvée');
    }
}
