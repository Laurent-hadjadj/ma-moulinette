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

  private static string $titre = 'MES PROJETS';
  private static string $groupe = 'MA PETITE ENTREPRISE';
  private static $liste =  ['fr.ma-petite-entreprise:ma-moulinette'];
  private static string $dateModification = '2024-03-26 14:46:38+01';
  private static string $dateEnregistrement = '2024-03-25 12:26:58+01';

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
          ->setPortefeuille(self::$titre)
          ->setGroupeFonctionnel(self::$groupe)
          ->setListe(self::$liste)
          ->setDateModification(new \DateTime(self::$dateModification))
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
      $manager->persist($portefeuille);


      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
