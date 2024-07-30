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

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $projectName = 'Ma-Moulinette';
  private static $lines = 22015;
  private static $ncloc = 10043;
  private static $languageDistribution = ['java'=>4278, 'ts'=>18690];
  private static $coverage = 10.3;
  private static $duplicatedLinesDensity = 5.1;
  private static $sqaleDebtRatio = 26.0;
  private static $issues = 200;
  private static $tests = 123;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

//'skipped_tests' => $data->skipped_tests,
//'test_errors' => $data->test_errors,
//'test_failures' => $data->test_failures,
//  'classes' => $data->classes,
//  'comment_lines' => $data->comment_lines,
//  'comment_lines_density' => $data->comment_lines_density,
//  'files' => $data->files,
//  'nombre_ligne' => $data->lines,
//  'nombre_ligne_code' => $data->ncloc,
//  'ncloc_language_distribution' =>$data->ncloc_language_distribution,
//  'functions' => $data->functions,


  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table MESURES */
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $mesures=(new Mesures())
            ->setMavenKey(static::$mavenKey)
            ->setProjectName(static::$projectName)
            ->setLines(static::$lines)
            ->setNcloc(static::$ncloc)
            ->setLanguageDistribution(static::$languageDistribution)
            ->setCoverage(static::$coverage)
            ->setDuplicatedLinesDensity(static::$duplicatedLinesDensity)
            ->setSqaleDebtRatio(static::$sqaleDebtRatio)
            ->setIssues(static::$issues)
            ->setTests(static::$tests)
            ->setModeCollecte($mode)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($mesures);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
