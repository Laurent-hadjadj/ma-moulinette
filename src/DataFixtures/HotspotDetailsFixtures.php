<?php

namespace App\DataFixtures;

use App\Entity\HotspotDetails;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description HotspotDetailsFixtures]
 */
class HotspotDetailsFixtures extends Fixture
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $version = '1.2.0-RELEASE';
  private static $dateVersion = '2024-07-10 15:26:07+02';
  private static $securityCategory = 'dos';
  private static $ruleKey = 'typescript:S5852';
  private static $ruleName = 'Using slow regular expressions is security-sensitive';
  private static $severity = 'MEDIUM';
  private static $status = 'TO_REVIEW';
  private string $resolution = 'Todo';
  private static $niveau = 2;
  private static $frontend = 1;
  private static $backend = 1;
  private static $autre= 0;
  private static $fileName = 'service-worker-network-first.ts';
  private static $filePath = 'ma-moulinette/angular/src/service-worker-network-first.ts';
  private static $line = 60;
  private static $message = 'Make sure the regex used here, which is vulnerable to super-linear runtime due to backtracking, cannot lead to denial of service.';
  private static $key = 'AZCc06XbgfifxdiJPzw2';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-03-26 14:46:38+02';

  public function load(ObjectManager $manager): void
    {
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $hotspotDetails=(new HotspotDetails())
          ->setMavenKey(static::$mavenKey)
          ->setVersion(static::$version)
          ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
          ->setSecurityCategory(static::$securityCategory)
          ->setRuleKey(static::$ruleKey)
          ->setRuleName(static::$ruleName)
          ->setSeverity(static::$severity)
          ->setStatus(static::$status)
          ->setResolution($this->resolution)
          ->setNiveau(static::$niveau)
          ->setFrontend(static::$frontend)
          ->setBackend(static::$backend)
          ->setAutre(static::$autre)
          ->setFileName(static::$fileName)
          ->setFilePath(static::$filePath)
          ->setLine(static::$line)
          ->setMessage(static::$message)
          ->setKey(static::$key)
          ->setModeCollecte($mode)
          ->setUtilisateurCollecte(static::$utilisateurCollecte)
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($hotspotDetails);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
