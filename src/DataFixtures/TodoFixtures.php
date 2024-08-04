<?php

namespace App\DataFixtures;

use App\Entity\Todo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description TodoFixtures]
 */
class TodoFixtures extends Fixture
{
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $rule = 'java:S1135';
  private static $component = 'fr.ma-petite-entreprise:ma-moulinette:ma-moulinette/src/main/java/fr/ma-petite-entreprise/service/AnalyseTraceService.java';
  private static $line = 81;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-03-26 14:46:38+02';

  public function load(ObjectManager $manager): void
    {
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $todo=(new Todo())
            ->setMavenKey(static::$mavenKey)
            ->setRule(static::$rule)
            ->setComponent(static::$component)
            ->setLine(static::$line)
            ->setModeCollecte($mode)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($todo);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
