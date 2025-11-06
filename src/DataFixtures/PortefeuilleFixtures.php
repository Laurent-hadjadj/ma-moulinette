<?php

namespace App\DataFixtures;

use App\Entity\Portefeuille;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description PortefeuilleFixtures]
 */
class PortefeuilleFixtures extends Fixture
{

  private static $titre = 'MES PROJETS';
  private static $groupe = 'MA PETITE ENTREPRISE';
  private static $liste =  ['fr.ma-petite-entreprise:ma-moulinette'];
  private static $dateModification = '2024-03-26 14:46:38+01';
  private static $dateEnregistrement = '2024-03-25 12:26:58+01';

  /**
   * [Description for load]
   * Chargement des portefeuilles
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
      $portefeuille=(new Portefeuille())
          ->setTitre(static::$titre)
          ->setGroupe(static::$groupe)
          ->setListe(static::$liste)
          ->setDateModification(new \DateTime(static::$dateModification))
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
      $manager->persist($portefeuille);


      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
