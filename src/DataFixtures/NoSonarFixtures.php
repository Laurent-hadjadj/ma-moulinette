<?php

namespace App\DataFixtures;

use App\Entity\NoSonar;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description NoSonarFixtures]
 */
class NoSonarFixtures extends Fixture
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $rule = 'java:S1309';
  private static $component = 'fr.ma-petite-entreprise:mo-moulinette:
  ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java';
  private static $line = 118;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-03-26 14:46:38+02';

  public function load(ObjectManager $manager): void
    {
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $nosonar=(new NoSonar())
            ->setMavenKey(static::$mavenKey)
            ->setRule(static::$rule)
            ->setComponent(static::$component)
            ->setLine(static::$line)
            ->setModeCollecte($mode)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($nosonar);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
