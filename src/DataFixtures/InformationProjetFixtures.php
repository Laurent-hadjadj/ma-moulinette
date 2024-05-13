<?php

namespace App\DataFixtures;

use App\Entity\InformationProjet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description InformationProjetFixtures]
 */
class InformationProjetFixtures extends Fixture
{
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $analyseKey = 'AYVyxZcQo0TJpgSeq-ph';
  private static $date = '2024-04-12 16:23:11';
  private static $projectVersion = '2.0.0-RELEASE';
  private static $type = 'RELEASE';
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table LISTEPROJET */
      $listeProjet=(new InformationProjet())
          ->setMavenKey(static::$mavenKey)
          ->setAnalyseKey(static::$analyseKey)
          ->setDate(new \DateTime(static::$date))
          ->setProjectVersion(static::$projectVersion)
          ->setType(static::$type)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($listeProjet);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
