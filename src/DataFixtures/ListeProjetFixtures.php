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
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $name = 'Ma-Moulinette';
  private static $tags = ['ma-moulinette', '2048'];
  private static $visibility = 'private';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

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
          static::$mavenKey,
          static::$name,
          static::$visibility,
          static::$tags,
          new \DateTimeImmutable(static::$dateEnregistrement)))
            ->setMavenKey(static::$mavenKey)
            ->setName(static::$name)
            ->setTags(static::$tags)
            ->setVisibility(static::$visibility)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($listeProjet);
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
