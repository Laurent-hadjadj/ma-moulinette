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

namespace App\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Logger;
use App\DataFixtures\LoggerFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description LoggerRepositoryTest]
 */
class LoggerRepositoryTest extends KernelTestCase
{

    private static string $erreurCode200 = 'Erreur le code retour doit être 200.';
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.logger_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.logger');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new LoggerFixtures()], true);
    }

    public function testDeleteLoggerMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $map = ['maven_key' => self::$mavenKey];

        $notesRepository = $entityManager->getRepository(Logger::class);
        $r = $notesRepository->deleteLoggerMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectLogger(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $map = ['maven_key' => self::$mavenKey];

        $loggerRepository = $entityManager->getRepository(Logger::class);
        $r = $loggerRepository->selectLogger($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertLogger(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = [
            'maven_key' => self::$mavenKey,
            'logger_info' => 14,
            'logger_warn' => 0,
            'logger_error' => 15,
            'logger_debug' => 8,
            'mode_collecte' => 'TRAITEMENT AUTOMATIQUE',
            'utilisateur_collecte' => 'laurent.hadjadj@ma-moulinette.fr',
            'date_enregistrement' => new \DateTimeImmutable('2024-03-26 14:46:38+02')
        ];

        // Appel de la méthode
        $loggerRepository = $entityManager->getRepository(Logger::class);
        $r = $loggerRepository->insertLogger($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // On se déconnecte pour éviter des problèmes de mémoires
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
        $entityManager = null;
    }
}
