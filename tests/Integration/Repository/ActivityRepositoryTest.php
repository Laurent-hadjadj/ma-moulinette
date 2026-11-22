<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Activity;
use App\DataFixtures\ActivityFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ActivityRepositoryTest]
 */
class ActivityRepositoryTest extends KernelTestCase
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
            $sequence = 'ma_moulinette.activity_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.activity');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new ActivityFixtures()], true);
    }

    public function testInsertSelectActivite(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->selectActivity($year);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertActivity(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $map = [[
            'maven_key' => 'fr.ma-moulinette:ma-moulinette',
            'project_name' => 'ma-moulinette',
            'analyse_id' => 'vtrf14lkiutq9mp',
            'status' => 'SUCCESS',
            'submitter_login' => 'laurent.hadjadj',
            'submitted_at' => new  \DateTime('2024-07-31 12:26:58+02'),
            'started_at' => new  \DateTime('2024-07-31 12:27:05+02'),
            'executed_at' => new  \DateTime('2024-07-31 12:27:47+02'),
            'execution_time' => 42
        ]];

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->insertActivity($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testNombreJourAnneeDonnee(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->nombreJourAnneeDonnee($year);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testTempsExecutionMax(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->tempsExecutionMax($year);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testNombreStatus(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;
        $status = 'SUCCESS';

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->nombreStatus($year, $status);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }


    public function testNombreAnalyse(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->nombreAnalyse($year);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDernierDate(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;
        $yearNull = null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->dernierDate($year);
        $r2 = $activityRepository->dernierDate($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], self::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testPremiereDate(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;
        $yearNull = null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->premiereDate($year);
        $r2 = $activityRepository->premiereDate($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], self::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testListeProjectAnalyse(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;
        $yearNull = null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->listeProjectAnalyse($year);
        $r2 = $activityRepository->listeProjectAnalyse($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], self::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testListeAnalyseJour(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;
        $yearNull = null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->listeAnalyseJour($year);
        $r2 = $activityRepository->listeAnalyseJour($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], self::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testListeAnalyseProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Appel de la méthode
        $year = 2024;
        $yearNull = null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->listeAnalyseProjet($year);
        $r2 = $activityRepository->listeAnalyseProjet($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], self::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    /**
     * MODIF 2026-11-19 : nouvelle méthode pour le bloc "Aujourd'hui (par
     * heure)" de la page Activité.
     */
    public function testDistributionHoraireJourGroupeParHeure(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $jour = new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris'));
        $activityRepository = $entityManager->getRepository(Activity::class);

        $insert = static function (string $heureMinute) use ($entityManager, $jour): void {
            $executedAt = $jour->modify($heureMinute);
            $entityManager->getConnection()->executeStatement(
                'INSERT INTO ma_moulinette.activity
                    (maven_key, project_name, analyse_id, status, submitter_login, submitted_at, started_at, executed_at, execution_time)
                 VALUES (:mk, :pn, :aid, :st, :sub, :sa, :sta, :ea, :et)',
                [
                    'mk' => 'fr.ma-moulinette:ma-moulinette',
                    'pn' => 'ma-moulinette',
                    'aid' => 'AY_test_horaire_' . $heureMinute,
                    'st' => 'SUCCESS',
                    'sub' => 'ci-pipeline',
                    'sa' => $executedAt->modify('-60 seconds')->format('Y-m-d H:i:sO'),
                    'sta' => $executedAt->modify('-10 seconds')->format('Y-m-d H:i:sO'),
                    'ea' => $executedAt->format('Y-m-d H:i:sO'),
                    'et' => 12000,
                ]
            );
        };
        $insert('09:15');
        $insert('09:45');
        $insert('14:30');

        $debutJour = $jour->setTime(0, 0);
        $finJour = $debutJour->modify('+1 day');
        $r = $activityRepository->distributionHoraireJour($debutJour, $finJour);

        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);

        $parHeure = [];
        foreach ($r['request'] as $row) {
            $parHeure[(int) $row['heure']] = (int) $row['analyse'];
        }
        $this->assertSame(2, $parHeure[9] ?? 0);
        $this->assertSame(1, $parHeure[14] ?? 0);
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
