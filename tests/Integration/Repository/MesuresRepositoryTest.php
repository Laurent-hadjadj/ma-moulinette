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
use App\Entity\Mesures;
use App\DataFixtures\MesuresFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description MesuresRepositoryTest]
 */
class MesuresRepositoryTest extends KernelTestCase
{
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $erreurCode200 = 'Erreur le code retour doit être 200';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.mesures_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.mesures');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new MesuresFixtures()], true);
    }

    public function testSelectMesuresVersionLast(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['maven_key' => self::$mavenKey];

        // Appel de la méthode
        $mesuresRepository = $entityManager->getRepository(Mesures::class);
        $r = $mesuresRepository->selectMesuresVersionLast($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertMesures(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = [
            'maven_key' => self::$mavenKey,
            'project_name' => 'Ma-Moulinette',
            'lines' => 22015,
            'ncloc' => 10043,
            'language_distribution' => ['java' => 4278, 'ts' => 18690],
            'files' => 18,
            'classes' => 26,
            'functions' => '52',
            'coverage' => 10.3,
            'duplicated_lines_density' => 5.1,
            'sqale_debt_ratio' => 26.0,
            'issues' => 200,
            'tests' => 123,
            'mode_collecte' => 'TRAITEMENT MANUEL',
            'utilisateur_collecte' => 'laurent.hadjadj@ma-moulinette.fr',
            'date_enregistrement' => new \DateTimeImmutable('2024-04-12 16:23:11')
        ];

        // Appel de la méthode
        $mesuresRepository = $entityManager->getRepository(Mesures::class);
        $r = $mesuresRepository->insertMesures($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteMesuresMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['maven_key' => self::$mavenKey];

        // Appel de la méthode
        $mesuresRepository = $entityManager->getRepository(Mesures::class);
        $r = $mesuresRepository->deleteMesuresMavenKey($map);

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
