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

namespace App\Tests\Unit\Repository;

use App\Entity\NoSonar;
use App\DataFixtures\NoSonarFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


/**
 * [Description NoSonarRepositoryTest]
 */
class NoSonarRepositoryTest extends KernelTestCase
{

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $erreurCode200 = 'Erreur le code retour doit être 200.';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.no_sonar_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new NoSonarFixtures()]);
    }

    public function testDeleteNoSonarMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => static::$mavenKey];

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(NoSonar::class);
        $r = $notesRepository->deleteNoSonarMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectNoSonarRuleGroupByRule(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => static::$mavenKey];

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(NoSonar::class);
        $r = $notesRepository->selectNoSonarRuleGroupByRule($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertNoSonar(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = [['maven_key' => static::$mavenKey, 'rule' => 'java:S1309',
                'component'=> 'fr.ma-petite-entreprise:mo-moulinette:
                ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java', 'line' => 118,
                'mode_collecte' => 'TRAITEMENT MANUEL','utilisateur_collecte' => 'laurent.hadjadj@ma-petite-entreprise.fr',
                'date_enregistrement'=> new \DateTimeImmutable('2024-03-26 14:46:38')]];

        // Appel de la méthode
        $nosonarRepository = $entityManager->getRepository(NoSonar::class);
        $r = $nosonarRepository->insertNoSonar($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // On se déconnecte pour éviter des problèmes de mémoires
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->close();
        $entityManager = null;
    }

}
