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
use App\DataFixtures\OwaspTop10Fixtures;
use App\Entity\OwaspTop10;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description OwaspTop10KernelTest]
 */
class OwaspTop10KernelTest extends KernelTestCase
{

    private static $category = "A1 - Attaques d'injection";
    private static $year = 2017;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.owasp_top10_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new OwaspTop10Fixtures()]);
    }

    public function testOwaspTop10FindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $owaspRepository = $entityManager->getRepository(OwaspTop10::class);
        $response = $owaspRepository->findOneBy(['category' => static::$category]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$response], 'CATEGORY: Aucune réponse trouvée');
    }

    public function testOwaspCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $owaspRepository = $entityManager->getRepository(OwaspTop10::class);
        $response = $owaspRepository->findBy(['year' => static::$year]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, $response, 'CATEGORY: Aucune réponse trouvée');
    }
}
