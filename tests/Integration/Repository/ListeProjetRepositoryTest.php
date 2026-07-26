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
use App\Entity\ListeProjet;
use App\DataFixtures\ListeProjetFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ListeProjetRepositoryTest]
 */
class ListeProjetRepositoryTest extends KernelTestCase
{
    private static string $visibility = 'private';
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
            $sequence = 'ma_moulinette.liste_projet_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.liste_projet');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new ListeProjetFixtures()], true);
    }

    public function testCountListeProjetVisibility(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $notesRepository = $entityManager->getRepository(ListeProjet::class);
        $r = $notesRepository->countListeProjetVisibility(self::$visibility);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testCountListeProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $listeProjetRepository = $entityManager->getRepository(ListeProjet::class);
        $r = $listeProjetRepository->countListeProjet();

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testCountListeProjetTags(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $listeProjetRepository = $entityManager->getRepository(ListeProjet::class);
        $r = $listeProjetRepository->countListeProjetTags();

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectListeProjetByGroupe(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['clause_where'=>"tag LIKE 'ma-moulinette%' OR tag LIKE '2048%'" ];

        // Appel de la méthode
        $listeProjetRepository = $entityManager->getRepository(ListeProjet::class);
        $r = $listeProjetRepository->selectListeProjetByGroupe($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteListeProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $listeProjetRepository = $entityManager->getRepository(ListeProjet::class);
        $r = $listeProjetRepository->deleteListeProjet();

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
