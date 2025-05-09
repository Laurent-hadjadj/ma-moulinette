<?php

namespace App\DataFixtures;

use App\Entity\MaMoulinette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description MaMoulinetteFixtures]
 */
class MaMoulinetteFixtures extends Fixture
{

  private static $version = '1.0.0';
  private static $dateVersion = '2024-04-12 16:23:11';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table MA_MOULINETTE */
      $maMoulinette=(new MaMoulinette())
          ->setVersion(static::$version)
          ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
      $manager->persist($maMoulinette);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
