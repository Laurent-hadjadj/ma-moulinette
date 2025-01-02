<?php

namespace App\DataFixtures;

use App\Entity\ActivityHistorique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ActivityHistoriqueFixtures]
 */
class ActivityHistoriqueFixtures extends Fixture
{

  private static $year = 2024;
  private static $day = 326;
  private static $analyse = 1253;
  private static $analyseAverage = 87.3;
  private static $success = 1249;
  private static $failed = 4;
  private static $successRate = 0.99;
  private static $maxTime = 34;
  private static $dateEnregistrement = '2024-07-14 19:36:33+02';

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
      $activityHistorique=(new ActivityHistorique())
          ->setYear(static::$year)
          ->setDay(static::$day)
          ->setAnalyse(static::$analyse)
          ->setAnalyseAverage(static::$analyseAverage)
          ->setSuccess(static::$success)
          ->setFailed(static::$failed)
          ->setSuccessRate(static::$successRate)
          ->setMaxTime(static::$maxTime)
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
      $manager->persist($activityHistorique);
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
