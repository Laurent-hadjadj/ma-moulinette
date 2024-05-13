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
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table LISTEPROJET */
      $listeProjet=(new ListeProjet())
          ->setMavenKey(static::$mavenKey)
          ->setName(static::$name)
          ->setTags(static::$tags)
          ->setVisibility(static::$visibility)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($listeProjet);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
