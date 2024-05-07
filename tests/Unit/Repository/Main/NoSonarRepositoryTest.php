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

use App\Entity\Main\NoSonar;
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
    private static $rule = 'java:S1309';
    private static $component = 'fr.ma-petite-entreprise:mo-moulinette:
    ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java';
    private static $line = 118;
    private static $dateEnregistrement = '2024-03-26 14:46:38';
    private static $erreurCode200 = 'Erreur le code retour doit être 200';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
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

        $map = ['maven_key' => static::$mavenKey, 'rule'=>static::$rule,
                'component'=>static::$component, 'line' => static::$line,
                'date_enregistrement'=> static::$dateEnregistrement ];

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(NoSonar::class);
        $r = $notesRepository->insertNoSonar($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }


}
