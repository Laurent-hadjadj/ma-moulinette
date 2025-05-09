<?php

namespace App\DataFixtures;

use App\Entity\Properties;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description NoSonarOldFixtures]
 */
class PropertiesDiffFixtures extends Fixture
{

  private static $type = 'properties';
  private static $projetBd = 1;
  private static $projetSonar = 1000;
  private static $profilBd = 1;
  private static $profilSonar = 1000;
  private static $dateCreation = '2024-03-26 14:46:38+01';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table PROPERTIES */
      $properties=(new Properties())
          ->setType(static::$type)
          ->setProjetBd(static::$projetBd)
          ->setProjetSonar(static::$projetSonar)
          ->setProfilBd(static::$profilBd)
          ->setProfilSonar(static::$profilSonar)
          ->setDateCreation(new \DateTimeImmutable(static::$dateCreation))
          ->setDateModificationProjet(new \DateTime())
          ->setDateModificationProfil(new \DateTime());
      $manager->persist($properties);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
