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
  // v2.0.0 : le constructeur BatchTraitement exige titre/portefeuille/responsable/responsableShort.
  // `result` (bool) est remplacé par `success` (bool, nullable) — true = succès.
  private static string $mode = 'TRAITEMENT MANUEL';
  private static bool $success = true;
  private static string $titre = 'mon-batch à moi';
  private static string $portefeuille = 'application-ma-moulinette';
  private static int $nombreProjet = 1;
  private static string $responsable = 'Laurent HADJADJ';
  private static string $responsableShort = 'L.HADJADJ';
  private static string $debutTraitement = '2025-01-02 12:00:00+02';
  private static string $finTraitement = '2025-01-02 12:02:00+02';
  private static string $dateEnregistrement = '2025-01-02 12:02:00+02';


    public function load(ObjectManager $manager): void
    {
      $data = ['COLLECTE', self::$mode, self::$mode, self::$mode, 'TRAITEMENT AUTOMATIQUE'];

      foreach($data as $modeCollecte){
        $batchTraitement = new BatchTraitement(
            titre: self::$titre,
            portefeuille: self::$portefeuille,
            responsable: self::$responsable,
            responsableShort: self::$responsableShort,
            modeCollecte: $modeCollecte,
            nombreProjet: self::$nombreProjet
        );
        $batchTraitement
          ->setSuccess(self::$success)
          ->setDebutTraitement(new \DateTimeImmutable(self::$debutTraitement))
          ->setFinTraitement(new \DateTimeImmutable(self::$finTraitement))
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
        $manager->persist($batchTraitement);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
