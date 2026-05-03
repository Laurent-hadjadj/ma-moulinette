<?php

namespace App\DataFixtures;

use App\Entity\Mesures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description MesuresFixtures]
 */
class MesuresFixtures extends Fixture
{

  // v2.0.0 : refactor de l'entité Mesures.
  //  - `languageDistribution` (array) : champ supprimé (plus dans l'entité).
  //  - `issues` (int agrégé) : remplacé par des compteurs granulaires (openIssues,
  //    maintainabilityIssues, reliabilityIssues, securityIssues, etc.).
  //    Ici on utilise `openIssues` comme approximation compatible.
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $projectName = 'Ma-Moulinette';
  private static $lines = 22015;
  private static $ncloc = 10043;
  private static $files = 18;
  private static $classes = 26;
  private static $functions = 52;
  private static $coverage = 10.3;
  private static $duplicatedLinesDensity = 5.1;
  private static $sqaleDebtRatio = 26.0;
  private static $openIssues = 200;
  private static $tests = 123;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table MESURES */
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $mesures=(new Mesures())
            ->setMavenKey(self::$mavenKey)
            ->setProjectName(self::$projectName)
            ->setLines(self::$lines)
            ->setNcloc(self::$ncloc)
            ->setFiles(self::$files)
            ->setClasses(self::$classes)
            ->setFunctions(self::$functions)
            ->setCoverage(self::$coverage)
            ->setDuplicatedLinesDensity(self::$duplicatedLinesDensity)
            ->setSqaleDebtRatio(self::$sqaleDebtRatio)
            ->setOpenIssues(self::$openIssues)
            ->setTests(self::$tests)
            ->setModeCollecte($mode)
            ->setUtilisateurCollecte(self::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
        $manager->persist($mesures);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
