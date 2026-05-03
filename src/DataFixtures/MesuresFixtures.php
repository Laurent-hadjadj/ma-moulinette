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
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $projectName = 'Ma-Moulinette';
  private static int $lines = 22015;
  private static int $ncloc = 10043;
  private static int $files = 18;
  private static int $classes = 26;
  private static int $functions = 52;
  private static float $coverage = 10.3;
  private static float $duplicatedLinesDensity = 5.1;
  private static float $sqaleDebtRatio = 26.0;
  private static int $openIssues = 200;
  private static int $tests = 123;
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

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
