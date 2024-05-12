<?php

namespace App\DataFixtures;

use App\Entity\Main\Profiles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use phpDocumentor\Reflection\PseudoTypes\True_;

/**
 * [Description ProfilesFixtures]
 */
class ProfilesFixtures extends Fixture
{

  private static $key = 'AXyXMubJRtAGLwAs7Zcv';
  private static $name = 'Ma-Petite-Entreprise v1.0.0 (2024)';
  private static $languageName = 'css';
  private static $activeRuleCount = 31;
  private static $rulesUpdateAt = '2024-04-13 12:10:51';
  private static $referentielDefault = true;
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  public function load(ObjectManager $manager): void
    {
      /** création du jeu de données pour la table PROFILES */
      $profiles=(new Profiles())
          ->setKey(static::$key)
          ->setName(static::$name)
          ->setLanguageName(static::$languageName)
          ->setActiveRuleCount(static::$activeRuleCount)
          ->setRulesUpdateAt(new \DateTime(static::$rulesUpdateAt))
          ->setReferentielDefault(static::$referentielDefault)
          ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
      $manager->persist($profiles);

      /** Enregistrement des données dans la base de tests */
      $manager->flush();
    }
  }
