<?php

namespace App\DataFixtures;

use App\Entity\Profiles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description ProfilesFixtures]
 */
class ProfilesFixtures extends Fixture
{

  private static $key = 'AXyXMubJRtAGLwAs7Zcv';
  private static $name = 'Ma-Petite-Entreprise v1.0.0 (2024)';
  private static $languageName = 'css';
  private static $activeRuleCount = 31;
  private static $rulesUpdatedAt = '2024-04-13 12:10:51+01';
  private static $referentialDefault = true;
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table PROFILES */
      $profiles=(new Profiles())
          ->setKey(self::$key)
          ->setName(self::$name)
          ->setLanguageName(self::$languageName)
          ->setActiveRuleCount(self::$activeRuleCount)
          ->setRulesUpdatedAt(new \DateTimeImmutable(self::$rulesUpdatedAt))
          ->setReferentialDefault(self::$referentialDefault)
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
      $manager->persist($profiles);

      /** Enregistrement des données dans la base de tests */
      $manager->flush();
    }
  }
