<?php

namespace App\DataFixtures;

use App\Entity\Main\Mesures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description MesuresFixtures]
 */
class MesuresFixtures extends Fixture
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $projectName = 'Ma-Moulinette';
  private static $lines = 22015;
  private static $ncloc = 10043;
  private static $coverage = 10.3;
  private static $duplicationDensity = 5.1;
  private static $sqaleDebtRatio = 26.0;
  private static $issues = 200;
  private static $tests = 123;
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table MESURES */
      $mesures=(new Mesures())
          ->setMavenKey(static::$mavenKey)
          ->setProjectName(static::$projectName)
          ->setLines(static::$lines)
          ->setNcloc(static::$ncloc)
          ->setCoverage(static::$coverage)
          ->setDuplicationDensity(static::$duplicationDensity)
          ->setSqaleDebtRatio(static::$sqaleDebtRatio)
          ->setIssues(static::$issues)
          ->setTests(static::$tests)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($mesures);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
