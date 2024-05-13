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
    private static $dateCourte = '2022-04-14';
    private static $language = 'java';
    private static $date  = '2022-08-30T18:42:41+0200';
    private static $action = 'ACTIVATED';
    private static $auteur = 'HADJADJ Laurent';
    private static $regle = 'java:S5679';
    private static $description = 'OpenSAML2 should be configured to prevent authentication bypass';
    private static $detail = '{"severity":"MAJOR"}';
    private static $dateEnregistrement = '2024-04-12 16:23:11';

    private static $erreurCode200 = 'Erreur le code retour doit être 200';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
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

        $map=[  'date_courte'=>static::$dateCourte, 'language'=>static::$language,
                'date'=>static::$date, 'action'=>static::$action, 'auteur'=>static::$auteur,
                'regle'=>static::$regle, 'description'=>static::$description,
                'detail'=>static::$detail, 'date_enregistrement'=>static::$dateEnregistrement];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->insertProfilesHistorique($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueAction(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=['language'=>static::$language, 'action'=>static::$action];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueAction($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueDateTri(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=['language'=>static::$language, 'tri'=>'ASC', 'limit'=>1];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueDateTri($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueDateCourteGroupeBy(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=['language'=>static::$language];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueDateCourteGroupeBy($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectProfilesHistoriqueLangageDateCourte(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=['language'=>static::$language, 'date_courte'=>static::$dateCourte];

        // Appel de la méthode
        $profilesHistoriqueRepository = $entityManager->getRepository(ProfilesHistorique::class);
        $r = $profilesHistoriqueRepository->selectProfilesHistoriqueLangageDateCourte($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }
}
