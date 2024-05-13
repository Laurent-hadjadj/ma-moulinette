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
  private static $dateCreation = '2024-03-26 14:46:38';
  private static $dateModificationProjet = '2024-03-27 10:26:31';
  private static $dateModificationProfil = '2024-04-12 16:23:11';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table PROPERTIES */
      $properties=(new Properties())
          ->setType(static::$type)
          ->setProjetBd(static::$projetBd)
          ->setProjetSonar(static::$projetSonar)
          ->setProfilBd(static::$profilBd)
          ->setProfilSonar(static::$profilSonar)
          ->setDateCreation(new \DateTime(static::$dateCreation))
          ->setDateModificationProjet(new \DateTime(static::$dateModificationProjet))
          ->setDateModificationProfil(new \DateTime(static::$dateModificationProfil));
      $manager->persist($properties);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
