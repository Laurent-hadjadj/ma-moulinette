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
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      /** création du jeu de données pour la table INFORMATION_PROJET */
      foreach($modeCollecte as $mode){
        $listeProjet=(new InformationProjet())
          ->setMavenKey(static::$mavenKey)
          ->setAnalyseKey(static::$analyseKey)
          ->setDate(new \DateTimeImmutable(static::$date))
          ->setProjectVersion(static::$projectVersion)
          ->setType(static::$type)
          ->setModeCollecte($mode)
          ->setUtilisateurCollecte(static::$utilisateurCollecte)
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
          $manager->persist($listeProjet);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
