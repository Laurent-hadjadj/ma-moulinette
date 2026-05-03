<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Integration\Repository;

use App\Entity\ProfilesHistorique;
use App\DataFixtures\ProfilesHistoriqueFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ProfilesHistoriqueRepositoryTest]
 */
class ProfilesHistoriqueRepositoryTest extends KernelTestCase
{
    private static string $dateCourte = '2022-04-14';
    private static string $language = 'java';
    private static string $date = '2022-08-30T18:42:41+0200';
    private static string $action = 'ACTIVATED';
    private static string $auteur = 'HADJADJ Laurent';
    private static string $rule = 'java:S5679';
    private static string $description = 'OpenSAML2 should be configured to prevent authentication bypass';
    private static string $detail = '{"severity":"MAJOR"}';
    private static string $dateEnregistrement = '2024-04-12 16:23:11';

    private static string $erreurCode200 = 'Erreur le code retour doit être 200.';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.profiles_historique_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new ProfilesHistoriqueFixtures()]);
    }

    public function testInsertProfilesHistorique(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=[  'date_courte'=>self::$dateCourte, 'language'=>self::$language,
                'date'=>self::$date, 'action'=>self::$action, 'auteur'=>self::$auteur,
                'rule'=>self::$rule, 'description'=>self::$description,
                'detail'=>self::$detail, 'date_enregistrement'=> new \DateTimeImmutable(self::$dateEnregistrement)];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->insertProfilesHistorique($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueAction(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=[ 'language' => self::$language, 'action' => self::$action];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueAction($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueDateTri(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['language' => self::$language, 'tri' => 'ASC', 'limit' => 1];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueDateTri($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueDateCourteGroupeBy(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['language' => self::$language];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueDateCourteGroupeBy($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueLangageDateCourte(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['language' => self::$language, 'date_courte' => self::$dateCourte];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueLangageDateCourte($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
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
