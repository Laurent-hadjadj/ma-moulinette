<?php

namespace App\DataFixtures;

use App\Entity\Hotspots;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description HotspotFixtures]
 */
class HotspotsFixtures extends Fixture
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $version = '1.2.0-RELEASE';
  private static $dateVersion = '2024-07-10 15:26:07+02';
  private static $hotspotKey = 'AZCc06XbgfifxdiJPzw6';
  private static $securityCategory = 'dos';
  private static $ruleKey = 'typescript:S5852';
  private static $probability = 'MEDIUM';
  private static $status = 'TO_REVIEW';
  private static $resolution = 'Todo';
  private static $niveau = 2;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table MESURES */
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $hotspots=(new Hotspots())
          ->setMavenKey(static::$mavenKey)
          ->setVersion(static::$version)
          ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
          ->setHotspotKey(static::$hotspotKey)
          ->setSecurityCategory(static::$securityCategory)
          ->setRuleKey(static::$ruleKey)
          ->setProbability(static::$probability)
          ->setStatus(static::$status)
          ->setResolution(static::$resolution)
          ->setNiveau(static::$niveau)
          ->setModeCollecte($mode)
          ->setUtilisateurCollecte(static::$utilisateurCollecte)
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($hotspots);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
