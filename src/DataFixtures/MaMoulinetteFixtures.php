<?php

namespace App\DataFixtures;

use App\Entity\Main\MaMoulinette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description MaMoulinetteFixtures]
 */
class MaMoulinetteFixtures extends Fixture
{

  private static $version = '2.0.0';
  private static $dateVersion = '2024-04-12 16:23:11';
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table LISTEPROJET */
      $maMoulinette=(new MaMoulinette())
          ->setVersion(static::$version)
          ->setDateVersion(new \DateTime(static::$dateVersion))
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($maMoulinette);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
