<?php

namespace App\DataFixtures;

use App\Entity\Activite;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ActiviteFixtures]
 */
class ActiviteFixtures extends Fixture
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $projectName = 'ma-moulinette';
  private static $analyseId = 'vtrf14lkiutq9mp';
  private static $submitterLogin = 'laurent.hadjadj';
  private static $submittedAt = '2024-07-31 12:26:58+02';
  private static $startedAt = '2024-07-31 12:27:05+02';
  private static $executedAt = '2024-07-31 12:27:47+02';
  private static $executionTime = "42";

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
        $activite=(new Activite())
            ->setMavenKey(static::$mavenKey)
            ->setProjectName(static::$projectName)
            ->setAnalyseId(static::$analyseId)
            ->setStatus($status)
            ->setSubmitterLogin(static::$submitterLogin)
            ->setSubmittedAt(new \DateTimeImmutable(static::$submittedAt))
            ->setStartedAt(new \DateTimeImmutable(static::$startedAt))
            ->setExecutedAt(new \DateTimeImmutable(static::$executedAt))
            ->setExecutionTime(static::$executionTime);
            $manager->persist($activite);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
