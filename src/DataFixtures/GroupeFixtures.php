<?php

namespace App\DataFixtures;

use App\Entity\Groupe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description GroupeFixtures]
 */
class GroupeFixtures extends Fixture
{

  private static $titre = 'MA PETITE ENTREPRISE';
  private static $description = "Équipe de Développement de l'application Ma-Moulinette";
  private static $dateModification = '2024-03-26 14:46:38+02';
  private static $dateEnregistrement = '2024-03-25 12:26:58+02';

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
      $groupe=(new Groupe())
          ->setTitre(static::$titre)
          ->setDescription(static::$description)
          ->setDateModification(new \DateTime(static::$dateModification))
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
      $manager->persist($groupe);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
