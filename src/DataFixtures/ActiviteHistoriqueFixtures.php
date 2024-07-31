<?php

namespace App\DataFixtures;

use App\Entity\ActiviteHistorique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ActiviteHistoriqueFixtures]
 */
class ActiviteHistoriqueFixtures extends Fixture
{

  private static $annee = 2024;
  private static $nbJour = 326;
  private static $nbAnalyse = 1253;
  private static $moyenneAnalyse = 87.3;
  private static $nbReussi = 1249;
  private static $nbEchec = 4;
  private static $tauxReussite = 0.99;
  private static $maxTemps = 34;
  private static $dateEnregistrement = '2024-07-14 19:36:33+02';

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
      $activiteHistorique=(new ActiviteHistorique())
          ->setAnnee(static::$annee)
          ->setNbJour(static::$nbJour)
          ->setNbAnalyse(static::$nbAnalyse)
          ->setMoyenneAnalyse(static::$moyenneAnalyse)
          ->setNbReussi(static::$nbReussi)
          ->setNbEchec(static::$nbEchec)
          ->setTauxReussite(static::$tauxReussite)
          ->setmaxTemps(static::$maxTemps)
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
      $manager->persist($activiteHistorique);
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
