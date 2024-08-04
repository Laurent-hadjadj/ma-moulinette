<?php

namespace App\DataFixtures;

use App\Entity\Logger;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description LoggerFixtures]
 */
class LoggerFixtures extends Fixture
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $loggerInfo = 14;
  private static $loggerWarn = 0;
  private static $loggerError = 15;
  private static $loggerDebug = 8;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-03-26 14:46:38+02';

  public function load(ObjectManager $manager): void
    {
      $modeCollecte=['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'];

      foreach($modeCollecte as $mode){
        $logger=(new Logger())
            ->setMavenKey(static::$mavenKey)
            ->setLoggerInfo(static::$loggerInfo)
            ->setLoggerWarn(static::$loggerWarn)
            ->setLoggerError(static::$loggerError)
            ->setLoggerDebug(static::$loggerDebug)
            ->setModeCollecte($mode)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($logger);
      }
      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
