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
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-03-26 14:46:38+01';

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
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      /** création de la note RELIABILITY */
      foreach($modeCollecte as $mode){
        $reliability=(new Notes())
            ->setMavenKey(static::$mavenKey)
            ->setType('reliability')
            ->setValue(3)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setModeCollecte($mode)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($reliability);
      }

      /** création de la note SECURITY */
      foreach($modeCollecte as $mode){
        $security=(new Notes())
            ->setMavenKey(static::$mavenKey)
            ->setType('security')
            ->setValue(1)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setModeCollecte($mode)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($security);
      }

      /** création de la note SQALE */
      foreach($modeCollecte as $mode){
        $sqale=(new Notes())
            ->setMavenKey(static::$mavenKey)
            ->setType('sqale')
            ->setValue(2)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setModeCollecte($mode)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($sqale);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
