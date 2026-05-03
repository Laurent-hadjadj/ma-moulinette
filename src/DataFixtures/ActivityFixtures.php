<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ActivityFixtures]
 */
class ActivityFixtures extends Fixture
{

  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $projectName = 'ma-moulinette';
  private static string $analyseId = 'vtrf14lkiutq9mp';
  private static string $submitterLogin = 'laurent.hadjadj';
  private static string $submittedAt = '2024-07-31 12:26:58+02';
  private static string $startedAt = '2024-07-31 12:27:05+02';
  private static string $executedAt = '2024-07-31 12:27:47+02';
  private static int $executionTime = 42;

  /**
   * [Description for load]
   * Chargement des utilisateurs
   *
   * @param ObjectManager $manager
   *
   * @return void
   *
   * Created at: 05/05/2024 18:43:05 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function load(ObjectManager $manager): void
    {
      $statuses=['SUCCESS', 'FAILED'];

      foreach($statuses as $status){
        $activity=(new Activity())
            ->setMavenKey(self::$mavenKey)
            ->setProjectName(self::$projectName)
            ->setAnalyseId(self::$analyseId)
            ->setStatus($status)
            ->setSubmitterLogin(self::$submitterLogin)
            ->setSubmittedAt(new \DateTimeImmutable(self::$submittedAt))
            ->setStartedAt(new \DateTimeImmutable(self::$startedAt))
            ->setExecutedAt(new \DateTimeImmutable(self::$executedAt))
            ->setExecutionTime(self::$executionTime);
            $manager->persist($activity);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
