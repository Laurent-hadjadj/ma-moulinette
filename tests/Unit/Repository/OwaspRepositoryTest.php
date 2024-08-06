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

use App\Entity\Owasp;
use App\DataFixtures\OwaspFixtures;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description UtilisateurRepositoryTest]
 */
class OwaspRepositoryTest extends KernelTestCase
{

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $version = '1.2.0-RELEASE';
    private static $dateVersion = '2024-07-10 15:26:07+02';
    private static $effortTotal = 0;
    private static $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-03-26 14:46:38+02';

    private static $erreurCode200 = 'Erreur le code retour doit être 200.';

    /**
     * [Description for setUp]
     * Création des owasp en base depuis les fixtures
     *
     * @return void
     *
     * Created at: 05/05/2024 18:15:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.owasp_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new OwaspFixtures()]);
    }

    public function testSelectOwaspOrderByDateEnregistrement(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => static::$mavenKey];

        // Appel de la méthode
        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->selectOwaspOrderByDateEnregistrement($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteOwaspMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => static::$mavenKey];

        // Appel de la méthode
        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->deleteOwaspMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertOwasp(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = [];

        // Ajouter les propriétés de l'objet $owasp au tableau $map
        $map['maven_key'] = static::$mavenKey;
        $map['version'] = static::$version;
        $map['date_version'] = static::$dateVersion;
        $map['effort_total'] = static::$effortTotal;

        for ($i = 0; $i < 10; $i++) {
            $map["a" . ($i + 1)] = static::$a[$i];
            $map["a" . ($i + 1) . "_blocker"] = static::$aBlocker[$i];
            $map["a" . ($i + 1) . "_critical"] = static::$aCritical[$i];
            $map["a" . ($i + 1) . "_major"] = static::$aMajor[$i];
            $map["a" . ($i + 1) . "_info"] = static::$aInfo[$i];
            $map["a" . ($i + 1) . "_minor"] = static::$aMinor[$i];
        }

        $map['mode_collecte'] = static::$modeCollecte;
        $map['utilisateur_collecte'] = static::$utilisateurCollecte;
        $map['date_enregistrement'] = new \DateTimeImmutable(static::$dateEnregistrement);

        // Appel de la méthode
        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->insertOwasp($map);

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
