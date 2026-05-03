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
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $analyseKey = 'AYVyxZcQo0TJpgSeq-ph';
  private static string $date = '2024-04-12 16:23:11';
  private static string $projectVersion = '2.0.0-RELEASE';
  private static string $type = 'RELEASE';
  private static int $versionSonar = 59;
  private static int $versionReleaseSonar = 54;
  private static int $versionSnapshotSonar = 3;
  private static int $versionAutreSonar = 2;
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      /** création du jeu de données pour la table INFORMATION_PROJET */
      foreach($modeCollecte as $mode){
        $listeProjet=(new InformationProjet())
          ->setMavenKey(self::$mavenKey)
          ->setAnalyseKey(self::$analyseKey)
          ->setDate(new \DateTimeImmutable(self::$date))
          ->setProjectVersion(self::$projectVersion)
          ->setType(self::$type)
          ->setVersionSonar(self::$versionSonar)
          ->setVersionReleaseSonar(self::$versionReleaseSonar)
          ->setVersionSnapshotSonar(self::$versionSnapshotSonar)
          ->setVersionAutreSonar(self::$versionAutreSonar)
          ->setModeCollecte($mode)
          ->setUtilisateurCollecte(self::$utilisateurCollecte)
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
          $manager->persist($listeProjet);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
