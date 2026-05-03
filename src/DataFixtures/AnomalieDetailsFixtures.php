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
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $name = 'ma-moulinette';
  private static $bugBlocker = 7;
  private static $bugCritical = 0;
  private static $bugMajor = 44;
  private static $bugInfo = 37;
  private static $bugMinor = 0;
  private static $vulnerabilityBlocker = 0;
  private static $vulnerabilityCritical = 9;
  private static $vulnerabilityMajor = 0;
  private static $vulnerabilityInfo = 0;
  private static $vulnerabilityMinor = 0;
  private static $codeSmellBlocker = 0;
  private static $codeSmellCritical = 4;
  private static $codeSmellMajor = 109;
  private static $codeSmellInfo = 72;
  private static $codeSmellMinor = 13;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-07-14 19:36:33+02';

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
