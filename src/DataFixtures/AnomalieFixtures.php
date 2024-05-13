<?php

namespace App\DataFixtures;

use App\Entity\Anomalie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description AnomalieFixtures]
 */
class AnomalieFixtures extends Fixture
{

  private static $mavenKey = 'fr.map-petite-entreprise:ma-moulinette';
  private static $projectName = 'ma-moulinette';
  private static $anomalieTotal = 1956;
  private static $detteMinute = 19586;
  private static $detteReliabilityMinute = 107;
  private static $detteVulnerabilityMinute = 0;
  private static $detteCodeSmellMinute = 7369;
  private static $detteReliability = '0h:5min';
  private static $detteVulnerability = '0h:0min';
  private static $dette = '4d, 19h:32min';
  private static $detteCodeSmell = '5d, 2h:49min';
  private static $frontend = 806;
  private static $backend = 0;
  private static $autre = 0;
  private static $blocker = 0;
  private static $critical = 0;
  private static $major = 4750;
  private static $info = 0;
  private static $minor = 222;
  private static $bug = 0;
  private static $vulnerability = 0;
  private static $codeSmell = 801;
  private static $dateEnregistrement = '2024-03-25 12:26:58';

  /**
   * [Description for load]
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
      $anomalie=(new Anomalie())
          ->setMavenKey(static::$mavenKey)
          ->setProjectName(static::$projectName)
          ->setAnomalieTotal(static::$anomalieTotal)
          ->setDetteMinute(static::$detteMinute)
          ->setDetteReliabilityMinute(static::$detteReliabilityMinute)
          ->setDetteVulnerabilityMinute(static::$detteVulnerabilityMinute)
          ->setDetteCodeSmellMinute(static::$detteCodeSmellMinute)
          ->setDetteReliability(static::$detteReliability)
          ->setDetteVulnerability(static::$detteVulnerability)
          ->setDetteCodeSmell(static::$detteCodeSmell)
          ->setDette(static::$dette)
          ->setFrontend(static::$frontend)
          ->setBackend(static::$backend)
          ->setAutre(static::$autre)
          ->setBlocker(static::$blocker)
          ->setCritical(static::$critical)
          ->setMajor(static::$major)
          ->setInfo(static::$info)
          ->setMinor(static::$minor)
          ->setBug(static::$bug)
          ->setVulnerability(static::$vulnerability)
          ->setCodeSmell(static::$codeSmell)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($anomalie);


      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
