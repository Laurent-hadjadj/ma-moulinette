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
use App\DataFixtures\OwaspTop10Fixtures;
use App\Entity\OwaspTop10;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description OwaspTop10KernelTest]
 */
class OwaspTop10KernelTest extends KernelTestCase
{

    private static string $category = "A1 - Attaques d'injection";
    private static int $year = 2017;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.owasp_top10_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.owasp_top10');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new OwaspTop10Fixtures()], true);
    }

    public function testOwaspTop10FindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $owaspRepository = $entityManager->getRepository(OwaspTop10::class);
        $response = $owaspRepository->findOneBy(['category' => self::$category]);

        $this->assertNotNull($response, 'CATEGORY: Aucune réponse trouvée');
    }

    public function testOwaspCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $owaspRepository = $entityManager->getRepository(OwaspTop10::class);
        $response = $owaspRepository->findBy(['year' => self::$year]);

        $this->assertCount(1, $response, 'CATEGORY: Aucune réponse trouvée');
    }
}
