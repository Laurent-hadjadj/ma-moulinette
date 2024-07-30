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
            ->setMavenKey(static::$mavenKey)
            ->setName(static::$name)
            ->setBugBlocker(static::$bugBlocker)
            ->setBugCritical(static::$bugCritical)
            ->setBugMajor(static::$bugMajor)
            ->setBugInfo(static::$bugInfo)
            ->setBugMinor(static::$bugMinor)
            ->setVulnerabilityBlocker(static::$vulnerabilityBlocker)
            ->setVulnerabilityCritical(static::$vulnerabilityCritical)
            ->setVulnerabilityMajor(static::$vulnerabilityMajor)
            ->setVulnerabilityInfo(static::$vulnerabilityInfo)
            ->setVulnerabilityMinor(static::$vulnerabilityMinor)
            ->setCodeSmellBlocker(static::$codeSmellBlocker)
            ->setCodeSmellCritical(static::$codeSmellCritical)
            ->setCodeSmellMajor(static::$codeSmellMajor)
            ->setCodeSmellInfo(static::$codeSmellInfo)
            ->setCodeSmellMinor(static::$codeSmellMinor)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setModeCollecte($mode)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
            $manager->persist($anomalieDetails);
        }

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
