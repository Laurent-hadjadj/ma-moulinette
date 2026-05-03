<?php

namespace App\DataFixtures;

use App\Entity\HotspotOwasp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description HotspotOwaspFixtures]
 */
class HotspotOwaspFixtures extends Fixture
{

  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static int $referentialOwasp = 2017;
  private static string $version = '1.2.0-RELEASE';
  private static string $dateVersion = '2024-07-10 15:26:07+02';
  private static string $menace = 'a1';
  private static string $securityCategory = 'dos';
  private static string $ruleKey = 'typescript:S5852';
  private static string $probability = 'MEDIUM';
  private static string $status = 'TO_REVIEW';
  private static string $resolution = 'Todo';
  private static int $niveau = 2;
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table MESURES */
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $hotspotOwasp=(new HotspotOwasp())
          ->setMavenKey(self::$mavenKey)
          ->setReferentialOwasp(self::$referentialOwasp)
          ->setVersion(self::$version)
          ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
          ->setMenace(self::$menace)
          ->setSecurityCategory(self::$securityCategory)
          ->setRuleKey(self::$ruleKey)
          ->setProbability(self::$probability)
          ->setStatus(self::$status)
          ->setResolution(self::$resolution)
          ->setNiveau(self::$niveau)
          ->setModeCollecte($mode)
          ->setUtilisateurCollecte(self::$utilisateurCollecte)
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
        $manager->persist($hotspotOwasp);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
