<?php

namespace App\DataFixtures;

use App\Entity\RepartitionTemp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description NoSonarFixtures]
 */
class RepartitionTempFixtures extends Fixture
{

  private static int $setup = 1000000000001;
  private static string $component = '/src/Controller/ApiController.php';
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

  public function load(ObjectManager $manager): void
    {
      $types = ['BUG', 'VULNERABILITY', 'CODE_SMELL'];
      $severities = ['BLOCKER', 'CRITICAL', 'MAJOR', 'MINOR', 'INFO'];

      /** création du jeu de données pour la table RepartitionTemp */
      foreach($types as $type){
        foreach($severities as $severity){
          $repartitionTemp=(new RepartitionTemp())
              ->setComponent(self::$component)
              ->setType($type)
              ->setSeverity($severity)
              ->setSetup(self::$setup)
              ->setMavenKey(self::$mavenKey);
          $manager->persist($repartitionTemp);
        }
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
