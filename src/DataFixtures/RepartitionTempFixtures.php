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

  private static $setup = 1000000000001;
  private static $component = '/src/Controller/ApiController.php';
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

  public function load(ObjectManager $manager): void
    {
      $types = ['BUG', 'VULNERABILITY', 'CODE_SMELL'];
      $severities = ['BLOCKER', 'CRITICAL', 'MAJOR', 'MINOR', 'INFO'];

      /** création du jeu de données pour la table RepartitionTemp */
      foreach($types as $type){
        $i = \random_int(0,4);
        $repartitionTemp=(new RepartitionTemp())
            ->setComponent(static::$component)
            ->setType($type)
            ->setSeverity($severities[$i])
            ->setSetup(static::$setup)
            ->setMavenKey(static::$mavenKey);
        $manager->persist($repartitionTemp);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
