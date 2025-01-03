<?php

namespace App\DataFixtures;

use App\Entity\BatchTraitement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description BatchTraitementFixtures]
 */
class BatchTraitementFixtures extends Fixture
{
  private static $mode = 'TRAITEMENT MANUEL';
  private static $result = true;
  private static $titre = 'mon-batch à moi';
  private static $portefeuille = 'application-ma-moulinette';
  private static $nombreProjet = 1;
  private static $responsable = 'Laurent HADJADJ';
  private static $debutTraitement = '2025-01-02 12:00:00+02';
  private static $finTraitement = '2025-01-02 12:02:00+02';
  private static $dateEnregistrement = '2025-01-02 12:02:00+02';


    public function load(ObjectManager $manager): void
    {
      $data = ['COLLECTE', static::$mode, static::$mode, static::$mode, 'TRAITEMENT AUTOMATIQUE'];

      foreach($data as $modeCollecte){
        $batchTraitement=(new BatchTraitement())
          ->setModeCollecte($modeCollecte)
          ->setResult(static::$result)
          ->setTitre(static::$titre)
          ->setPortefeuille(static::$portefeuille)
          ->setNombreProjet(static::$nombreProjet)
          ->setResponsable(static::$responsable)
          ->setDebutTraitement(new \DateTimeImmutable(static::$debutTraitement))
          ->setFinTraitement(new \DateTimeImmutable(static::$finTraitement))
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($batchTraitement);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
