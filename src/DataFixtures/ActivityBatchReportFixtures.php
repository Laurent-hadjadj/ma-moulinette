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





  private static $lastError = ['Erreur inconnue.'];


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
