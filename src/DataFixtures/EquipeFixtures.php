<?php

namespace App\DataFixtures;

use App\Entity\Equipe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description EquipeFixtures]
 */
class EquipeFixtures extends Fixture
{

  private static $titre = 'MA PETITE ENTREPRISE';
  private static $description = "Equipe de Développement de l'application Ma-Moulinette";
  private static $dateModification = '2024-03-26 14:46:38';
  private static $dateEnregistrement = '2024-03-25 12:26:58';

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
      $equipe=(new Equipe())
          ->setTitre(static::$titre)
          ->setDescription(static::$description)
          ->setDateModification(new \DateTime(static::$dateModification))
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($equipe);


      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
