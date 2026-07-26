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
use App\Entity\Todo;
use App\DataFixtures\TodoFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description TodoRepositoryTest]
 */
class TodoRepositoryTest extends KernelTestCase
{

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $rule = 'java:S1135';
    private static string $component = 'fr.ma-moulinette:ma-moulinette:ma-moulinette/src/main/java/fr/ma-petite-entreprise/service/AnalyseTraceService.java';
    private static int $line = 81;
    private static string $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    private static string $erreurCode200 = 'Erreur le code retour doit être 200.';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.todo_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.todo');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new TodoFixtures()], true);
    }

    public function testDeleteTodoMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['maven_key' => self::$mavenKey];

        // Appel de la méthode
        $todoRepository = $entityManager->getRepository(Todo::class);
        $r = $todoRepository->deleteTodoMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectTodoRuleGroupByRule(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['maven_key' => self::$mavenKey];

        // Appel de la méthode
        $todoRepository = $entityManager->getRepository(Todo::class);
        $r = $todoRepository->selectTodoRuleGroupByRule($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectTodoComponentOrderByRule(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['maven_key' => self::$mavenKey];

        // Appel de la méthode
        $todoRepository = $entityManager->getRepository(Todo::class);
        $r = $todoRepository->selectTodoComponentOrderByRule($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertTodo(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = [[
            'maven_key' => self::$mavenKey,
            'rule' => self::$rule,
            'component' => self::$component,
            'line' => self::$line,
            'mode_collecte' => self::$modeCollecte,
            'utilisateur_collecte' => self::$utilisateurCollecte,
            'date_enregistrement' =>  new \DateTimeImmutable(self::$dateEnregistrement)
        ]];

        // Appel de la méthode
        $todoRepository = $entityManager->getRepository(Todo::class);
        $r = $todoRepository->insertTodo($map);

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
