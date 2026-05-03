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

  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $version = '1.2.0-RELEASE';
  private static string $dateVersion = '2024-07-10 15:26:07+02';
  private static string $securityCategory = 'dos';
  private static string $ruleKey = 'typescript:S5852';
  private static string $ruleName = 'Using slow regular expressions is security-sensitive';
  private static string $severity = 'MEDIUM';
  private static string $status = 'TO_REVIEW';
  private string $resolution = 'Todo';
  private static int $niveau = 2;
  private static int $frontend = 1;
  private static int $backend = 1;
  private static int $autre = 0;
  private static string $fileName = 'service-worker-network-first.ts';
  private static string $filePath = 'ma-moulinette/angular/src/service-worker-network-first.ts';
  private static int $line = 60;
  private static string $message = 'Make sure the regex used here, which is vulnerable to super-linear runtime due to backtracking, cannot lead to denial of service.';
  private static string $hotspotKey = 'AZCc06XbgfifxdiJPzw2';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

  public function load(ObjectManager $manager): void
    {
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $hotspotDetails=(new HotspotDetails())
          ->setMavenKey(self::$mavenKey)
          ->setVersion(self::$version)
          ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
          ->setSecurityCategory(self::$securityCategory)
          ->setRuleKey(self::$ruleKey)
          ->setRuleName(self::$ruleName)
          ->setSeverity(self::$severity)
          ->setStatus(self::$status)
          ->setResolution($this->resolution)
          ->setNiveau(self::$niveau)
          ->setFrontend(self::$frontend)
          ->setBackend(self::$backend)
          ->setAutre(self::$autre)
          ->setFileName(self::$fileName)
          ->setFilePath(self::$filePath)
          ->setLine(self::$line)
          ->setMessage(self::$message)
          ->setHotspotKey(self::$hotspotKey)
          ->setModeCollecte($mode)
          ->setUtilisateurCollecte(self::$utilisateurCollecte)
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
        $manager->persist($hotspotDetails);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
