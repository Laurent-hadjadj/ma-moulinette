<?php

namespace App\DataFixtures;

use App\Entity\Batch;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description BatchFixtures]
 */
class BatchFixtures extends Fixture
{
    // v2.0.0 : renommage `statut` → `activated`, ajout `automatique`, `responsableShort`
    private static bool $activated = false;
    private static bool $automatique = false;
    private static string $description = 'Mon batch à moi';
    private static string $responsable = 'Laurent HADJADJ';
    private static string $responsableShort = 'L.HADJADJ';
    private static int $nombreProjet = 1;
    private static string $execution = 'OK';
    private static string $dateModification = '2025-01-02 12:00:00+02';
    private static string $dateEnregistrement = '2024-07-31 12:27:05+02';

    public function load(ObjectManager $manager): void
    {
      $data = ['ma-moulinette', 'le-chat', 'logger-tracker'];

      foreach($data as $titre){
        $batch=(new Batch())
          ->setActivated(self::$activated)
          ->setAutomatique(self::$automatique)
          ->setTitre($titre)
          ->setDescription(self::$description)
          ->setResponsable(self::$responsable)
          ->setResponsableShort(self::$responsableShort)
          ->setPortefeuille($titre)
          ->setNombreProjet(self::$nombreProjet)
          ->setExecution(self::$execution)
          ->setDateModification(new \DateTime(self::$dateModification))
          ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
        $manager->persist($batch);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
