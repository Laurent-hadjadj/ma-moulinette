<?php

namespace App\DataFixtures;

use App\Entity\Repartition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description RepartitionFixtures]
 */
class RepartitionFixtures extends Fixture
{
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $name = 'ma-moulinette';
  private static $component = '/controller/auth/reset-password.php';
  private static $type = 'bug';
  private static $setup = '2024032614';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      $data=['low','medium', 'high'];

      foreach($data as $severity){
        $repartition=(new Repartition())
          ->setMavenKey(static::$mavenKey)
          ->setName(static::$name)
          ->setComponent(static::$component)
          ->setType(static::$type)
          ->setSeverity($severity)
          ->setSetup(static::$setup)
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($repartition);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
