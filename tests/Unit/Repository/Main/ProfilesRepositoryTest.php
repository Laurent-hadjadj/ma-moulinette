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

namespace App\Tests\Unit\Repository\Main;

use App\Entity\Main\Profiles;
use App\DataFixtures\ProfilesFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ProfilesRepositoryTest]
 */
class ProfilesRepositoryTest extends KernelTestCase
{
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $type = 'RELEASE';

    private static $erreurCode200 = 'Erreur le code retour doit être 200';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new ProfilesFixtures()]);
    }

    public function testCountProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->countProfiles();

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->selectProfiles();

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteProfiles(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->deleteProfiles();

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesLanguage(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->selectProfilesLanguage();

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesRuleCount(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $profilesRepository = $entityManager->getRepository(Profiles::class);
        $r = $profilesRepository->selectProfilesRuleCount();

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }
}
