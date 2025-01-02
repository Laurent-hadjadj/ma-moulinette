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

  private static $dateCourte = '2022-04-14';
  private static $language = 'java';
  private static $date  = '2022-08-30T18:42:41+0200';
  private static $action = 'ACTIVATED';
  private static $auteur = 'HADJADJ Laurent';
  private static $rule = 'java:S5679';
  private static $description = 'OpenSAML2 should be configured to prevent authentication bypass';
  private static $detail = '{"severity":"MAJOR"}';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {

      /** création du jeu de données pour la table PROFILES HISTORIQUE */
      $profiles=(new ProfilesHistorique())
          ->setDateCourte(new \DateTimeImmutable(static::$dateCourte))
          ->setLanguage(static::$language)
          ->setDate(new \DateTimeImmutable(static::$date))
          ->setAction(static::$action)
          ->setAuteur(static::$auteur)
          ->setRule(static::$rule)
          ->setDescription(static::$description)
          ->setDetail(static::$detail)
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
      $manager->persist($profiles);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
