<?php

namespace App\DataFixtures;

use App\Entity\ActivityBatchReport;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ActivityBatchReportFixtures]
 */
class ActivityBatchReportFixtures extends Fixture
{

  private static $dateStart = '2025-01-01 12:26:58+01';
  private static $dateEnd = '2025-01-01 12:27:12+01';
  private static $taskCount = 12;
  private static $taskDone = 11;
  private static $page = 1;
  private static $lastError = ['Erreur inconnue.'];
  private static $dateEnregistrement = '2024-07-31 12:27:05+02';

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

        $activityReport=(new ActivityBatchReport())
          ->setDateStart(new \DateTimeImmutable(self::$dateStart))
          ->setDateEnd(new \DateTimeImmutable(self::$dateEnd))
          ->setTaskCount(self::$taskCount)
          ->setTaskDone(self::$taskDone)
          ->setPage(self::$page)
          ->setLastError(self::$lastError)
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
        $manager->persist($activityReport);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
