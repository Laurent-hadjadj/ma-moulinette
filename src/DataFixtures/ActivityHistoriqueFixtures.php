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

  private static int $year = 2024;
  private static int $day = 326;
  private static int $analyse = 1253;
  private static float $analyseAverage = 87.3;
  private static int $success = 1249;
  private static int $failed = 4;
  private static float $successRate = 0.99;
  private static int $maxTime = 34;
  private static string $dateEnregistrement = '2024-07-14 19:36:33+02';

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
      $ActivityHistorique=(new ActivityHistorique())
          ->setYear(self::$year)
          ->setDay(self::$day)
          ->setAnalyse(self::$analyse)
          ->setAnalyseAverage(self::$analyseAverage)
          ->setSuccess(self::$success)
          ->setFailed(self::$failed)
          ->setSuccessRate(self::$successRate)
          ->setMaxTime(self::$maxTime)
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
      $manager->persist($ActivityHistorique);
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
