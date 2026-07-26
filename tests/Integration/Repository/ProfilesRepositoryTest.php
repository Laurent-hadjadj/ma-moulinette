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
use App\Entity\Profiles;
use App\DataFixtures\ProfilesFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ProfilesRepositoryTest]
 */
class ProfilesRepositoryTest extends KernelTestCase
{

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
            $sequence = 'ma_moulinette.profiles_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.profiles');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new ProfilesFixtures()], true);
    }

    public function testCountProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r1 = $profilesRepository->countProfiles('true', 'css');
        $r2 = $profilesRepository->countProfiles('true', null);
        $r3 = $profilesRepository->countProfiles('false', 'css');
        $r4 = $profilesRepository->countProfiles('false', null);

        // Assert
        $this->assertEquals(200, $r1['code'], self::$erreurCode200);
        $this->assertEmpty($r1['erreur'], $r1['erreur']);
        $this->assertEquals(200, $r2['code'], self::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
        $this->assertEquals(200, $r3['code'], self::$erreurCode200);
        $this->assertEmpty($r3['erreur'], $r3['erreur']);
        $this->assertEquals(200, $r4['code'], self::$erreurCode200);
        $this->assertEmpty($r4['erreur'], $r4['erreur']);
    }

    public function testSelectProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r1 = $profilesRepository->selectProfiles('true', 'css');
        $r2 = $profilesRepository->selectProfiles('true', null);
        $r3 = $profilesRepository->selectProfiles('false', 'css');
        $r4 = $profilesRepository->selectProfiles('false', null);

        // Assert
        $this->assertEquals(200, $r1['code'], self::$erreurCode200);
        $this->assertEmpty($r1['erreur'], $r1['erreur']);
        $this->assertEquals(200, $r2['code'], self::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
        $this->assertEquals(200, $r3['code'], self::$erreurCode200);
        $this->assertEmpty($r3['erreur'], $r3['erreur']);
        $this->assertEquals(200, $r4['code'], self::$erreurCode200);
        $this->assertEmpty($r4['erreur'], $r4['erreur']);
    }

    public function testDeleteProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->deleteProfiles();

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesLanguage(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->selectProfilesLanguage();

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesRuleCount(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->selectProfilesRuleCount();

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $map = ['profiles' => [
            ['key' => 'AXyXMubJRtAGLwAs7Zcv', 'name'  => 'Ma-Petite-Entreprise v1.0.0 (2024)', 'languageName' => 'css', 'isDefault' => true, 'activeRuleCount' => 31, 'rulesUpdatedAt' => '2024-04-13 12:10:51+01', ]],
            'date_enregistrement' => new \DateTimeImmutable('2024-04-12 16:23:11+01')];
        $r = $profilesRepository->insertProfiles($map);
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
