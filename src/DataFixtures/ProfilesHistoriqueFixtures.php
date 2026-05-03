<?php

namespace App\DataFixtures;

use App\Entity\ProfilesHistorique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ProfilesHistoriqueFixtures]
 */
class ProfilesHistoriqueFixtures extends Fixture
{

  private static string $dateCourte = '2022-04-14';
  private static string $language = 'java';
  private static string $date = '2022-08-30T18:42:41+0200';
  private static string $action = 'ACTIVATED';
  private static string $auteur = 'HADJADJ Laurent';
  private static string $rule = 'java:S5679';
  private static string $description = 'OpenSAML2 should be configured to prevent authentication bypass';
  private static string $detail = '{"severity":"MAJOR"}';
  private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {

      /** création du jeu de données pour la table PROFILES HISTORIQUE */
      $profiles=(new ProfilesHistorique())
          ->setDateCourte(new \DateTimeImmutable(self::$dateCourte))
          ->setLanguage(self::$language)
          ->setDate(new \DateTimeImmutable(self::$date))
          ->setAction(self::$action)
          ->setAuteur(self::$auteur)
          ->setRule(self::$rule)
          ->setDescription(self::$description)
          ->setDetail(self::$detail)
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
      $manager->persist($profiles);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
