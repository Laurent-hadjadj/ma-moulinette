<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Historique;
use App\DataFixtures\HistoriqueFixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description HistoriqueRepositoryTest]
 */
class HistoriqueRepositoryTest extends KernelTestCase
{
    private static $erreurCode200 = 'Erreur le code retour doit être 200';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Pas de séquence, car clé composite
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new HistoriqueFixtures()]);
    }

    public function testCountHistoriqueProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->countHistoriqueProjet($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testGetProjetFavori(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $where = " maven_key='fr.ma-petite-entreprise:ma-moulinette' AND version='1.2.0-RELEASE'";

        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->getProjetFavori($where);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testUpdateHistoriqueReference(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
            'initial' => true, 'version' => '1.2.0-RELEASE', 'date_version' => '2024-07-12 16:34:46'];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->updateHistoriqueReference($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteHistoriqueProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
                'version' => '1.5.0-RELEASE', 'date_version' => '2024-08-18 15:54:26'];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->deleteHistoriqueProjet($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectUnionHistoriqueProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
                'limit' => 1];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectUnionHistoriqueProjet($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectUnionHistoriqueAnomalie(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
                'limit' => 1];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectUnionHistoriqueAnomalie($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectUnionHistoriqueDetails(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
                'limit' => 1];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectUnionHistoriqueDetails($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectHistoriqueAnomalieGraphique(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
                'limit' => 1];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectHistoriqueAnomalieGraphique($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertHistoriqueAjoutProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
        'analyse_key' => 'AZCc05qWgfifxdiJPzns', 'version' => '1.5.0-RELEASE',
        'date_version' => '2024-08-18 15:54:26', 'nom_projet' => 'ma-moulinette',
        'version_release' => 2, 'version_snapshot' => 0, 'version_autre' => 1,
        'suppress_warning' => 8, 'no_sonar'=> 0,  'todo' => 17, 'logger_info' => 14, 'logger_warn' => 0, 'logger_error' => 15, 'logger_debug' => 8,     'nombre_ligne' => 17049, 'nombre_ligne_code' => 8928, 'coverage' => 50.1,
        'duplicated_lines_density' => 0.2, 'sqale_debt_ratio' => 1, 'tests' => 55,
        'violations' => 295, 'dette' => 3054, 'nombre_bug' => 88,
        'nombre_vulnerability' => 9, 'nombre_code_smell' => 198, 'bug_blocker' => 7, 'bug_critical' => 0, 'bug_major' => 44, 'bug_minor' => 0, 'bug_info' => 37, 'vulnerability_blocker' => 0,  'vulnerability_critical' => 9,
        'vulnerability_major' => 0, 'vulnerability_minor' => 0,  'vulnerability_info' => 0,  'code_smell_blocker' => 0,    'code_smell_critical' => 4, 'code_smell_major' => 109, 'code_smell_minor' => 13, 'code_smell_info' => 72, 'frontend' => 21, 'backend' => 136,
        'autre' => 0,  'nombre_anomalie_bloquant' => 7,
        'nombre_anomalie_critique' => 13, 'nombre_anomalie_majeur' => 153,
        'nombre_anomalie_mineur' => 13, 'nombre_anomalie_info' => 109,
        'note_reliability' =>'E', 'note_security'=>'D', 'note_sqale' => 'A',
        'note_hotspot' => 'A',  'nombre_hotspot' => 0, 'hotspot_high' => 0,
        'hotspot_medium' => 0, 'hotspot_low' => 0, 'initial' => true,     'mode_collecte' => 'COLLECTE', 'utilisateur_collecte' => 'admin@ma-moulinette.fr', 'date_enregistrement' => new \DateTimeImmutable('2024-08-28 14:25:15+02')];

        $json="";

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->insertHistoriqueAjoutProjet($map, $json);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);

        $r = $historiqueRepository->insertHistoriqueAjoutProjet($map, $json);
        $this->assertEquals(23505, $r['code'], 'Doublon détecté');
    }

    public function testSelectHistoriqueProjetByDate(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectHistoriqueProjetByDate($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectHistoriqueProjetLast(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectHistoriqueProjetLast($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectHistoriqueProjetReference(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectHistoriqueProjetReference($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectHistoriqueProjetFavori(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['clause_where' => "maven_key = 'fr.ma-petite-entreprise:ma-moulinette'",'nombre_projet_favori' => 5];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectHistoriqueProjetFavori($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectHistoriqueIsValide(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        // Appel de la méthode
        $historiqueRepository = $entityManager->getRepository(Historique::class);
        $r = $historiqueRepository->selectHistoriqueIsValide($map);

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
