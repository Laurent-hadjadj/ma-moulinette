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
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $projectName = 'ma-moulinette';
  private static int $anomalieTotal = 1956;
  private static int $detteMinute = 19586;
  private static int $detteReliabilityMinute = 107;
  private static int $detteVulnerabilityMinute = 0;
  private static int $detteCodeSmellMinute = 7369;
  private static string $detteReliability = '0h:5min';
  private static string $detteVulnerability = '0h:0min';
  private static string $dette = '4d, 19h:32min';
  private static string $detteCodeSmell = '5d, 2h:49min';
  private static int $frontend = 806;
  private static int $backend = 0;
  private static int $autre = 0;
  private static int $inconnu = 1;
  private static int $blocker = 0;
  private static int $critical = 0;
  private static int $major = 4750;
  private static int $info = 0;
  private static int $minor = 222;
  private static int $bug = 0;
  private static int $vulnerability = 0;
  private static int $codeSmell = 801;
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-06-28 17:55:45+02';

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
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $anomalie=(new Anomalie())
            ->setMavenKey(self::$mavenKey)
            ->setProjectName(self::$projectName)
            ->setAnomalieTotal(self::$anomalieTotal)
            ->setDetteMinute(self::$detteMinute)
            ->setDetteReliabilityMinute(self::$detteReliabilityMinute)
            ->setDetteVulnerabilityMinute(self::$detteVulnerabilityMinute)
            ->setDetteCodeSmellMinute(self::$detteCodeSmellMinute)
            ->setDetteReliability(self::$detteReliability)
            ->setDetteVulnerability(self::$detteVulnerability)
            ->setDetteCodeSmell(self::$detteCodeSmell)
            ->setDette(self::$dette)
            ->setFrontend(self::$frontend)
            ->setBackend(self::$backend)
            ->setAutre(self::$autre)
            ->setInconnu(self::$inconnu)
            ->setBlocker(self::$blocker)
            ->setCritical(self::$critical)
            ->setMajor(self::$major)
            ->setInfo(self::$info)
            ->setMinor(self::$minor)
            ->setBug(self::$bug)
            ->setVulnerability(self::$vulnerability)
            ->setCodeSmell(self::$codeSmell)
            ->setUtilisateurCollecte(self::$utilisateurCollecte)
            ->setModeCollecte($mode)
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
            $manager->persist($anomalie);
        }

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
