<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Properties;
use App\DataFixtures\PropertiesFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description PropertiesRepositoryTest]
 */
class PropertiesRepositoryTest extends KernelTestCase
{
    private static $type = 'properties';
    private static $projetBd = 100;
    private static $projetSonar = 12;
    private static $profilBd = 12;
    private static $profilSonar = 18;
    private static $dateCreation = '2024-03-26 14:46:38';
    private static $dateModificationProjet = '2024-03-27 10:26:31';
    private static $dateModificationProfil = '2024-04-12 16:23:11';

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
            $sequence = 'ma_moulinette.properties_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new PropertiesFixtures()]);
    }

    public function testGetProperties(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $propertiesRepository = $entityManager->getRepository(Properties::class);
        $r = $propertiesRepository->getProperties(static::$type);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertProperties(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['projet_bd' => static::$projetBd,
                'projet_sonar'=> static::$projetSonar,
                'profil_bd' => static::$profilBd,
                'profil_sonar'=> static::$profilSonar,
                'date_creation' => static::$dateCreation,
                'date_modification_projet' => static::$dateModificationProjet,
                'date_modification_profil' => static::$dateModificationProfil];

        // Appel de la méthode
        $propertiesRepository = $entityManager->getRepository(Properties::class);
        $r = $propertiesRepository->insertProperties($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testUpdatePropertiesProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['projet_bd' => static::$projetBd,
                'projet_sonar'=> static::$projetSonar,
                'date_modification_projet' => new \DateTimeImmutable (static::$dateModificationProjet),
                'type' => static::$type];

        // Appel de la méthode
        $propertiesRepository = $entityManager->getRepository(Properties::class);
        $r = $propertiesRepository->updatePropertiesProjet($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testUpdatePropertiesProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['profil_bd' => static::$profilBd,
                'profil_sonar'=> static::$profilSonar,
                'date_modification_profil' => new \DateTimeImmutable(static::$dateModificationProfil),
                'type' => static::$type];

        // Appel de la méthode
        $propertiesRepository = $entityManager->getRepository(Properties::class);
        $r = $propertiesRepository->updatePropertiesProfiles($map);

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
