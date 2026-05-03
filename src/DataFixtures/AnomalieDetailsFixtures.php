<?php

namespace App\DataFixtures;

use App\Entity\AnomalieDetails;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description AnomalieDetailsFixtures]
 */
class AnomalieDetailsFixtures extends Fixture
{
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $name = 'ma-moulinette';
  private static int $bugBlocker = 7;
  private static int $bugCritical = 0;
  private static int $bugMajor = 44;
  private static int $bugInfo = 37;
  private static int $bugMinor = 0;
  private static int $vulnerabilityBlocker = 0;
  private static int $vulnerabilityCritical = 9;
  private static int $vulnerabilityMajor = 0;
  private static int $vulnerabilityInfo = 0;
  private static int $vulnerabilityMinor = 0;
  private static int $codeSmellBlocker = 0;
  private static int $codeSmellCritical = 4;
  private static int $codeSmellMajor = 109;
  private static int $codeSmellInfo = 72;
  private static int $codeSmellMinor = 13;
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-07-14 19:36:33+02';

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
        $anomalieDetails=(new AnomalieDetails())
            ->setMavenKey(self::$mavenKey)
            ->setName(self::$name)
            ->setBugBlocker(self::$bugBlocker)
            ->setBugCritical(self::$bugCritical)
            ->setBugMajor(self::$bugMajor)
            ->setBugInfo(self::$bugInfo)
            ->setBugMinor(self::$bugMinor)
            ->setVulnerabilityBlocker(self::$vulnerabilityBlocker)
            ->setVulnerabilityCritical(self::$vulnerabilityCritical)
            ->setVulnerabilityMajor(self::$vulnerabilityMajor)
            ->setVulnerabilityInfo(self::$vulnerabilityInfo)
            ->setVulnerabilityMinor(self::$vulnerabilityMinor)
            ->setCodeSmellBlocker(self::$codeSmellBlocker)
            ->setCodeSmellCritical(self::$codeSmellCritical)
            ->setCodeSmellMajor(self::$codeSmellMajor)
            ->setCodeSmellInfo(self::$codeSmellInfo)
            ->setCodeSmellMinor(self::$codeSmellMinor)
            ->setUtilisateurCollecte(self::$utilisateurCollecte)
            ->setModeCollecte($mode)
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
            $manager->persist($anomalieDetails);
        }

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
