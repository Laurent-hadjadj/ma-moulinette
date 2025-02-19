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
use App\DataFixtures\RepartitionFixtures;
use App\Entity\Repartition;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description RepartitionKernelTest]
 */
class RepartitionKernelTest extends KernelTestCase
{

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $setup = '1739816022572';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.repartition_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new RepartitionFixtures()]);
    }

    public function testRepartitionFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $repartitionRepository = $entityManager->getRepository(Repartition::class);
        $response = $repartitionRepository->findOneBy(['mavenKey' => static::$mavenKey, 'setup' => static::$setup]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$response], 'MavenKey & Setup: Aucune réponse trouvée');
    }

    public function testRepartitionCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $repartitionRepository = $entityManager->getRepository(Repartition::class);
        $response = $repartitionRepository->findBy(['setup' => static::$setup]);

        $this->assertNotNull($response, 'Une entité doit être présente.');
        $this->assertCount(1, $response, 'Une valeur pour setup doit être trouvée.');
    }

}
