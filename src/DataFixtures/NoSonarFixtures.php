<?php

namespace App\DataFixtures;

use App\Entity\Main\NoSonar;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


/**
 * [Description NoSonarFixtures]
 */
class NoSonarFixtures extends Fixture
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $rule = 'java:S1309';
  private static $component = 'fr.ma-petite-entreprise:mo-moulinette:
  ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java';
  private static $line = 118;
  private static $dateEnregistrement = '2024-03-26 14:46:38';

  public function load(ObjectManager $manager): void
    {
      /** création de la note RELIABILITY */
      $reliability=(new NoSonar())
          ->setMavenKey(static::$mavenKey)
          ->setRule(static::$rule)
          ->setComponent(static::$component)
          ->setLine(static::$line)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($reliability);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
