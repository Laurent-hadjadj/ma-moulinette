<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Properties;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;


/**
 * [Description PropertiesPerformancesTest]
 */
class PropertiesPerformancesTest extends KernelTestCase
{

  private static $type = 'properties';
    private static $projetBd = 100;
    private static $projetSonar = 12;
    private static $profilBd = 12;
    private static $profilSonar = 18;
    private static $dateCreation = '2024-03-26 14:46:38';
    private static $dateModificationProjet = '2024-03-27 10:26:31';
    private static $dateModificationProfil = '2024-04-12 16:23:11';

  private function getEntity(): Properties
  {
    return (new properties())
    ->setType(static::$type)
    ->setProjetBd(static::$projetBd)
    ->setProjetSonar(static::$projetSonar)
    ->setProfilBd(static::$profilBd)
    ->setProfilSonar(static::$profilSonar)
    ->setDateCreation(new \DateTime(static::$dateCreation))
    ->setDateModificationProjet(new \DateTime(static::$dateModificationProjet))
    ->setDateModificationProfil(new \DateTime(static::$dateModificationProfil));
  }

  public function assertHasErrors(Properties $entity, int $number = 0): void
  {
    self::bootKernel();
    $container = static::getContainer();
    $errors = $container->get('validator')->validate($entity);
    $messages = [];
    /** @var ConstraintViolation $error */
    foreach($errors as $error) {
      $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();
    }
    $this->assertCount($number, $errors, implode(', ', $messages));
  }

  public function testPerformance(): void
  {
    $entityCount = 1000; // Nombre d'entités à valider
    $startTime = microtime(true);
    for ($i = 0; $i < $entityCount; $i++) {
        // Créé une nouvelle entité pour chaque itération
        $entity = $this->getEntity()->setType('properties ' . $i);

        // Valide l'entité et vérifie qu'il n'y a pas d'erreurs
        $this->assertHasErrors($entity, 0);
    }
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Le temps d'exécution doit être raisonnable (par exemple, moins de 10 seconde pour 1000 entités)
    $this->assertLessThan(8.0, $executionTime);
  }

}
