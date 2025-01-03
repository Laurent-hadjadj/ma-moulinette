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
    private static $statut = false;
    private static $description = 'Mon batch à moi';
    private static $responsable = 'Laurent HADJADJ';
    private static $nombreProjet = 1;
    private static $execution = 'OK';
    private static $dateModification = '2025-01-02 12:00:00+02';
    private static $dateEnregistrement = '2024-07-31 12:27:05+02';

    public function load(ObjectManager $manager): void
    {
      $data = ['ma-moulinette', 'le-chat', 'logger-tracker'];

      foreach($data as $titre){
        $batch=(new Batch())
          ->setStatut(static::$statut)
          ->setTitre($titre)
          ->setDescription(static::$description)
          ->setResponsable(static::$responsable)
          ->setPortefeuille($titre)
          ->setNombreProjet(static::$nombreProjet)
          ->setExecution(static::$execution)
          ->setDateModification(new \DateTime(static::$dateModification))
          ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        $manager->persist($batch);
      }

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
