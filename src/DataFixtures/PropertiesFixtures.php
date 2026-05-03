<?php

namespace App\DataFixtures;

use App\Entity\Properties;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description NoSonarFixtures]
 */
class PropertiesFixtures extends Fixture
{

  private static $type = 'properties';
  private static $projetBd = 100;
  private static $projetSonar = 12;
  private static $profilBd = 12;
  private static $profilSonar = 18;
  private static $dateCreation = '2024-03-26 14:46:38+01';
  private static $dateModificationProjet = '2024-03-27 10:26:31+01';
  private static $dateModificationProfil = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table PROPERTIES */
      $properties=(new Properties())
          ->setType(self::$type)
          ->setProjetBd(self::$projetBd)
          ->setProjetSonar(self::$projetSonar)
          ->setProfilBd(self::$profilBd)
          ->setProfilSonar(self::$profilSonar)
          ->setDateCreation(new \DateTimeImmutable(self::$dateCreation))
          ->setDateModificationProjet(new \DateTime(self::$dateModificationProjet))
          ->setDateModificationProfil(new \DateTime(self::$dateModificationProfil));
      $manager->persist($properties);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
