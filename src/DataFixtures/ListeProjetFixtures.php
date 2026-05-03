<?php

namespace App\DataFixtures;

use App\Entity\ListeProjet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ListeProjetFixtures]
 */
class ListeProjetFixtures extends Fixture
{
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $name = 'Ma-Moulinette';
  private static $tags = ['ma-moulinette', '2048'];
  private static string $visibility = 'private';
  private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

  /**
   * [Description for load]
   *
   * @param ObjectManager $manager
   *
   * @return void
   *
   * Created at: 19/04/2026 18:34:33 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table LISTE_PROJET */
        $listeProjet=(new ListeProjet(
          self::$mavenKey,
          self::$name,
          self::$visibility,
          self::$tags))
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
        $manager->persist($listeProjet);
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
