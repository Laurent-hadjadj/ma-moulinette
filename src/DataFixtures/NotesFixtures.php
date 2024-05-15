<?php

namespace App\DataFixtures;

use App\Entity\Notes;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description NotesFixtures]
 */
class NotesFixtures extends Fixture
{
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $date = '2024-023-29 17:23:18';
  private static $dateEnregistrement = '2024-03-26 14:46:38';

  /**
   * [Description for load]
   * Chargement des utilisateurs
   *
   * @param ObjectManager $manager
   *
   * @return void
   *
   * Created at: 05/05/2024 18:43:05 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function load(ObjectManager $manager): void
    {
      /** création de la note RELIABILITY */
      $reliability=(new Notes())
          ->setMavenKey(static::$mavenKey)
          ->setType('reliability')
          ->setDate(new \DateTime(static::$date))
          ->setValue(3)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($reliability);

      /** création de la note SECURITY */
      $security=(new Notes())
          ->setMavenKey(static::$mavenKey)
          ->setType('securiy')
          ->setDate(new \DateTime(static::$date))
          ->setValue(1)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($security);

      /** création de la note SQALE */
      $sqale=(new Notes())
          ->setMavenKey(static::$mavenKey)
          ->setType('sqale')
          ->setDate(new \DateTime(static::$date))
          ->setValue(2)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($sqale);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
